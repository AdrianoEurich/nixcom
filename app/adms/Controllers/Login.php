<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsLogin;

class Login
{
    private array $data = [];
    private array|null $formData = null;
    private AdmsLogin $admsLogin;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->admsLogin = new AdmsLogin();
    }

    public function index(): void
    {
        $this->carregarDadosView();
        $this->carregarViewLogin();
    }

    public function autenticar(): void
    {
        // Define o cabeçalho para indicar que a resposta será JSON
        header('Content-Type: application/json');

        // Inicializa a resposta padrão como erro
        $response = ['success' => false, 'message' => 'Erro desconhecido.'];

        // Inicia a sessão se ainda não estiver iniciada (boa prática para usar $_POST)
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

        // Validação no backend
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
                $this->criarSessaoUsuario($resultado['user']);
                $response['success'] = true;
                $response['message'] = 'Login realizado com sucesso! Bem-vindo(a), ' . $resultado['user']['nome'] . '.';
                $response['redirect'] = URLADM . "dashboard"; // Informa ao JS para onde redirecionar
            } else {
                $response['message'] = $resultado['message'] ?? 'Credenciais inválidas.';
            }
        } catch (\Exception $e) {
            error_log("Erro no processo de autenticação: " . $e->getMessage());
            $response['message'] = 'Erro inesperado no processo de login. Tente novamente.';
        }

        echo json_encode($response);
        exit(); // Garante que nenhum HTML extra seja enviado
    }

    public function logout(): void
    {
        $this->destruirSessao();
        $this->redirecionarParaLogin();
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
        
        // Define user_id e user_level diretamente na sessão
        $_SESSION['user_id'] = $usuario['id']; 
        $_SESSION['user_level'] = $usuario['nivel_acesso']; // Esta linha é a adição principal!

        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'nivel_acesso' => $usuario['nivel_acesso'], // Mantém aqui para compatibilidade com outros lugares que possam ler $_SESSION['usuario']
            'foto' => $usuario['foto'] ?? 'usuario.png',
            'ultimo_acesso' => date('Y-m-d H:i:s')
        ];

        // Adiciona um log para confirmar o que foi armazenado
        error_log("DEBUG LOGIN: Sessão criada. user_id: " . ($_SESSION['user_id'] ?? 'N/A') . ", user_level: " . ($_SESSION['user_level'] ?? 'N/A'));
    }

    private function destruirSessao(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
    }

    private function redirecionarParaLogin(): void
    {
        $_SESSION['msg'] = ['type' => 'success', 'text' => 'Você foi desconectado com sucesso.'];
        header("Location: " . URLADM . "login");
        exit();
    }

    private function redirecionarParaDashboard(): void
    {
        header("Location: " . URLADM . "dashboard");
        exit();
    }
}
