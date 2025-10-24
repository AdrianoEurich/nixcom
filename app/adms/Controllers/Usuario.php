<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\Models\AdmsUser;

class Usuario
{
    /**
     * Construtor do controlador.
     * Garante que a sessão esteja iniciada e o usuário logado.
     * Permite apenas admins (nível 3) ou auto-deleção.
     */
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Apenas admins ou o próprio usuário podem acessar
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Faça login!'];
            header("Location: " . URLADM . "login/index");
            exit();
        }
    }

    /**
     * Helper para enviar respostas JSON e encerrar a execução.
     * @param array $response O array de dados a ser convertido para JSON.
     */
    private function sendJsonResponse(array $response): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Realiza o soft delete do usuário (admin ou auto-exclusão).
     * Espera uma requisição POST via AJAX.
     * Chamada via /adms/usuario/softDeleteUser
     */
    public function deleteAccount(): void
    {
        error_log("DEBUG CONTROLLER USUARIO: Método deleteAccount() chamado.");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Ajuste para receber user_id via JSON
            $data = json_decode(file_get_contents('php://input'), true);
            $userIdToDelete = $data['user_id'] ?? null;
            $userIdToDelete = filter_var($userIdToDelete, FILTER_VALIDATE_INT);
            
            // DEBUG: Log dos dados recebidos
            error_log("DEBUG CONTROLLER USUARIO: Dados recebidos: " . json_encode($data));
            error_log("DEBUG CONTROLLER USUARIO: userIdToDelete: " . $userIdToDelete);
            error_log("DEBUG CONTROLLER USUARIO: currentUserId: " . ($_SESSION['user_id'] ?? 'N/A'));
            error_log("DEBUG CONTROLLER USUARIO: isAdmin: " . (($_SESSION['user_level_name'] ?? 'usuario') === 'administrador' ? 'true' : 'false'));

            if (!$userIdToDelete) {
                $this->sendJsonResponse(['success' => false, 'message' => 'ID do usuário não fornecido para exclusão.']);
            }

            $admsUserModel = new AdmsUser();

            $currentUserId = $_SESSION['user_id'] ?? null;
            $isAdmin = ($_SESSION['user_level_name'] ?? 'usuario') === 'administrador';

            // DEBUG: Log da verificação de permissão
            error_log("DEBUG CONTROLLER USUARIO: Verificando permissão - isAdmin: " . ($isAdmin ? 'true' : 'false') . ", currentUserId: " . $currentUserId . ", userIdToDelete: " . $userIdToDelete);
            error_log("DEBUG CONTROLLER USUARIO: Condição (isAdmin || currentUserId == userIdToDelete): " . (($isAdmin || $currentUserId == $userIdToDelete) ? 'true' : 'false'));
            
            // Permite admin deletar qualquer usuário, ou o próprio usuário deletar a si mesmo
            if ($isAdmin || $currentUserId == $userIdToDelete) {
                error_log("DEBUG CONTROLLER USUARIO: Permissão concedida. Chamando softDeleteUser para userId: " . $userIdToDelete);
                
                if ($admsUserModel->softDeleteUser($userIdToDelete)) {
                    error_log("DEBUG CONTROLLER USUARIO: softDeleteUser executado com sucesso");
                    
                    // Limpa a sessão se o próprio usuário deletou a si mesmo
                    if ($currentUserId == $userIdToDelete) {
                        error_log("DEBUG CONTROLLER USUARIO: ATENÇÃO! Excluindo própria conta do admin! Destruindo sessão...");
                        session_destroy();
                    } else {
                        error_log("DEBUG CONTROLLER USUARIO: Excluindo conta de outro usuário. Sessão mantida.");
                    }

                    $redirectUrl = URLADM . "login";
                    $msg = $admsUserModel->getMsg()['text'] ?? 'Conta excluída/desativada com sucesso!';
                    $this->sendJsonResponse([
                        'success' => true,
                        'message' => $msg,
                        'redirect' => $redirectUrl
                    ]);
                } else {
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => $admsUserModel->getMsg()['text'] ?? 'Erro ao excluir/desativar a conta.'
                    ]);
                }
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado. Você não tem permissão para excluir esta conta.']);
            }
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método de requisição inválido.']);
        }
    }
}