<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDO;
use PDOException;

class AdmsPaymentLog extends StsConn
{
    private object $conn;

    public function __construct()
    {
        $this->conn = $this->connectDb();
    }

    /**
     * Cria tabela de logs de pagamento se não existir
     */
    public function createTable(): bool
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS payment_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                payment_id VARCHAR(255) NOT NULL,
                subscription_id INT,
                action VARCHAR(100) NOT NULL,
                status VARCHAR(50) NOT NULL,
                amount DECIMAL(10,2),
                plan_type VARCHAR(50),
                payment_method VARCHAR(50),
                external_reference VARCHAR(255),
                mercado_pago_data TEXT,
                error_message TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_payment_id (payment_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->conn->exec($sql);
            return true;
        } catch (PDOException $e) {
            error_log("AdmsPaymentLog::createTable - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra log de pagamento
     */
    public function logPayment(array $data): bool
    {
        try {
            $sql = "INSERT INTO payment_logs (
                user_id, payment_id, subscription_id, action, status, amount, 
                plan_type, payment_method, external_reference, mercado_pago_data, 
                error_message, ip_address, user_agent
            ) VALUES (
                :user_id, :payment_id, :subscription_id, :action, :status, :amount,
                :plan_type, :payment_method, :external_reference, :mercado_pago_data,
                :error_message, :ip_address, :user_agent
            )";
            
            $stmt = $this->conn->prepare($sql);
            
            return $stmt->execute([
                'user_id' => $data['user_id'] ?? null,
                'payment_id' => $data['payment_id'] ?? null,
                'subscription_id' => $data['subscription_id'] ?? null,
                'action' => $data['action'] ?? 'unknown',
                'status' => $data['status'] ?? 'unknown',
                'amount' => $data['amount'] ?? null,
                'plan_type' => $data['plan_type'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'pix',
                'external_reference' => $data['external_reference'] ?? null,
                'mercado_pago_data' => isset($data['mercado_pago_data']) ? json_encode($data['mercado_pago_data']) : null,
                'error_message' => $data['error_message'] ?? null,
                'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("AdmsPaymentLog::logPayment - Erro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca logs de pagamento com filtros
     */
    public function getPaymentLogs(array $filters = []): array
    {
        try {
            $where = ['1=1'];
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $where[] = 'user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = 'status = :status';
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['plan_type'])) {
                $where[] = 'plan_type = :plan_type';
                $params['plan_type'] = $filters['plan_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = 'created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = 'created_at <= :date_to';
                $params['date_to'] = $filters['date_to'];
            }
            
            $limit = $filters['limit'] ?? 50;
            $offset = $filters['offset'] ?? 0;
            
            $sql = "SELECT * FROM payment_logs 
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdmsPaymentLog::getPaymentLogs - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém estatísticas de pagamento
     */
    public function getPaymentStats(array $filters = []): array
    {
        try {
            $where = ['1=1'];
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $where[] = 'created_at >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = 'created_at <= :date_to';
                $params['date_to'] = $filters['date_to'];
            }
            
            $sql = "SELECT 
                        COUNT(*) as total_payments,
                        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_payments,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
                        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_payments,
                        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_revenue,
                        AVG(CASE WHEN status = 'approved' THEN amount END) as avg_payment_value
                    FROM payment_logs 
                    WHERE " . implode(' AND ', $where);
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdmsPaymentLog::getPaymentStats - Erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpa logs antigos (manter apenas últimos 90 dias)
     */
    public function cleanOldLogs(): bool
    {
        try {
            $sql = "DELETE FROM payment_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AdmsPaymentLog::cleanOldLogs - Erro: " . $e->getMessage());
            return false;
        }
    }
}

