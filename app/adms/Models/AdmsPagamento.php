<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use Adms\Models\AdmsNotificacao;
use PDO;
use PDOException;

class AdmsPagamento extends StsConn
{
    private object $conn;
    private string $mercadopago_access_token;
    private string $mercadopago_public_key;
    private bool $sandbox_mode;

    public function __construct()
    {
        $this->conn = $this->connectDb();
        
        // Configurações do Mercado Pago (modo sandbox para desenvolvimento)
        $this->mercadopago_access_token = 'TEST-1234567890-abcdefghijklmnopqrstuvwxyz-1234567890'; // Substitua pela sua chave
        $this->mercadopago_public_key = 'TEST-12345678-1234-1234-1234-123456789012'; // Substitua pela sua chave
        $this->sandbox_mode = true; // Modo sandbox para desenvolvimento
    }

    /**
     * Cria um pagamento PIX via Mercado Pago
     */
    public function createPixPayment(int $userId, int $planoId, string $periodo = '1_mes'): array
    {
        try {
            // Buscar dados do plano
            $plano = $this->getPlanById($planoId);
            if (!$plano) {
                return ['success' => false, 'message' => 'Plano não encontrado'];
            }

            // Calcular valor baseado no período
            $valor = $this->calculatePrice($plano, $periodo);
            
            // Gerar ID único para o pagamento
            $paymentId = 'PIX_' . time() . '_' . rand(1000, 9999);
            
            // Dados para o Mercado Pago
            $paymentData = [
                'transaction_amount' => $valor,
                'description' => "Pagamento {$plano['nome']} - {$periodo}",
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $_SESSION['user_email'] ?? 'usuario@exemplo.com'
                ],
                'external_reference' => $paymentId,
                'notification_url' => URLADM . 'pagamento/webhook'
            ];

            // Simular resposta do Mercado Pago (em produção, fazer requisição real)
            $mercadopagoResponse = $this->simulateMercadoPagoResponse($paymentData);
            
            if ($mercadopagoResponse['success']) {
                // Salvar pagamento no banco
                $this->savePayment($userId, $planoId, $valor, $periodo, $paymentId, $mercadopagoResponse);
                
                return [
                    'success' => true,
                    'payment_id' => $paymentId,
                    'qr_code' => $mercadopagoResponse['qr_code'],
                    'qr_code_base64' => $mercadopagoResponse['qr_code_base64'],
                    'pix_copy_paste' => $mercadopagoResponse['pix_copy_paste'],
                    'expires_at' => $mercadopagoResponse['expires_at']
                ];
            } else {
                return ['success' => false, 'message' => 'Erro ao criar pagamento PIX'];
            }
            
        } catch (\Exception $e) {
            error_log("AdmsPagamento::createPixPayment - Erro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }

    /**
     * Simula resposta do Mercado Pago (remover em produção)
     */
    private function simulateMercadoPagoResponse(array $paymentData): array
    {
        // Em produção, fazer requisição real para a API do Mercado Pago
        return [
            'success' => true,
            'id' => 'MP_' . time(),
            'status' => 'pending',
            'qr_code' => '00020126360014BR.GOV.BCB.PIX0114+5511999999999520400005303986540.005802BR5913MERCADO PAGO6009SAO PAULO62070503***6304' . rand(1000, 9999),
            'qr_code_base64' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'pix_copy_paste' => '00020126360014BR.GOV.BCB.PIX0114+5511999999999520400005303986540.005802BR5913MERCADO PAGO6009SAO PAULO62070503***6304' . rand(1000, 9999),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ];
    }

    /**
     * Salva pagamento no banco de dados
     */
    private function savePayment(int $userId, int $planoId, float $valor, string $periodo, string $paymentId, array $mercadopagoResponse): bool
    {
        try {
            $query = "INSERT INTO pagamentos 
                      (user_id, valor, metodo, gateway, status, transaction_id, pix_code, pix_qr_code, created_at) 
                      VALUES (:user_id, :valor, :metodo, :gateway, :status, :transaction_id, :pix_code, :pix_qr_code, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
            $stmt->bindParam(':metodo', 'pix', PDO::PARAM_STR);
            $stmt->bindParam(':gateway', 'mercadopago', PDO::PARAM_STR);
            $stmt->bindParam(':status', 'pendente', PDO::PARAM_STR);
            $stmt->bindParam(':transaction_id', $paymentId, PDO::PARAM_STR);
            $stmt->bindParam(':pix_code', $mercadopagoResponse['pix_copy_paste'], PDO::PARAM_STR);
            $stmt->bindParam(':pix_qr_code', $mercadopagoResponse['qr_code_base64'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AdmsPagamento::savePayment - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca plano por ID
     */
    private function getPlanById(int $planoId): ?array
    {
        try {
            $query = "SELECT * FROM planos WHERE id = :id AND ativo = 1 LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $planoId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("AdmsPagamento::getPlanById - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcula preço baseado no período
     */
    private function calculatePrice(array $plano, string $periodo): float
    {
        switch ($periodo) {
            case '6_meses':
                return (float) $plano['preco_6_meses'];
            case '12_meses':
                return (float) $plano['preco_12_meses'];
            default:
                return (float) $plano['preco_mensal'];
        }
    }

    /**
     * Verifica status do pagamento
     */
    public function checkPaymentStatus(string $paymentId): array
    {
        try {
            $query = "SELECT * FROM pagamentos WHERE transaction_id = :payment_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_id', $paymentId, PDO::PARAM_STR);
            $stmt->execute();
            
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                return ['success' => false, 'message' => 'Pagamento não encontrado'];
            }
            
            return [
                'success' => true,
                'status' => $payment['status'],
                'payment' => $payment
            ];
        } catch (PDOException $e) {
            error_log("AdmsPagamento::checkPaymentStatus - Erro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao verificar status'];
        }
    }

    /**
     * Atualiza status do pagamento
     */
    public function updatePaymentStatus(string $paymentId, string $status): bool
    {
        try {
            $query = "UPDATE pagamentos SET status = :status, updated_at = NOW() WHERE transaction_id = :payment_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':payment_id', $paymentId, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AdmsPagamento::updatePaymentStatus - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Webhook para receber notificações do Mercado Pago
     */
    public function webhook(): void
    {
        // Em produção, implementar validação do webhook do Mercado Pago
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($data && isset($data['data']['id'])) {
            // Processar notificação do Mercado Pago
            $this->processMercadoPagoNotification($data);
        }
    }

    /**
     * Processa notificação do Mercado Pago
     */
    private function processMercadoPagoNotification(array $data): void
    {
        // Implementar lógica para processar notificação
        // Em produção, validar assinatura do webhook
        error_log("Webhook Mercado Pago recebido: " . json_encode($data));
    }

    /**
     * Busca pagamentos de um usuário
     */
    public function getUserPayments(int $userId): array
    {
        try {
            $query = "SELECT p.*, pl.nome as plano_nome, pl.tipo as plano_tipo 
                      FROM pagamentos p 
                      LEFT JOIN planos pl ON p.plano_id = pl.id 
                      WHERE p.user_id = :user_id 
                      ORDER BY p.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdmsPagamento::getUserPayments - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aprova pagamento de um usuário
     */
    public function approvePayment(string $paymentId, int $userId): bool
    {
        try {
            $this->conn->beginTransaction();
            
            // Atualizar status do pagamento
            $query = "UPDATE pagamentos SET status = 'aprovado', data_pagamento = NOW(), updated_at = NOW() 
                      WHERE transaction_id = :payment_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_id', $paymentId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Atualizar status do usuário (alinhar com enum: 'pending'|'paid'|'failed')
            $query = "UPDATE usuarios SET payment_status = 'paid', updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Enviar notificação para o usuário
            $this->notifyUserPaymentApproved($userId);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("AdmsPagamento::approvePayment - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica usuário sobre aprovação de pagamento
     */
    private function notifyUserPaymentApproved(int $userId): void
    {
        try {
            // Buscar dados do usuário
            $query = "SELECT nome, plan_type FROM usuarios WHERE id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $admsNotificacao = new AdmsNotificacao();
                $admsNotificacao->notifyPaymentApproved($userId, ucfirst($user['plan_type']));
            }
        } catch (\Exception $e) {
            error_log("AdmsPagamento::notifyUserPaymentApproved - Erro: " . $e->getMessage());
        }
    }
}
