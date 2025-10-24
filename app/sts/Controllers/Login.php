<?php

namespace Sts\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm; 
use Adms\CoreAdm\ConfigAdm; 

class Login extends ConfigAdm
{
    private array $data = [];

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->config(); 
    }

    public function index(): void
    {
        error_log("DEBUG STS LOGIN: Método index() chamado.");

        // Se o usuário já estiver logado (verificando user_id da sessão), redireciona para o dashboard ADM
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            error_log("DEBUG STS LOGIN: Usuário ID " . $_SESSION['user_id'] . " já logado. Redirecionando para dashboard ADM.");
            header("Location: " . URLADM . "dashboard");
            exit();
        }

        $this->data = [
            'title' => 'Login - ' . SITE_NAME, 
            'favicon' => URLADM . 'assets/images/favicon.ico', 
            'form_email' => $_SESSION['form_email'] ?? '',
            'msg' => $_SESSION['msg'] ?? []
        ];
        unset($_SESSION['msg'], $_SESSION['form_email']);

        $loadView = new ConfigViewAdm("adms/Views/login/login", $this->data); 
        $loadView->loadViewLogin();
    }

    public function autenticar(): void
    {
        // Redireciona para o controller de login da área administrativa
        header("Location: " . URLADM . "login/autenticar");
        exit();
    }
}
