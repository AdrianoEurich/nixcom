<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PDO;
use PDOException;

class AdmsSubscription
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
            error_log("AdmsSubscription::__construct - Erro de conexão: " . $e->getMessage());
            throw new \Exception("Erro de conexão com o banco de dados");
        }
    }

    /**
     * Cria uma nova assinatura
     */
    public function createSubscription(int $userId, int $planoId, string $period, float $amount, string $providerRef = null): ?int
    {
        try {
            error_log("AdmsSubscription::createSubscription - Iniciando criação: user=$userId, plano=$planoId, period=$period, amount=$amount");
            
            $query = "INSERT INTO subscriptions (user_id, plano_id, status, provider, provider_ref, amount, period, created_at) 
                      VALUES (:user_id, :plano_id, 'pending', 'mercadopago', :provider_ref, :amount, :period, NOW())";
            
            error_log("AdmsSubscription::createSubscription - Query: $query");
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':plano_id', $planoId, PDO::PARAM_INT);
            $stmt->bindParam(':provider_ref', $providerRef, PDO::PARAM_STR);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':period', $period, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            error_log("AdmsSubscription::createSubscription - Execute result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                $lastId = $this->conn->lastInsertId();
                error_log("AdmsSubscription::createSubscription - Last insert ID: $lastId");
                return $lastId;
            }
            
            error_log("AdmsSubscription::createSubscription - Execute falhou");
            return null;
        } catch (PDOException $e) {
            error_log("AdmsSubscription::createSubscription - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza uma assinatura
     */
    public function updateSubscription(int $subscriptionId, array $data): bool
    {
        try {
            $fields = [];
            foreach ($data as $key => $value) {
                $fields[] = "{$key} = :{$key}";
            }
            
            $query = "UPDATE subscriptions SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $subscriptionId, PDO::PARAM_INT);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AdmsSubscription::updateSubscription - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca assinatura por ID
     */
    public function getSubscriptionById(int $subscriptionId): ?array
    {
        try {
            $query = "SELECT s.*, p.nome as plano_nome, p.tipo as plano_tipo, u.nome as user_name, u.email as user_email
                      FROM subscriptions s
                      JOIN planos p ON s.plano_id = p.id
                      JOIN usuarios u ON s.user_id = u.id
                      WHERE s.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $subscriptionId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdmsSubscription::getSubscriptionById - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca assinaturas de um usuário
     */
    public function getUserSubscriptions(int $userId): array
    {
        try {
            $query = "SELECT s.*, p.nome as plano_nome, p.tipo as plano_tipo
                      FROM subscriptions s
                      JOIN planos p ON s.plano_id = p.id
                      WHERE s.user_id = :user_id
                      ORDER BY s.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdmsSubscription::getUserSubscriptions - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca assinatura ativa de um usuário
     */
    public function getActiveSubscription(int $userId): ?array
    {
        try {
            // Verificar se a tabela subscriptions existe
            $checkTable = "SHOW TABLES LIKE 'subscriptions'";
            $stmt = $this->conn->prepare($checkTable);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                error_log("AdmsSubscription::getActiveSubscription - Tabela subscriptions não existe");
                return null;
            }
            
            $query = "SELECT s.*, p.nome as plano_nome, p.tipo as plano_tipo
                      FROM subscriptions s
                      JOIN planos p ON s.plano_id = p.id
                      WHERE s.user_id = :user_id 
                      AND s.status = 'active' 
                      AND s.expires_at > NOW()
                      ORDER BY s.created_at DESC
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Garantir que retorna array ou null
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("AdmsSubscription::getActiveSubscription - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Aprova uma assinatura (admin)
     */
    public function approveSubscription(int $subscriptionId): bool
    {
        try {
            $this->conn->beginTransaction();
            
            // Buscar dados da assinatura
            $subscription = $this->getSubscriptionById($subscriptionId);
            if (!$subscription) {
                throw new \Exception("Assinatura não encontrada");
            }
            
            // Calcular data de expiração (6 meses)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+6 months'));
            
            // Atualizar assinatura
            $query = "UPDATE subscriptions 
                      SET status = 'active', starts_at = NOW(), expires_at = :expires_at, paid_at = NOW(), updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);
            $stmt->bindParam(':id', $subscriptionId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Atualizar usuário (inclui plan_type conforme tipo do plano aprovado)
            // DB enum para payment_status: 'pending'|'paid'|'failed'
            $query = "UPDATE usuarios 
                      SET plano_atual_id = :plano_id, plano_expira_em = :expires_at, payment_status = 'paid', can_create_ads = 1, plan_type = :plan_type
                      WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':plano_id', $subscription['plano_id'], PDO::PARAM_INT);
            $stmt->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);
            $stmt->bindParam(':plan_type', $subscription['plano_tipo'], PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $subscription['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->conn->rollBack();
            error_log("AdmsSubscription::approveSubscription - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Suspende assinaturas expiradas
     */
    public function suspendExpiredSubscriptions(): int
    {
        try {
            $query = "UPDATE subscriptions 
                      SET status = 'suspended', updated_at = NOW()
                      WHERE status = 'active' 
                      AND expires_at < NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $affectedRows = $stmt->rowCount();
            
            // Atualizar usuários com assinaturas suspensas
            $query = "UPDATE usuarios u
                      JOIN subscriptions s ON u.id = s.user_id
                      SET u.can_create_ads = 0, u.payment_status = 'pending'
                      WHERE s.status = 'suspended'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $affectedRows;
        } catch (PDOException $e) {
            error_log("AdmsSubscription::suspendExpiredSubscriptions - Erro: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Busca assinaturas aguardando aprovação
     */
    public function getPendingApprovalSubscriptions(): array
    {
        try {
            $query = "SELECT s.*, p.nome as plano_nome, u.nome as user_name, u.email as user_email
                      FROM subscriptions s
                      JOIN planos p ON s.plano_id = p.id
                      JOIN usuarios u ON s.user_id = u.id
                      WHERE s.status = 'paid_awaiting_admin'
                      ORDER BY s.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdmsSubscription::getPendingApprovalSubscriptions - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Atualiza QR Code da assinatura
     */
    public function updateQRCode(int $subscriptionId, string $qrCodeBase64, string $qrText): bool
    {
        try {
            $query = "UPDATE subscriptions 
                      SET qr_code_base64 = :qr_code_base64, qr_text = :qr_text, updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':qr_code_base64', $qrCodeBase64, PDO::PARAM_STR);
            $stmt->bindParam(':qr_text', $qrText, PDO::PARAM_STR);
            $stmt->bindParam(':id', $subscriptionId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AdmsSubscription::updateQRCode - Erro: " . $e->getMessage());
            return false;
        }
    }
}
