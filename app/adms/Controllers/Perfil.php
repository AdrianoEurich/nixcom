<?php
// Exemplo: app/adms/Controllers/Perfil.php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: P\xc3\xa1gina n\xc3\xa3o encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsPerfil; // Supondo que seu model de perfil esteja aqui

class Perfil
{
    private array $data = [];
    private array $userData;
    private AdmsPerfil $admsPerfil; // Instância do modelo AdmsPerfil

    public function __construct()
    {
        // Garante que a sessão esteja iniciada antes de acessar $_SESSION
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // LOG DE DEBUG: Verifica o valor da sessão 'usuario' no construtor
        error_log("DEBUG PERFIL CONTROLLER CONSTRUCT: \$_SESSION['usuario']['nome'] no in\xc3\xadcio: " . ($_SESSION['usuario']['nome'] ?? 'N\xc3\xa3o Definido'));
        error_log("DEBUG PERFIL CONTROLLER CONSTRUCT: \$_SESSION['usuario']['foto'] no in\xc3\xadcio: " . ($_SESSION['usuario']['foto'] ?? 'N\xc3\xa3o Definido'));
        error_log("DEBUG PERFIL CONTROLLER CONSTRUCT: \$_SESSION['user_name'] no in\xc3\xadcio: " . ($_SESSION['user_name'] ?? 'N\xc3\xa3o Definido'));
        error_log("DEBUG PERFIL CONTROLLER CONSTRUCT: \$_SESSION['user_photo_path'] no in\xc3\xadcio: " . ($_SESSION['user_photo_path'] ?? 'N\xc3\xa3o Definido'));


        $this->verifySession();
        // A partir daqui, $_SESSION['usuario'] certamente existe e é um array
        $this->userData = $_SESSION['usuario'];
        $this->admsPerfil = new AdmsPerfil(); // Inicializa o modelo AdmsPerfil
    }

    private function verifySession(): void
    {
        if (!isset($_SESSION['user_id'])) { // Usar 'user_id' para consistência
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Fa\xc3\xa7a login para continuar.'];
            $this->redirect(URLADM . "login");
        }
    }

    public function index(): void
    {
        // LOG DE DEBUG: Verifica o valor de $this->userData antes de passar para a view
        error_log("DEBUG PERFIL CONTROLLER INDEX: \$this->userData['nome'] antes da view: " . ($this->userData['nome'] ?? 'N\xc3\xa3o Definido'));
        error_log("DEBUG PERFIL CONTROLLER INDEX: \$this->userData['foto'] antes da view: " . ($this->userData['foto'] ?? 'N\xc3\xa3o Definido'));
        error_log("DEBUG PERFIL CONTROLLER INDEX: \$_SESSION['user_name'] antes da view: " . ($_SESSION['user_name'] ?? 'N\xc3\xa3o Definido'));
        error_log("DEBUG PERFIL CONTROLLER INDEX: \$_SESSION['user_photo_path'] antes da view: " . ($_SESSION['user_photo_path'] ?? 'N\xc3\xa3o Definido'));


        $this->data = [
            'user_data' => $this->userData, // <--- Este é o array que popula a view
            'sidebar_active' => 'perfil',
            'msg' => $_SESSION['msg'] ?? [],
            'user_profile_data' => [
                'name' => $this->userData['nome'] ?? 'Nome Usu\xc3\xa1rio',
                'role' => $this->userData['nivel_acesso'] ?? 'Usu\xc3\xa1rio', 
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
        ob_clean(); 
        header('Content-Type: application/json');

        error_log("DEBUG PERFIL CONTROLLER: Chegou em atualizarFoto. _FILES: " . print_r($_FILES, true));
        error_log("DEBUG PERFIL CONTROLLER: _POST em atualizarFoto: " . print_r($_POST, true));


        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
            $userId = $this->userData['id'] ?? null;

            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'ID do usu\xc3\xa1rio n\xc3\xa3o encontrado na sess\xc3\xa3o.']);
                exit();
            }

            $uploadDir = 'assets/images/users/'; 
            $result = $this->admsPerfil->processarUploadFoto($_FILES['foto_perfil'], $userId, $uploadDir);

            if ($result['success']) {
                // Atualiza APENAS o nome do arquivo na sess\xc3\xa3o 'usuario'
                $_SESSION['usuario']['foto'] = basename($result['new_photo_path']); 
                
                // *** NOVO: Sincroniza com a chave que main.php espera ***
                $_SESSION['user_photo_path'] = basename($result['new_photo_path']); 

                error_log("DEBUG PERFIL CONTROLLER ATUALIZARFOTO: \$_SESSION['usuario']['foto'] AP\xc3\x93S atualiza\xc3\xa7\xc3\xa3o: " . ($_SESSION['usuario']['foto'] ?? 'N\xc3\xa3o Definido'));
                error_log("DEBUG PERFIL CONTROLLER ATUALIZARFOTO: \$_SESSION['user_photo_path'] AP\xc3\x93S atualiza\xc3\xa7\xc3\xa3o: " . ($_SESSION['user_photo_path'] ?? 'N\xc3\xa3o Definido'));


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
            echo json_encode(['success' => false, 'message' => 'Requisi\xc3\xa7\xc3\xa3o inv\xc3\xa1lida ou nenhum arquivo de foto enviado.']);
        }
        exit();
    }

    public function atualizarNome(): void
    {
        ob_clean(); 
        header('Content-Type: application/json');

        error_log("DEBUG PERFIL CONTROLLER: Chegou em atualizarNome. REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("DEBUG PERFIL CONTROLLER: _POST em atualizarNome: " . print_r($_POST, true));


        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
            $novoNome = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
            $userId = $this->userData['id'] ?? null;

            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'ID do usu\xc3\xa1rio n\xc3\xa3o encontrado na sess\xc3\xa3o.']);
                exit();
            }

            if (empty($novoNome) || strlen($novoNome) < 3) {
                echo json_encode(['success' => false, 'message' => 'O nome deve ter pelo menos 3 caracteres.']);
                exit();
            }

            $result = $this->admsPerfil->atualizarNome($userId, $novoNome);

            if ($result['success']) {
                // Atualiza a sess\xc3\xa3o 'usuario' com o novo nome
                $_SESSION['usuario']['nome'] = $novoNome;
                
                // *** NOVO: Sincroniza com a chave que main.php espera ***
                $_SESSION['user_name'] = $novoNome; 

                error_log("DEBUG PERFIL CONTROLLER ATUALIZAR_NOME: \$_SESSION['usuario']['nome'] AP\xc3\x93S atualiza\xc3\xa7\xc3\xa3o: " . ($_SESSION['usuario']['nome'] ?? 'N\xc3\xa3o Definido'));
                error_log("DEBUG PERFIL CONTROLLER ATUALIZAR_NOME: \$_SESSION['user_name'] AP\xc3\x93S atualiza\xc3\xa7\xc3\xa3o: " . ($_SESSION['user_name'] ?? 'N\xc3\xa3o Definido'));


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
            echo json_encode(['success' => false, 'message' => 'Requisi\xc3\xa7\xc3\xa3o inv\xc3\xa1lida ou nome n\xc3\xa3o fornecido.']);
        }
        exit();
    }

    public function atualizarSenha(): void
    {
        ob_clean(); 
        header('Content-Type: application/json');

        error_log("DEBUG PERFIL CONTROLLER: Chegou em atualizarSenha. _POST: " . print_r($_POST, true));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $senhaAtual = filter_input(INPUT_POST, 'senha_atual', FILTER_DEFAULT);
            $novaSenha = filter_input(INPUT_POST, 'nova_senha', FILTER_DEFAULT);
            $confirmaSenha = filter_input(INPUT_POST, 'confirma_senha', FILTER_DEFAULT);
            $userId = $this->userData['id'] ?? null;

            if ($userId === null) {
                echo json_encode(['success' => false, 'message' => 'ID do usu\xc3\xa1rio n\xc3\xa3o encontrado na sess\xc3\xa3o.']);
                exit();
            }

            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmaSenha)) {
                echo json_encode(['success' => false, 'message' => 'Todos os campos s\xc3\xa3o obrigat\xc3\xb3rios!']);
                exit();
            }

            if ($novaSenha !== $confirmaSenha) {
                echo json_encode(['success' => false, 'message' => 'As novas senhas n\xc3\xa3o coincidem!']);
                exit();
            }

            if (strlen($novaSenha) < 6) {
                echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres!']);
                exit();
            }

            $result = $this->admsPerfil->atualizarSenha($userId, $senhaAtual, $novaSenha);

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => $result['message'] ?? 'Senha atualizada com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Erro ao atualizar a senha.']);
            }
        }
        exit();
    }

    /**
     * Realiza o soft delete da conta do usu\xc3\xa1rio logado.
     * Marca a conta como deletada no banco de dados e invalida a sess\xc3\xa3o.
     * Este m\xc3\xa9todo \xc3\xa9 acessado via POST.
     */
    public function softDeleteAccount(): void
    {
        ob_clean(); 
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Erro ao processar a solicita\xc3\xa7\xc3\xa3o.'];

        error_log("DEBUG PERFIL CONTROLLER: Chegou em softDeleteAccount. _POST: " . print_r($_POST, true));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $this->userData['id'] ?? null;

            if ($userId === null) {
                $response['message'] = 'ID do usu\xc3\xa1rio n\xc3\xa3o encontrado na sess\xc3\xa3o.';
                echo json_encode($response);
                exit();
            }

            $result = $this->admsPerfil->softDeleteUserAccount($userId);

            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = $result['message'] ?? 'Sua conta foi desativada com sucesso. Voc\xc3\xaa ser\xc3\xa1 redirecionado.';
                // Destr\xc3\xb3i a sess\xc3\xa3o ap\xc3\xb3s o soft delete bem-sucedido
                $this->destruirSessao();
            } else {
                $response['message'] = $result['message'] ?? 'N\xc3\xa3o foi poss\xc3\xadvel desativar sua conta. Tente novamente.';
            }
        } else {
            $response['message'] = 'M\xc3\xa9todo de requisi\xc3\xa7\xc3\xa3o inv\xc3\xa1lida.';
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Destr\xc3\xb3i a sess\xc3\xa3o do usu\xc3\xa1rio.
     * Replicado do Login.php para uso interno aqui.
     */
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
}
