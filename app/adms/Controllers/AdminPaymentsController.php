<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PDO;
use PDOException;

class AdminPaymentsController
{
    private array $data = [];
    private PDO $conn;

    public function __construct()
    {
        $this->conn = $this->getConnection();
    }

    /**
     * Obtém conexão com o banco de dados
     */
    private function getConnection(): PDO
    {
        try {
            $host = 'localhost';
            $dbname = 'nixcom';
            $username = 'root';
            $password = '';
            
            $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            error_log("AdminPaymentsController::getConnection - Erro: " . $e->getMessage());
            die("Erro de conexão com o banco de dados");
        }
    }

    /**
     * Dashboard de pagamentos
     */
    public function index(): void
    {
        $this->data['title'] = 'Gerenciar Pagamentos';
        
        // Buscar pagamentos pendentes
        $this->data['pending_payments'] = $this->getPendingPayments();
        
        // Buscar estatísticas
        $this->data['stats'] = $this->getPaymentStats();
        
        $this->loadView('admin-payments/index');
    }

    /**
     * Lista de pagamentos
     */
    public function list(): void
    {
        $this->data['title'] = 'Lista de Pagamentos';
        
        // Buscar todos os pagamentos
        $this->data['payments'] = $this->getAllPayments();
        
        $this->loadView('admin-payments/list');
    }

    /**
     * Aprovar pagamento
     */
    public function approve(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentId = $_POST['payment_id'] ?? null;
            $userId = $_POST['user_id'] ?? null;
            
            if ($paymentId && $userId) {
                if ($this->approvePayment($paymentId, $userId)) {
                    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Pagamento aprovado com sucesso!'];
                } else {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro ao aprovar pagamento!'];
                }
            }
        }
        
        header("Location: " . URLADM . "admin-payments");
        exit();
    }

    /**
     * Rejeitar pagamento
     */
    public function reject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentId = $_POST['payment_id'] ?? null;
            $userId = $_POST['user_id'] ?? null;
            $reason = $_POST['reason'] ?? 'Pagamento rejeitado';
            
            if ($paymentId && $userId) {
                if ($this->rejectPayment($paymentId, $userId, $reason)) {
                    $_SESSION['msg'] = ['type' => 'success', 'text' => 'Pagamento rejeitado!'];
                } else {
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro ao rejeitar pagamento!'];
                }
            }
        }
        
        header("Location: " . URLADM . "admin-payments");
        exit();
    }

    /**
     * Buscar pagamentos pendentes
     */
    private function getPendingPayments(): array
    {
        try {
            $query = "SELECT p.*, u.nome, u.email, u.telefone 
                     FROM pagamentos p 
                     JOIN usuarios u ON p.user_id = u.id 
                     WHERE p.status = 'pending' 
                     ORDER BY p.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdminPaymentsController::getPendingPayments - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar todos os pagamentos
     */
    private function getAllPayments(): array
    {
        try {
            $query = "SELECT p.*, u.nome, u.email, u.telefone 
                     FROM pagamentos p 
                     JOIN usuarios u ON p.user_id = u.id 
                     ORDER BY p.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdminPaymentsController::getAllPayments - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar estatísticas de pagamentos
     */
    private function getPaymentStats(): array
    {
        try {
            $stats = [];
            
            // Total de pagamentos
            $query = "SELECT COUNT(*) as total FROM pagamentos";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Pagamentos pendentes
            $query = "SELECT COUNT(*) as pending FROM pagamentos WHERE status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
            
            // Pagamentos aprovados
            $query = "SELECT COUNT(*) as approved FROM pagamentos WHERE status = 'approved'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['approved'] = $stmt->fetch(PDO::FETCH_ASSOC)['approved'];
            
            // Valor total aprovado
            $query = "SELECT SUM(amount) as total_amount FROM pagamentos WHERE status = 'approved'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['total_amount'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'] ?? 0;
            
            return $stats;
        } catch (PDOException $e) {
            error_log("AdminPaymentsController::getPaymentStats - Erro: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'total_amount' => 0
            ];
        }
    }

    /**
     * Aprovar pagamento
     */
    private function approvePayment(int $paymentId, int $userId): bool
    {
        try {
            $this->conn->beginTransaction();
            
            // Atualizar status do pagamento
            $query = "UPDATE pagamentos SET status = 'approved', approved_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$paymentId]);
            
            // Ativar assinatura do usuário
            $query = "UPDATE usuarios SET plano = 'premium', status_pagamento = 'approved' WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            
            // Criar notificação para o usuário
            $this->createUserNotification($userId, 'Pagamento Aprovado', 'Seu pagamento foi aprovado! Seu plano foi ativado.');
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("AdminPaymentsController::approvePayment - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rejeitar pagamento
     */
    private function rejectPayment(int $paymentId, int $userId, string $reason): bool
    {
        try {
            $this->conn->beginTransaction();
            
            // Atualizar status do pagamento
            $query = "UPDATE pagamentos SET status = 'rejected', rejected_at = NOW(), rejection_reason = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$reason, $paymentId]);
            
            // Criar notificação para o usuário
            $this->createUserNotification($userId, 'Pagamento Rejeitado', 'Seu pagamento foi rejeitado. Motivo: ' . $reason);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("AdminPaymentsController::rejectPayment - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Criar notificação para usuário
     */
    private function createUserNotification(int $userId, string $title, string $message): void
    {
        try {
            $query = "INSERT INTO notificacoes (user_id, type, title, message, created_at) 
                     VALUES (?, 'payment', ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $title, $message]);
        } catch (PDOException $e) {
            error_log("AdminPaymentsController::createUserNotification - Erro: " . $e->getMessage());
        }
    }

    /**
     * Carrega a view
     */
    private function loadView(string $view): void
    {
        $this->data['view'] = $view;
        extract($this->data);
        require_once "app/adms/Views/layout/main.php";
    }
}

