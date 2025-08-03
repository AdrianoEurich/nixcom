<?php
// Exemplo: app/adms/Controllers/Perfil.php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsPerfil; // Supondo que seu model de perfil esteja aqui

class Perfil
{
    private array $data = [];
    private array $userData;

    public function __construct()
    {
        // Garante que a sessão esteja iniciada antes de acessar $_SESSION
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->verifySession();
        // A partir daqui, $_SESSION['usuario'] certamente existe e é um array
        $this->userData = $_SESSION['usuario'];
    }

    private function verifySession(): void
    {
        if (!isset($_SESSION['usuario']['id'])) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
            $this->redirect(URLADM . "login");
        }
    }

    public function index(): void
    {
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

        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        $viewPath = 'adms/Views/perfil/perfil';

        $loadView = new ConfigViewAdm($viewPath, $this->data);
        if ($isAjaxRequest) {
            $loadView->loadContentView();
        } else {
            $loadView->loadView();
        }
    }

    private function redirect(string $url): void
    {
        header("Location: " . $url);
        exit();
    }

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
                // Atualiza APENAS o nome do arquivo na sessão, não o caminho completo
                $_SESSION['usuario']['foto'] = basename($result['new_photo_path']); 
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'] ?? 'Foto de perfil atualizada com sucesso!',
                    'new_photo_url' => URLADM . 'assets/images/users/' . basename($result['new_photo_path'])
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
     * Remove a foto de perfil do usuário.
     * Atualiza o campo 'foto' para 'usuario.png' no banco de dados e exclui o arquivo físico.
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

            // Não permitir remoção se a foto já é a padrão
            if ($currentFoto === 'usuario.png') {
                echo json_encode(['success' => false, 'message' => 'Não há foto de perfil para remover (já é a padrão).']);
                exit();
            }

            $modelPerfil = new AdmsPerfil();
            $uploadDir = 'assets/images/users/'; // O mesmo diretório usado para upload
            $result = $modelPerfil->removerFotoPerfil($userId, $currentFoto, $uploadDir);

            if ($result['success']) {
                // Atualiza a sessão para a foto padrão
                $_SESSION['usuario']['foto'] = 'usuario.png';
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'] ?? 'Foto de perfil removida com sucesso!',
                    'new_photo_url' => URLADM . 'assets/images/users/usuario.png' // URL da foto padrão
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
                $_SESSION['usuario']['nome'] = $novoNome;
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
}