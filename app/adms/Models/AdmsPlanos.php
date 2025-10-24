<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDO;
use PDOException;

class AdmsPlanos extends StsConn
{
    private object $conn;

    public function __construct()
    {
        $this->conn = $this->connectDb();
    }

    /**
     * Busca todos os planos ativos
     */
    public function getAllPlans(): array
    {
        try {
            $query = "SELECT * FROM planos WHERE ativo = 1 ORDER BY 
                      CASE tipo 
                          WHEN 'free' THEN 1 
                          WHEN 'basic' THEN 2 
                          WHEN 'premium' THEN 3 
                      END";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsPlanos::getAllPlans: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca um plano específico por tipo
     */
    public function getPlanByType(string $tipo): ?array
    {
        try {
            $query = "SELECT * FROM planos WHERE tipo = :tipo AND ativo = 1 LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsPlanos::getPlanByType: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca um plano específico por ID
     */
    public function getPlanById(int $id): ?array
    {
        try {
            $query = "SELECT * FROM planos WHERE id = :id AND ativo = 1 LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsPlanos::getPlanById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ativa um plano para um usuário
     */
    public function activateUserPlan(int $userId, int $planoId, string $periodo = '6_meses'): bool
    {
        try {
            $this->conn->beginTransaction();

            // Buscar dados do plano
            $plano = $this->getPlanById($planoId);
            if (!$plano) {
                throw new Exception("Plano não encontrado");
            }

            // Calcular data de expiração
            $dataInicio = date('Y-m-d');
            $dataFim = $periodo === '12_meses' ? 
                date('Y-m-d', strtotime('+12 months')) : 
                date('Y-m-d', strtotime('+6 months'));

            // Calcular valor
            $valor = $periodo === '12_meses' ? $plano['preco_12_meses'] : $plano['preco_6_meses'];

            // Inserir histórico do plano
            $query = "INSERT INTO usuario_planos 
                      (usuario_id, plano_id, data_inicio, data_fim, valor_pago, status) 
                      VALUES (:usuario_id, :plano_id, :data_inicio, :data_fim, :valor_pago, 'ativo')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':plano_id', $planoId, PDO::PARAM_INT);
            $stmt->bindParam(':data_inicio', $dataInicio, PDO::PARAM_STR);
            $stmt->bindParam(':data_fim', $dataFim, PDO::PARAM_STR);
            $stmt->bindParam(':valor_pago', $valor, PDO::PARAM_STR);
            $stmt->execute();

            // Atualizar usuário
            $query = "UPDATE usuarios 
                      SET plan_type = :plan_type, plano_atual_id = :plano_id, plano_expira_em = :data_fim 
                      WHERE id = :usuario_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':plan_type', $plano['tipo'], PDO::PARAM_STR);
            $stmt->bindParam(':plano_id', $planoId, PDO::PARAM_INT);
            $stmt->bindParam(':data_fim', $dataFim, PDO::PARAM_STR);
            $stmt->bindParam(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("ERRO AdmsPlanos::activateUserPlan: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se o plano do usuário está ativo
     */
    public function isUserPlanActive(int $userId): bool
    {
        try {
            $query = "SELECT plano_expira_em FROM usuarios WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['plano_expira_em']) {
                return false; // Usuário sem plano pago
            }

            return strtotime($result['plano_expira_em']) > time();
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsPlanos::isUserPlanActive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca o plano atual do usuário
     */
    public function getUserCurrentPlan(int $userId): ?array
    {
        try {
            $query = "SELECT u.plan_type, u.plano_atual_id, u.plano_expira_em, p.* 
                      FROM usuarios u 
                      LEFT JOIN planos p ON u.plano_atual_id = p.id 
                      WHERE u.id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsPlanos::getUserCurrentPlan: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza planos expirados para 'free'
     */
    public function updateExpiredPlans(): int
    {
        try {
            $query = "UPDATE usuarios 
                      SET plan_type = 'free', plano_atual_id = NULL, plano_expira_em = NULL 
                      WHERE plano_expira_em < CURDATE() AND plan_type != 'free'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("ERRO PDO AdmsPlanos::updateExpiredPlans: " . $e->getMessage());
            return 0;
        }
    }
}
