<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Definir timezone para corresponder ao MySQL
date_default_timezone_set('America/Sao_Paulo');

use PDO;

class Notificacoes
{
    private $pdo;

    public function __construct()
    {
        try {
            $host = 'localhost';
            $dbname = 'nixcom';
            $username = 'root';
            $password = '';
            
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("ERRO CONEXÃO NOTIFICAÇÕES: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro de conexão']);
        }
    }

    /**
     * Busca notificações para o administrador
     */
    public function getNotificacoes(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        
        if ($userLevel < 3) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        try {
            $stmt = $this->pdo->query("
                SELECT 
                    id,
                    tipo,
                    titulo,
                    mensagem,
                    user_id,
                    anuncio_id,
                    comentario_id,
                    lida,
                    created_at
                FROM notificacoes 
                ORDER BY created_at DESC
                LIMIT 20
            ");
            
            $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendJsonResponse([
                'success' => true,
                'notificacoes' => $notificacoes
            ]);
            
        } catch (PDOException $e) {
            error_log("ERRO BUSCAR NOTIFICAÇÕES: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao buscar notificações']);
        }
    }

    /**
     * Conta notificações não lidas
     */
    public function getContador(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        
        if ($userLevel < 3) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        try {
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as total 
                FROM notificacoes 
                WHERE lida = 0
            ");
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->sendJsonResponse([
                'success' => true,
                'total' => $result['total']
            ]);
            
        } catch (PDOException $e) {
            error_log("ERRO CONTAR NOTIFICAÇÕES: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao contar notificações']);
        }
    }

    /**
     * Marca notificação como lida
     */
    public function marcarLida(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        
        if ($userLevel < 3) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID da notificação é obrigatório']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->sendJsonResponse(['success' => true, 'message' => 'Notificação marcada como lida']);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao marcar como lida']);
            }
            
        } catch (PDOException $e) {
            error_log("ERRO MARCAR NOTIFICAÇÃO: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao marcar notificação']);
        }
    }

    /**
     * Cria notificação
     */
    public function criarNotificacao($tipo, $titulo, $mensagem, $user_id = null, $anuncio_id = null, $comentario_id = null): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notificacoes 
                (tipo, titulo, mensagem, user_id, anuncio_id, comentario_id, lida) 
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            
            return $stmt->execute([$tipo, $titulo, $mensagem, $user_id, $anuncio_id, $comentario_id]);
            
        } catch (PDOException $e) {
            error_log("ERRO CRIAR NOTIFICAÇÃO: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia resposta JSON
     */
    private function sendJsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

