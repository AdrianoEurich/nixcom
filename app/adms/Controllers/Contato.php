<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Definir timezone para corresponder ao MySQL
date_default_timezone_set('America/Sao_Paulo');

use Adms\Models\AdmsContato;
use Adms\Models\AdmsMensagensDiretas;
use PDO;

class Contato
{
    private $pdo;

    public function __construct()
    {
        // Inicia a sessão se ainda não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Conexão direta com o banco para evitar problemas de constantes
        try {
            $this->pdo = new PDO('mysql:host=localhost;dbname=nixcom', 'root', '');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("ERRO CONEXÃO CONTATO: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
        }
    }

    /**
     * Envia mensagem direta do usuário para o administrador
     */
    public function enviarMensagemDireta(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não logado']);
            return;
        }

        $assunto = $_POST['assunto'] ?? '';
        $mensagem = $_POST['mensagem'] ?? '';

        if (empty($assunto) || empty($mensagem)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Assunto e mensagem são obrigatórios']);
            return;
        }

        try {
            // Buscar dados do usuário
            $stmt = $this->pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não encontrado']);
                return;
            }

            // Buscar ID do administrador
            $stmt = $this->pdo->query("SELECT id FROM usuarios WHERE nivel_acesso = 'administrador' LIMIT 1");
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            $adminId = $admin ? $admin['id'] : 1; // Fallback para ID 1 se não encontrar admin

            // Inserir mensagem direta
            $stmt = $this->pdo->prepare("
                INSERT INTO mensagens_diretas 
                (user_id, admin_id, assunto, mensagem, status, prioridade, created_at, lida) 
                VALUES (?, ?, ?, ?, 'pendente', 'normal', ?, 0)
            ");
            
            $dataAtual = date('Y-m-d H:i:s');
            $result = $stmt->execute([$userId, $adminId, $assunto, $mensagem, $dataAtual]);

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true, 
                    'message' => 'Mensagem enviada com sucesso! O administrador será notificado.'
                ]);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao enviar mensagem']);
            }

        } catch (PDOException $e) {
            error_log("ERRO ENVIAR MENSAGEM DIRETA: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Envia mensagem do formulário de contato público
     */
    public function enviarFormularioContato(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $assunto = $_POST['assunto'] ?? '';
        $mensagem = $_POST['mensagem'] ?? '';

        if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
            return;
        }

        try {
            // Inserir no formulário de contato
            $stmt = $this->pdo->prepare("
                INSERT INTO formulario_contato 
                (nomeCompleto, email, telefone, assunto, mensagem, dataCriacao, lida) 
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            
            $dataAtual = date('Y-m-d H:i:s');
            
            $result = $stmt->execute([$nome, $email, $telefone, $assunto, $mensagem, $dataAtual]);

            if ($result) {
                // Enviar email para o administrador
                $this->enviarEmailContato($nome, $email, $telefone, $assunto, $mensagem);
                
                $this->sendJsonResponse([
                    'success' => true, 
                    'message' => 'Mensagem enviada com sucesso! Entraremos em contato em breve.'
                ]);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao enviar mensagem']);
            }

        } catch (PDOException $e) {
            error_log("ERRO ENVIAR FORMULÁRIO CONTATO: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Busca mensagens para administradores e usuários
     */
    public function getMensagens(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não logado']);
            return;
        }

        try {
            if ($userLevel >= 3) {
                // ADMINISTRADOR: Vê todas as mensagens recebidas
                $stmt = $this->pdo->query("
                    SELECT 
                        md.id,
                        md.assunto,
                        md.mensagem,
                        md.resposta,
                        md.status,
                        md.created_at,
                        md.responded_at,
                        md.lida,
                        u.nome as user_name,
                        u.email as user_email,
                        'direta' as tipo
                    FROM mensagens_diretas md
                    JOIN usuarios u ON md.user_id = u.id
                    ORDER BY md.created_at DESC
                    LIMIT 20
                ");
                $mensagensDiretas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $this->pdo->query("
                    SELECT 
                        id,
                        assunto,
                        mensagem,
                        dataCriacao as created_at,
                        lida,
                        nomeCompleto as user_name,
                        email as user_email,
                        'contato' as tipo
                    FROM formulario_contato
                    ORDER BY dataCriacao DESC
                    LIMIT 20
                ");
                $formulariosContato = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Combinar mensagens e ordenar por data
                $todasMensagens = array_merge($mensagensDiretas, $formulariosContato);
                
                // Ordenar por data (mais recente primeiro)
                usort($todasMensagens, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            } else {
                // USUÁRIO NORMAL: Vê todas as suas mensagens (com e sem resposta)
                $stmt = $this->pdo->prepare("
                    SELECT 
                        md.id,
                        md.assunto,
                        md.mensagem,
                        md.resposta,
                        md.status,
                        md.created_at,
                        md.responded_at,
                        md.lida_pelo_usuario as lida,
                        u.nome as user_name,
                        u.email as user_email,
                        'direta' as tipo
                    FROM mensagens_diretas md
                    JOIN usuarios u ON md.user_id = u.id
                    WHERE md.user_id = ?
                    ORDER BY md.created_at DESC
                    LIMIT 20
                ");
                $stmt->execute([$userId]);
                $todasMensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->sendJsonResponse([
                'success' => true,
                'mensagens' => $todasMensagens,
                'user_type' => $userLevel >= 3 ? 'admin' : 'user'
            ]);

        } catch (PDOException $e) {
            error_log("ERRO BUSCAR MENSAGENS: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao buscar mensagens']);
        }
    }

    /**
     * Conta mensagens não lidas para administradores e usuários
     */
    public function getContador(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não logado']);
            return;
        }

        try {
            if ($userLevel >= 3) {
                // ADMINISTRADOR: Conta todas as mensagens não lidas
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM mensagens_diretas WHERE lida = 0");
                $mensagensDiretas = $stmt->fetch()['total'];

                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM formulario_contato WHERE lida = 0");
                $formulariosContato = $stmt->fetch()['total'];

                $total = $mensagensDiretas + $formulariosContato;
            } else {
                // USUÁRIO NORMAL: Conta mensagens com respostas não lidas
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM mensagens_diretas 
                    WHERE user_id = ? AND resposta IS NOT NULL AND lida_pelo_usuario = 0
                ");
                $stmt->execute([$userId]);
                $total = $stmt->fetch()['total'];
                $mensagensDiretas = $total;
                $formulariosContato = 0;
            }

            $this->sendJsonResponse([
                'success' => true,
                'total' => $total,
                'mensagens_diretas' => $mensagensDiretas,
                'formularios_contato' => $formulariosContato,
                'user_type' => $userLevel >= 3 ? 'admin' : 'user'
            ]);

        } catch (PDOException $e) {
            error_log("ERRO CONTAR MENSAGENS: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao contar mensagens']);
        }
    }

    /**
     * Marca mensagem como lida
     */
    public function marcarLida(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não logado']);
            return;
        }

        $id = $_POST['id'] ?? null;
        $tipo = $_POST['tipo'] ?? '';

        if (!$id || !$tipo) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID e tipo são obrigatórios']);
            return;
        }

        try {
            if ($userLevel >= 3) {
                // ADMINISTRADOR: Marca como lida pelo admin
                if ($tipo === 'direta') {
                    $stmt = $this->pdo->prepare("UPDATE mensagens_diretas SET lida = 1 WHERE id = ?");
                } else {
                    $stmt = $this->pdo->prepare("UPDATE formulario_contato SET lida = 1 WHERE id = ?");
                }
            } else {
                // USUÁRIO NORMAL: Marca como lida pelo usuário
                if ($tipo === 'direta') {
                    $stmt = $this->pdo->prepare("UPDATE mensagens_diretas SET lida_pelo_usuario = 1 WHERE id = ? AND user_id = ?");
                    $result = $stmt->execute([$id, $userId]);
                } else {
                    // Usuários normais não podem marcar formulários de contato como lidos
                    $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado']);
                    return;
                }
            }

            if (!isset($result)) {
                $result = $stmt->execute([$id]);
            }

            if ($result) {
                $this->sendJsonResponse(['success' => true, 'message' => 'Mensagem marcada como lida']);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao marcar como lida']);
            }

        } catch (PDOException $e) {
            error_log("ERRO MARCAR COMO LIDA: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Responde mensagem direta
     */
    public function responderMensagem(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        
        if ($userLevel < 3) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado - apenas administradores']);
            return;
        }

        $id = $_POST['id'] ?? null;
        $resposta = $_POST['resposta'] ?? '';

        if (!$id || empty($resposta)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID e resposta são obrigatórios']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE mensagens_diretas 
                SET resposta = ?, status = 'respondida', responded_at = NOW(), lida = 1, lida_pelo_usuario = 0 
                WHERE id = ?
            ");

            $result = $stmt->execute([$resposta, $id]);

            if ($result) {
                // Verificar se a atualização foi bem-sucedida
                $affectedRows = $stmt->rowCount();
                if ($affectedRows > 0) {
                    $this->sendJsonResponse(['success' => true, 'message' => 'O usuário será notificado da sua resposta.']);
                } else {
                    $this->sendJsonResponse(['success' => false, 'message' => 'Mensagem não encontrada ou já respondida']);
                }
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Erro ao enviar resposta']);
            }

        } catch (PDOException $e) {
            error_log("ERRO RESPONDER MENSAGEM: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Envia email de notificação para o administrador
     */
    private function enviarEmailContato($nome, $email, $telefone, $assunto, $mensagem): void
    {
        // Por enquanto, apenas log. Implementar PHPMailer depois
        error_log("EMAIL CONTATO: Nome: $nome, Email: $email, Assunto: $assunto");
    }

    /**
     * Marca todas as mensagens como lidas (função de correção)
     */
    public function marcarTodasComoLidas(): void
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        
        if ($userLevel < 3) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado - apenas administradores']);
            return;
        }

        try {
            // Marcar todas as mensagens diretas como lidas
            $stmt = $this->pdo->prepare("UPDATE mensagens_diretas SET lida = 1 WHERE lida = 0");
            $stmt->execute();

            // Marcar todos os formulários de contato como lidos
            $stmt = $this->pdo->prepare("UPDATE formulario_contato SET lida = 1 WHERE lida = 0");
            $stmt->execute();

            $this->sendJsonResponse([
                'success' => true, 
                'message' => 'Todas as mensagens foram marcadas como lidas'
            ]);

        } catch (PDOException $e) {
            error_log("ERRO MARCAR TODAS COMO LIDAS: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Envia resposta JSON
     */
    private function sendJsonResponse(array $response): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
}
