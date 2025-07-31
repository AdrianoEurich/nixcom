<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigAdm; 

class Logout extends ConfigAdm
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->config(); 
    }

    public function index(): void
    {
        error_log("DEBUG LOGOUT: M\xc3\xa9todo index() chamado. Iniciando processo de logout.");
        $this->destruirSessao();
        $this->redirecionarParaLogin(); 
    }

    private function destruirSessao(): void
    {
        $_SESSION = []; 
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy(); 
        // NOVO: Regenera o ID da sessão para invalidar completamente o antigo
        session_regenerate_id(true); 
        error_log("DEBUG LOGOUT: Sess\xc3\xa3o destru\xc3\xadda e cookies de sess\xc3\xa3o limpos. ID de sess\xc3\xa3o regenerado.");
    }

    private function redirecionarParaLogin(): void
    {
        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Você foi desconectado com sucesso.'];
        header("Location: " . URLADM . "login");
        exit();
    }
}
