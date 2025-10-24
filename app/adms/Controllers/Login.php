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
            // Regenera ID da sessão para prevenir session fixation
            session_regenerate_id(true);
        }
        $this->admsLogin = new AdmsLogin();
        $this->admsUser = new AdmsUser();
    }

    public function index(): void
    {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $this->redirecionarParaDashboard();
        }
        
        // Verificar se existe cookie "lembrar-me"
        $this->verificarCookieLembrarMe();
        
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
        $lembrarMe = isset($this->formData['remember']) && $this->formData['remember'] === 'on';

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
                $this->criarSessaoUsuario($user); // Chama a função para criar a sessão
                $this->admsUser->updateLastAccess($user['id']);
                
                // Criar cookie "lembrar-me" se solicitado
                if ($lembrarMe) {
                    $this->criarCookieLembrarMe($user['id'], $user['email']);
                }
                
                $response['success'] = true;
                $response['message'] = 'Login realizado com sucesso! Bem-vindo(a), ' . $user['nome'] . '.';
                // Redirecionar baseado no nível de acesso
                $userLevel = $user['nivel_acesso'] ?? 'usuario';
                if ($userLevel === 'administrador') {
                    $response['redirect'] = URLADM . "admin-users";
                } else {
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
            $numericUserLevel = 3; // Nível 3 para administradores
        } elseif ($usuario['nivel_acesso'] === 'usuario' || $usuario['nivel_acesso'] === 'normal') {
            $numericUserLevel = 1; // Nível 1 para usuários comuns
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
            'nivel_acesso_numeric' => $_SESSION['user_level_numeric'], // Incluir também aqui para consistência
            'plan_type' => $usuario['plan_type'] ?? 'free',
            'payment_status' => $usuario['payment_status'] ?? 'pending'
        ];
        
        // Definir variáveis de sessão separadas para fácil acesso
        $_SESSION['user_plan'] = $usuario['plan_type'] ?? 'free';
        $_SESSION['payment_status'] = $usuario['payment_status'] ?? 'pending';

        // --- NOVO: Inicializar dados do anúncio na sessão ---
        $this->inicializarDadosAnuncioSessao($usuario['id']);

        error_log("DEBUG LOGIN: Sessão criada. user_id: " . ($_SESSION['user_id'] ?? 'N/A') . ", user_level: " . ($_SESSION['user_level'] ?? 'N/A') . ", user_role: " . ($_SESSION['user_role'] ?? 'N/A') . ", user_level_numeric: " . ($_SESSION['user_level_numeric'] ?? 'N/A') . ", user_plan: " . ($_SESSION['user_plan'] ?? 'N/A') . ", payment_status: " . ($_SESSION['payment_status'] ?? 'N/A'));
    }

    /**
     * Inicializa os dados do anúncio na sessão do usuário.
     * @param int $userId ID do usuário
     */
    private function inicializarDadosAnuncioSessao(int $userId): void
    {
        try {
            require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'AdmsAnuncio.php';
            $admsAnuncioModel = new \Adms\Models\AdmsAnuncio();
            
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            
            if ($existingAnuncio) {
                $_SESSION['has_anuncio'] = true;
                $_SESSION['anuncio_status'] = $existingAnuncio['status'];
                $_SESSION['anuncio_id'] = $existingAnuncio['id'];
                error_log("DEBUG LOGIN: Dados do anúncio inicializados na sessão - has_anuncio=true, status=" . $existingAnuncio['status'] . ", id=" . $existingAnuncio['id']);
            } else {
                $_SESSION['has_anuncio'] = false;
                $_SESSION['anuncio_status'] = 'not_found';
                $_SESSION['anuncio_id'] = null;
                error_log("DEBUG LOGIN: Dados do anúncio inicializados na sessão - has_anuncio=false, status=not_found, id=null");
            }
        } catch (Exception $e) {
            error_log("ERRO LOGIN: Erro ao inicializar dados do anúncio na sessão: " . $e->getMessage());
            // Define valores padrão em caso de erro
            $_SESSION['has_anuncio'] = false;
            $_SESSION['anuncio_status'] = 'not_found';
            $_SESSION['anuncio_id'] = null;
        }
    }

    private function redirecionarParaDashboard(): void
    {
        $userLevel = $_SESSION['user_level'] ?? 'usuario';
        
        if ($userLevel === 'administrador') {
            // Administrador vai para o painel administrativo de usuários
            header("Location: " . URLADM . "admin-users");
        } else {
            // Usuário comum vai para o dashboard de usuários
            header("Location: " . URLADM . "dashboard");
        }
        exit();
    }

    /**
     * Verifica se existe cookie "lembrar-me" e faz login automático
     */
    private function verificarCookieLembrarMe(): void
    {
        if (isset($_COOKIE['lembrar_usuario']) && isset($_COOKIE['lembrar_token'])) {
            $userId = $_COOKIE['lembrar_usuario'];
            $token = $_COOKIE['lembrar_token'];
            
            // Verificar se o token é válido
            if ($this->validarTokenLembrarMe($userId, $token)) {
                // Buscar dados do usuário
                $user = $this->admsUser->getUserById($userId);
                if ($user) {
                    $this->criarSessaoUsuario($user);
                    $this->admsUser->updateLastAccess($user['id']);
                    $this->redirecionarParaDashboard();
                }
            } else {
                // Token inválido, remover cookies
                $this->removerCookieLembrarMe();
            }
        }
    }

    /**
     * Cria cookie "lembrar-me" com token seguro
     */
    private function criarCookieLembrarMe(int $userId, string $email): void
    {
        $token = $this->gerarTokenSeguro();
        $expira = time() + (30 * 24 * 60 * 60); // 30 dias
        
        // Salvar token no banco de dados (você pode criar uma tabela para isso)
        $this->salvarTokenLembrarMe($userId, $token);
        
        // Criar cookies
        setcookie('lembrar_usuario', $userId, $expira, '/', '', false, true);
        setcookie('lembrar_token', $token, $expira, '/', '', false, true);
    }

    /**
     * Valida token "lembrar-me"
     */
    private function validarTokenLembrarMe(int $userId, string $token): bool
    {
        // Aqui você pode implementar validação no banco de dados
        // Por simplicidade, vamos usar uma validação básica
        $tokenEsperado = hash('sha256', $userId . $this->getSecretKey());
        return hash_equals($tokenEsperado, $token);
    }

    /**
     * Gera token seguro para "lembrar-me"
     */
    private function gerarTokenSeguro(): string
    {
        $userId = $this->formData['email'] ?? '';
        return hash('sha256', $userId . $this->getSecretKey() . time());
    }

    /**
     * Obtém chave secreta para tokens
     */
    private function getSecretKey(): string
    {
        return 'nixcom_lembrar_me_secret_key_2024';
    }

    /**
     * Salva token no banco de dados (implementação básica)
     */
    private function salvarTokenLembrarMe(int $userId, string $token): void
    {
        // Aqui você pode implementar salvamento no banco de dados
        // Por simplicidade, vamos usar arquivo temporário
        $tokensFile = __DIR__ . '/../../tokens_lembrar_me.json';
        $tokens = [];
        
        if (file_exists($tokensFile)) {
            $tokens = json_decode(file_get_contents($tokensFile), true) ?? [];
        }
        
        $tokens[$userId] = [
            'token' => $token,
            'created_at' => time(),
            'expires_at' => time() + (30 * 24 * 60 * 60)
        ];
        
        file_put_contents($tokensFile, json_encode($tokens));
    }

    /**
     * Remove cookies "lembrar-me"
     */
    private function removerCookieLembrarMe(): void
    {
        setcookie('lembrar_usuario', '', time() - 3600, '/', '', false, true);
        setcookie('lembrar_token', '', time() - 3600, '/', '', false, true);
    }
}