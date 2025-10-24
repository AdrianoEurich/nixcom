<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PDO;
use PDOException;

class AdmsNotificacao
{
    private object $conn;

    public function __construct()
    {
        try {
            $host = 'localhost';
            $dbname = 'nixcom';
            $username = 'root';
            $password = '';
            
            $this->conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("AdmsNotificacao::__construct - Erro de conexão: " . $e->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
    }

    /**
     * Cria uma nova notificação
     */
    public function createNotification(int $userId, string $type, string $title, string $message, array $data = []): bool
    {
        try {
            $query = "INSERT INTO notificacoes (user_id, type, title, message, data, created_at) 
                      VALUES (:user_id, :type, :title, :message, :data, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            
            // Corrigir erro de referência - criar variável primeiro
            $dataJson = json_encode($data);
            $stmt->bindParam(':data', $dataJson, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AdmsNotificacao::createNotification - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica administradores sobre novo usuário
     */
    public function notifyNewUser(int $userId, string $userName, string $userEmail, string $planType): bool
    {
        try {
            // Buscar todos os administradores
            $query = "SELECT id FROM usuarios WHERE nivel_acesso = 'administrador'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $success = true;
            foreach ($admins as $admin) {
                $result = $this->createNotification(
                    $admin['id'],
                    'new_user',
                    'Novo Usuário Cadastrado',
                    "O usuário {$userName} ({$userEmail}) se cadastrou com o plano {$planType}",
                    [
                        'user_id' => $userId,
                        'user_name' => $userName,
                        'user_email' => $userEmail,
                        'plan_type' => $planType
                    ]
                );
                
                if (!$result) {
                    $success = false;
                }
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("AdmsNotificacao::notifyNewUser - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica administradores sobre novo pagamento
     */
    public function notifyNewPayment(int $userId, string $userName, float $amount, string $planName): bool
    {
        try {
            // Buscar todos os administradores
            $query = "SELECT id FROM usuarios WHERE nivel_acesso = 'administrador'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $success = true;
            foreach ($admins as $admin) {
                $result = $this->createNotification(
                    $admin['id'],
                    'new_payment',
                    'Novo Pagamento Recebido',
                    "O usuário {$userName} efetuou um pagamento de R$ " . number_format($amount, 2, ',', '.') . " para o plano {$planName}",
                    [
                        'user_id' => $userId,
                        'user_name' => $userName,
                        'amount' => $amount,
                        'plan_name' => $planName
                    ]
                );
                
                if (!$result) {
                    $success = false;
                }
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("AdmsNotificacao::notifyNewPayment - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica usuário sobre aprovação de pagamento
     */
    public function notifyPaymentApproved(int $userId, string $planName): bool
    {
        return $this->createNotification(
            $userId,
            'payment_approved',
            'Pagamento Aprovado!',
            "Seu pagamento foi aprovado e você agora tem acesso ao plano {$planName}",
            [
                'plan_name' => $planName,
                'action' => 'upgrade_plan'
            ]
        );
    }

    /**
     * Notifica usuário sobre status do anúncio
     */
    public function notifyAnuncioStatus(int $userId, string $status, string $message = ''): bool
    {
        $titles = [
            'active' => 'Anúncio Aprovado!',
            'rejected' => 'Anúncio Rejeitado',
            'pending' => 'Anúncio em Análise',
            'pausado' => 'Anúncio Pausado'
        ];
        
        $defaultMessages = [
            'active' => 'Seu anúncio foi aprovado e está ativo!',
            'rejected' => 'Seu anúncio foi rejeitado. Verifique as diretrizes e tente novamente.',
            'pending' => 'Seu anúncio está em análise. Aguarde a aprovação.',
            'pausado' => 'Seu anúncio foi pausado.'
        ];
        
        $title = $titles[$status] ?? 'Status do Anúncio Atualizado';
        $msg = $message ?: ($defaultMessages[$status] ?? 'O status do seu anúncio foi atualizado.');
        
        return $this->createNotification(
            $userId,
            'anuncio_status',
            $title,
            $msg,
            [
                'status' => $status,
                'action' => 'view_anuncio'
            ]
        );
    }

    /**
     * Busca notificações de um usuário
     */
    public function getUserNotifications(int $userId, int $limit = 10): array
    {
        try {
            $query = "SELECT * FROM notificacoes 
                      WHERE user_id = :user_id 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdmsNotificacao::getUserNotifications - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca notificação como lida
     */
    public function markAsRead(int $notificationId): bool
    {
        try {
            $query = "UPDATE notificacoes SET is_read = 1, read_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AdmsNotificacao::markAsRead - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Conta notificações não lidas
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $query = "SELECT COUNT(*) FROM notificacoes 
                      WHERE user_id = :user_id AND is_read = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("AdmsNotificacao::getUnreadCount - Erro: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Envia notificação por email (se configurado)
     */
    public function sendEmailNotification(int $userId, string $subject, string $message): bool
    {
        // Implementar envio de email se necessário
        // Por enquanto, apenas log
        error_log("Email notification for user {$userId}: {$subject}");
        return true;
    }
}
