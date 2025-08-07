<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsLogin;
use Adms\Models\AdmsUser;

class Login
{
    private array $data = [];
    private array|null $formData = null;
    private AdmsLogin $admsLogin;
    private AdmsUser $admsUser;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->admsLogin = new AdmsLogin();
        $this->admsUser = new AdmsUser();
    }

    public function index(): void
    {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $this->redirecionarParaDashboard();
        }
        $this->carregarDadosView();
        $this->carregarViewLogin();
    }

    public function autenticar(): void
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Erro desconhecido.'];

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $filteredData = filter_input_array(INPUT_POST, [
            'login' => [
                'filter' => FILTER_DEFAULT,
                'flags' => FILTER_REQUIRE_ARRAY
            ]
        ]);

        if ($filteredData === false || $filteredData === null || !isset($filteredData['login']) || !is_array($filteredData['login'])) {
            $this->formData = [];
            $response['message'] = 'Nenhum dado de login recebido ou formato inválido.';
            echo json_encode($response);
            exit();
        } else {
            $this->formData = $filteredData['login'];
        }

        $email = $this->formData['email'] ?? '';
        $senha = $this->formData['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            $response['message'] = 'Preencha e-mail e senha!';
            echo json_encode($response);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'E-mail inválido!';
            echo json_encode($response);
            exit();
        }

        if (strlen($senha) < 6) {
            $response['message'] = 'Senha deve ter no mínimo 6 caracteres!';
            echo json_encode($response);
            exit();
        }

        try {
            $resultado = $this->admsLogin->verificarCredenciais($email, $senha);

            if ($resultado['success']) {
                $user = $resultado['user'];
                if (!empty($user['deleted_at'])) {
                    $response['message'] = 'Sua conta foi desativada. Por favor, entre em contato com o suporte.';
                    error_log("LOGIN REJEITADO: Usuário ID " . $user['id'] . " tentou logar, mas a conta está soft-deletada.");
                } else {
                    $this->criarSessaoUsuario($user); // Chama a função para criar a sessão
                    $this->admsUser->updateLastAccess($user['id']);
                    $response['success'] = true;
                    $response['message'] = 'Login realizado com sucesso! Bem-vindo(a), ' . $user['nome'] . '.';
                    $response['redirect'] = URLADM . "dashboard";
                }
            } else {
                $response['message'] = $resultado['message'] ?? 'Credenciais inválidas.';
            }
        } catch (\Exception $e) {
            error_log("Erro no processo de autenticação: " . $e->getMessage());
            $response['message'] = 'Erro inesperado no processo de login. Tente novamente.';
        }

        echo json_encode($response);
        exit();
    }

    private function carregarDadosView(): void
    {
        $this->data = [
            'title' => 'Login - Área Administrativa',
            'favicon' => URLADM . 'assets/images/favicon.ico',
            'form_email' => $_SESSION['form_email'] ?? '',
            'msg' => $_SESSION['msg'] ?? []
        ];
        unset($_SESSION['msg'], $_SESSION['form_email']);
    }

    private function carregarViewLogin(): void
    {
        $loadView = new ConfigViewAdm("adms/Views/login/login", $this->data);
        $loadView->loadViewLogin();
    }

    private function criarSessaoUsuario(array $usuario): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_level'] = $usuario['nivel_acesso'];

        // --- CORREÇÃO APLICADA AQUI ---
        $numericUserLevel = 0; // Valor padrão
        if ($usuario['nivel_acesso'] === 'administrador') {
            $numericUserLevel = 3; // Nível 3 para administradores (consistente com Dashboard e AdminUsersController)
        } elseif ($usuario['nivel_acesso'] === 'usuario') {
            $numericUserLevel = 1; // Nível 1 para usuários comuns (consistente com Dashboard controller)
        }
        $_SESSION['user_level_numeric'] = $numericUserLevel;
        // --- FIM DA CORREÇÃO ---

        // 'user_role' pode ser mantido para compatibilidade, mas user_level_numeric é o mais importante agora
        $_SESSION['user_role'] = ($usuario['nivel_acesso'] === 'administrador') ? 'admin' : 'normal';

        $_SESSION['user_name'] = $usuario['nome'];

        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'nivel_acesso' => $usuario['nivel_acesso'],
            'foto' => $usuario['foto'] ?? 'usuario.png',
            'ultimo_acesso' => date('Y-m-d H:i:s'),
            'nivel_acesso_numeric' => $_SESSION['user_level_numeric'] // Incluir também aqui para consistência
        ];

        error_log("DEBUG LOGIN: Sessão criada. user_id: " . ($_SESSION['user_id'] ?? 'N/A') . ", user_level: " . ($_SESSION['user_level'] ?? 'N/A') . ", user_role: " . ($_SESSION['user_role'] ?? 'N/A') . ", user_level_numeric: " . ($_SESSION['user_level_numeric'] ?? 'N/A'));
    }

    private function redirecionarParaDashboard(): void
    {
        header("Location: " . URLADM . "dashboard");
        exit();
    }
}