<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Usar a classe de conexão existente do módulo Sts
use Sts\Models\Helper\StsConn; 
use PDOException;

// AdmsUser agora estende a classe StsConn
class AdmsUser extends StsConn
{
    private object $conn; // Adicionado para armazenar a conexão PDO

    public function __construct()
    {
        // Chama o método connectDb da classe pai (StsConn) para obter a conexão
        $this->conn = $this->connectDb();
    }

    /**
     * Obtém o tipo de plano de um usuário pelo ID.
     * @param int $userId ID do usuário.
     * @return string Retorna 'premium' ou 'free'. Padrão 'free' se não encontrado.
     */
    public function getUserPlanType(int $userId): string
    {
        try {
            $query = "SELECT plan_type FROM usuarios WHERE id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result && isset($result['plan_type'])) {
                return $result['plan_type'];
            }
        } catch (PDOException $e) {
            error_log("ERRO PDO ao buscar plano do usuário: " . $e->getMessage());
            // Em caso de erro, retorna 'free' para não bloquear o usuário
        }
        return 'free'; // Padrão seguro se o usuário não for encontrado ou houver erro
    }

    // Outros métodos relacionados a usuários podem vir aqui (ex: login, cadastro, update, etc.)
}
