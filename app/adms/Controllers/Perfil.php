<?php
// app/adms/Controllers/Perfil.php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsPerfil;
use Adms\Models\AdmsUser;

class Perfil
{
    /** @var array $data Recebe os dados a serem enviados para a view. */
    private array $data = [];
    /** @var array $userData Dados do usuário logado, obtidos da sessão. */
    private array $userData;

    public function __construct()
    {
        // Garante que a sessão esteja iniciada antes de acessar $_SESSION
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->verifySession();
        // Os dados do usuário são configurados no verifySession()
    }

    /**
     * Verifica se o usuário está autenticado.
     * @return void
     */
    private function verifySession(): void
    {
        // Verificar se o usuário está logado usando as variáveis de sessão corretas
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario']['id'])) {
            $_SESSION['msg'] = [
                'type' => 'danger',
                'text' => 'Acesso negado. Faça login para continuar.'
            ];
            $this->redirect(URLADM . "login");
        }
        
        // Configurar dados do usuário baseado na estrutura de sessão disponível
        if (isset($_SESSION['user_id'])) {
            // Nova estrutura de sessão (do sistema de cadastro)
            $this->userData = [
                'id' => $_SESSION['user_id'],
                'nome' => $_SESSION['user_name'] ?? 'Usuário',
                'email' => $_SESSION['user_email'] ?? $_SESSION['usuario']['email'] ?? '',
                'nivel_acesso' => $_SESSION['user_role'] ?? 'usuario',
                'foto' => $_SESSION['user_photo_path'] ?? 'usuario.png'
            ];
        } else {
            // Estrutura antiga de sessão
            $this->userData = $_SESSION['usuario'];
        }
    }

    /**
     * Redireciona o usuário para uma URL específica.
     * @param string $url A URL de destino.
     * @return void
     */
    private function redirect(string $url): void
    {
        header("Location: " . $url);
        exit();
    }

    /**
     * Carrega a página de perfil do usuário.
     * @return void
     */
    public function index(): void
    {
        // Atualizar os dados do usuário com o que está no banco (evita depender de sessão desatualizada)
        try {
            $userId = $this->userData['id'] ?? ($_SESSION['user_id'] ?? null);
            if ($userId) {
                $userModel = new AdmsUser();
                $fresh = $userModel->getUserById((int)$userId);
                if ($fresh) {
                    // Sincroniza sessão (mantém compatibilidade com chaves usadas no layout/topbar)
                    if (isset($fresh['nome'])) { $_SESSION['user_name'] = $fresh['nome']; $this->userData['nome'] = $fresh['nome']; }
                    if (isset($fresh['email'])) { $_SESSION['user_email'] = $fresh['email']; $this->userData['email'] = $fresh['email']; }
                    if (isset($fresh['nivel_acesso'])) { $_SESSION['user_role'] = $fresh['nivel_acesso']; $this->userData['nivel_acesso'] = $fresh['nivel_acesso']; }
                    if (isset($fresh['foto'])) { $_SESSION['user_photo_path'] = $fresh['foto']; $this->userData['foto'] = $fresh['foto']; }
                    if (isset($fresh['status'])) { $_SESSION['user_status'] = $fresh['status']; }
                    if (isset($fresh['plan_type'])) { $_SESSION['user_plan'] = $fresh['plan_type']; }
                    if (isset($fresh['payment_status'])) { $_SESSION['payment_status'] = $fresh['payment_status']; }
                }
            }
        } catch (\Exception $e) {
            // Se falhar, continua com dados de sessão
        }

        $this->data = [
            'user_data' => $this->userData,
            'sidebar_active' => 'perfil',
            'msg' => $_SESSION['msg'] ?? [],
            'user_profile_data' => [
                'name' => $this->userData['nome'] ?? 'Nome Usuário',
                'email' => $this->userData['email'] ?? 'email@example.com',
                'role' => $this->userData['nivel_acesso'] ?? 'Usuário',
                'last_login' => $this->userData['ultimo_acesso'] ?? 'N/A',
                'avatar_url' => URLADM . 'assets/images/users/' . ($this->userData['foto'] ?? 'default.png')
            ]
        ];
        unset($_SESSION['msg']);

        $isAjaxRequest = (
            isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        $viewPath = 'adms/Views/perfil/perfil';
        $loadView = new ConfigViewAdm($viewPath, $this->data);

        if ($isAjaxRequest) {
            $loadView->loadContentView();
        } else {
            $loadView->loadView();
        }
    }
    
    /**
     * Processa a requisição AJAX para atualizar a foto de perfil.
     * @return void
     */
    public function atualizarFoto(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
            $userId = $this->userData['id'] ?? null;

            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'ID do usuário não encontrado na sessão.']);
                exit();
            }

            $modelPerfil = new AdmsPerfil();
            $uploadDir = 'assets/images/users/';
            $result = $modelPerfil->processarUploadFoto($_FILES['foto_perfil'], $userId, $uploadDir);

            if ($result['success']) {
                $newPhotoName = basename($result['new_photo_path']);
                // Atualizar ambas as estruturas de sessão para compatibilidade
                $_SESSION['usuario']['foto'] = $newPhotoName;
                $_SESSION['user_photo_path'] = $newPhotoName;
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'] ?? 'Foto de perfil atualizada com sucesso!',
                    'new_photo_url' => URLADM . 'assets/images/users/' . $newPhotoName
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao atualizar a foto de perfil.'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida ou nenhum arquivo de foto enviado.']);
        }
        exit();
    }

    /**
     * Processa a requisição AJAX para remover a foto de perfil.
     * @return void
     */
    public function removerFoto(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $this->userData['id'] ?? null;
            $currentFoto = $this->userData['foto'] ?? 'usuario.png';


            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'ID do usuário não encontrado na sessão.']);
                exit();
            }

            if ($currentFoto === 'usuario.png') {
                echo json_encode(['success' => false, 'message' => 'Não há foto de perfil para remover (já é a padrão).']);
                exit();
            }

            $modelPerfil = new AdmsPerfil();
            $uploadDir = 'assets/images/users/';
            $result = $modelPerfil->removerFotoPerfil($userId, $currentFoto, $uploadDir);

            if ($result['success']) {
                // Atualizar ambas as estruturas de sessão para compatibilidade
                $_SESSION['usuario']['foto'] = 'usuario.png';
                $_SESSION['user_photo_path'] = 'usuario.png';
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'] ?? 'Foto de perfil removida com sucesso!',
                    'new_photo_url' => URLADM . 'assets/images/users/usuario.png'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao remover a foto de perfil.'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
        }
        exit();
    }

    /**
     * Processa a requisição AJAX para atualizar o nome do usuário.
     * @return void
     */
    public function atualizarNome(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
            $novoNome = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
            $userId = $this->userData['id'] ?? null;

            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'ID do usuário não encontrado na sessão.']);
                exit();
            }

            if (empty($novoNome) || strlen($novoNome) < 3) {
                echo json_encode(['success' => false, 'message' => 'O nome deve ter pelo menos 3 caracteres.']);
                exit();
            }

            $modelPerfil = new AdmsPerfil();
            $result = $modelPerfil->atualizarNome($userId, $novoNome);

            if ($result['success']) {
                // Atualizar ambas as estruturas de sessão para compatibilidade
                $_SESSION['usuario']['nome'] = $novoNome;
                $_SESSION['user_name'] = $novoNome;
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'] ?? 'Nome atualizado com sucesso!',
                    'changed' => $result['changed']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao atualizar o nome.'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida ou nome não fornecido.']);
        }
        exit();
    }

    /**
     * Processa a requisição AJAX para atualizar a senha do usuário.
     * @return void
     */
    public function atualizarSenha(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $senhaAtual = filter_input(INPUT_POST, 'senha_atual', FILTER_DEFAULT);
            $novaSenha = filter_input(INPUT_POST, 'nova_senha', FILTER_DEFAULT);
            $confirmaSenha = filter_input(INPUT_POST, 'confirma_senha', FILTER_DEFAULT);
            $userId = $this->userData['id'] ?? null;

            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'ID do usuário não encontrado na sessão.']);
                exit();
            }

            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmaSenha)) {
                echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios!']);
                exit();
            }

            if ($novaSenha !== $confirmaSenha) {
                echo json_encode(['success' => false, 'message' => 'As novas senhas não coincidem!']);
                exit();
            }

            if (strlen($novaSenha) < 6) {
                echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres!']);
                exit();
            }

            $modelPerfil = new AdmsPerfil();
            $result = $modelPerfil->atualizarSenha($userId, $senhaAtual, $novaSenha);

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => $result['message'] ?? 'Senha atualizada com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Erro ao atualizar a senha.']);
            }
        }
        exit();
    }

    /**
     * Recebe a requisição AJAX para exclusão de conta.
     * Realiza o soft-delete do usuário e de seus anúncios, e destrói a sessão.
     * @return void
     */
    public function deleteAccount(): void
    {
        header('Content-Type: application/json');

        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST'
            || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
        ) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
            exit();
        }

        // CORREÇÃO: Verificar se é uma ação administrativa (admin excluindo conta de outro usuário)
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $isAdminAction = isset($data['admin_action']) && $data['admin_action'] === true;
        $targetUserId = $data['user_id'] ?? null;
        
        // Se for ação administrativa, usar o user_id enviado; senão, usar o ID do usuário logado
        $userId = $isAdminAction ? $targetUserId : ($this->userData['id'] ?? null);

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'ID do usuário não encontrado.']);
            exit();
        }

        // Verificar se o usuário tem permissão para excluir a conta
        $currentUserId = $this->userData['id'] ?? null;
        $isAdmin = ($_SESSION['user_level_numeric'] ?? 0) >= 3;
        
        // Permite admin excluir qualquer conta, ou o próprio usuário excluir sua conta
        if (!$isAdmin && $currentUserId != $userId) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não tem permissão para excluir esta conta.']);
            exit();
        }

        $modelPerfil = new AdmsPerfil();
        $deleteResult = $modelPerfil->deleteUserAndAnuncios($userId);

        if ($deleteResult['success']) {
            // Se o usuário logado excluiu sua própria conta, destruir a sessão e direcionar para a home pública (STS)
            if ($currentUserId == $userId) {
                session_destroy();
                echo json_encode([
                    'success' => true,
                    'message' => 'Sua conta foi excluída com sucesso.',
                    'redirect_url' => (defined('URL') ? URL : '/')
                ]);
                exit();
            }
            // Caso seja ação administrativa, manter admin logado e redirecionar para dashboard
            echo json_encode([
                'success' => true,
                'message' => 'Conta do usuário excluída com sucesso.',
                'redirect_url' => (defined('URLADM') ? URLADM . 'dashboard' : '/adms/dashboard')
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => $deleteResult['message'] ?? 'Não foi possível desativar a conta.']);
            exit();
        }
    }

    /**
     * Retorna informações atualizadas do usuário logado (para atualizar Topbar na SPA)
     * @return void
     */
    public function getUserInfo(): void
    {
        header('Content-Type: application/json');
        try {
            $userId = $this->userData['id'] ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
                exit();
            }
            $model = new AdmsUser();
            $fresh = $model->getUserById((int)$userId);
            if (!$fresh) {
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                exit();
            }
            // Sincroniza sessão rapidamente
            $_SESSION['user_name'] = $fresh['nome'] ?? ($_SESSION['user_name'] ?? null);
            $_SESSION['user_email'] = $fresh['email'] ?? ($_SESSION['user_email'] ?? null);
            $_SESSION['user_role'] = $fresh['nivel_acesso'] ?? ($_SESSION['user_role'] ?? null);
            $_SESSION['user_status'] = $fresh['status'] ?? ($_SESSION['user_status'] ?? null);
            $_SESSION['user_plan'] = $fresh['plan_type'] ?? ($_SESSION['user_plan'] ?? null);
            $_SESSION['payment_status'] = $fresh['payment_status'] ?? ($_SESSION['payment_status'] ?? null);
            $_SESSION['user_photo_path'] = $fresh['foto'] ?? ($_SESSION['user_photo_path'] ?? null);

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => (int)$userId,
                    'nome' => $fresh['nome'] ?? '',
                    'email' => $fresh['email'] ?? '',
                    'foto' => $fresh['foto'] ?? 'usuario.png',
                    'plan_type' => $fresh['plan_type'] ?? 'free',
                    'payment_status' => $fresh['payment_status'] ?? 'pending'
                ]
            ]);
            exit();
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao obter informações do usuário']);
            exit();
        }
    }
}
