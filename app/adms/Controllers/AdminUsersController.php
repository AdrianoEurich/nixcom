<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsUser; // Importa o modelo AdmsUser

/**
 * Controlador para gerenciar as ações de administração de usuários (listar, soft delete).
 * Todas as ações são projetadas para serem acessadas por administradores.
 */
class AdminUsersController
{
    private array $data = [];
    private int $page;
    private string $searchTerm;
    private string $filterStatus;
    private int $limit = 10; // Limite de usuários por página para o admin

    public function __construct()
    {
        // Garante que a sessão esteja iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está logado e tem nível de acesso de administrador (nível 3 ou superior)
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_level_numeric'] ?? 0) < 3) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Você não tem permissão para esta área.'];
            header("Location: " . URLADM . "dashboard"); // Redireciona para o dashboard ou login
            exit();
        }

        $this->page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
        $this->searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $this->filterStatus = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all';
        error_log("DEBUG ADMINUSERS: Construtor - Page: {$this->page}, Search: '{$this->searchTerm}', Status: '{$this->filterStatus}'");
    }

    /**
     * Método principal para a página de gerenciamento de usuários.
     * Carrega a view HTML da lista de usuários, com ou sem o layout completo,
     * dependendo se a requisição é AJAX.
     */
    public function index(): void
    {
        $this->_prepareUsersData();

        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        error_log("DEBUG ADMINUSERS: index() - Is AJAX Request: " . ($isAjaxRequest ? 'true' : 'false'));

        $loadView = new ConfigViewAdm('adms/Views/users/list_users', $this->data); // Nova view para listar usuários

        if ($isAjaxRequest) {
            error_log("DEBUG ADMINUSERS: index() - Carregando apenas o conteúdo da view para requisição AJAX.");
            $loadView->loadContentView(); 
        } else {
            error_log("DEBUG ADMINUSERS: index() - Carregando a view HTML completa da lista de usuários.");
            $loadView->loadView();
        }
    }

    /**
     * Método para obter os dados de usuários via AJAX.
     * Este método retorna APENAS JSON.
     */
    public function getUsersData(): void
    {
        $this->_prepareUsersData();

        error_log("DEBUG ADMINUSERS: getUsersData() - Retornando dados JSON para usuários.");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'users' => $this->data['listUsers'] ?? [],
            'pagination' => $this->data['pagination_data'] ?? [],
            'message' => 'Dados de usuários carregados via AJAX.'
        ]);
        exit();
    }

    /**
     * Prepara os dados dos usuários para serem exibidos na view.
     */
    private function _prepareUsersData(): void
    {
        $admsUserModel = new AdmsUser();

        $listUsers = $admsUserModel->getLatestUsers($this->page, $this->limit, $this->searchTerm, $this->filterStatus);
        $totalUsers = $admsUserModel->getTotalUsers($this->searchTerm, $this->filterStatus);
        $totalPages = ceil($totalUsers / $this->limit);
        if ($totalPages === 0) {
            $totalPages = 1;
        }

        $this->data = [
            'user_data' => $_SESSION['usuario'] ?? [],
            'sidebar_active' => 'users', // Define um item de menu ativo para usuários
            'listUsers' => $listUsers,
            'pagination_data' => [
                'current_page' => $this->page,
                'total_pages' => $totalPages,
                'search_term' => $this->searchTerm,
                'filter_status' => $this->filterStatus,
                'limit' => $this->limit
            ],
            'msg' => $_SESSION['msg'] ?? [], // Para exibir mensagens de sucesso/erro
        ];
        unset($_SESSION['msg']); // Limpa a mensagem após exibir

        error_log("DEBUG ADMINUSERS: _prepareUsersData() - Dados para a view: " . print_r($this->data, true));
    }

    /**
     * Realiza o soft delete de uma conta de usuário.
     * Espera um POST com 'user_id'.
     */
    public function softDeleteUser(): void
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Erro ao processar a solicitação.'];

        // Recebe os dados via JSON POST
        $input = file_get_contents('php://input');
        $requestData = json_decode($input, true);

        $userIdToDelete = $requestData['user_id'] ?? null;

        if ($userIdToDelete === null) {
            $response['message'] = 'ID do usuário não fornecido.';
            echo json_encode($response);
            exit();
        }

        // Previne que um administrador se auto-delete
        if ($userIdToDelete == ($_SESSION['user_id'] ?? 0)) {
            $response['message'] = 'Você não pode desativar sua própria conta através desta interface.';
            echo json_encode($response);
            exit();
        }

        $admsUserModel = new AdmsUser();
        $result = $admsUserModel->softDeleteUser($userIdToDelete);

        if ($result) { // AdmsUser::softDeleteUser retorna bool
            $response['success'] = true;
            $response['message'] = 'Conta de usuário desativada com sucesso.';
        } else {
            $response['message'] = 'Falha ao desativar a conta do usuário. Verifique os logs.';
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Ativa uma conta de usuário soft-deletada.
     * Espera um POST com 'user_id'.
     */
    public function activateUser(): void
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Erro ao processar a solicitação.'];

        $input = file_get_contents('php://input');
        $requestData = json_decode($input, true);

        $userIdToActivate = $requestData['user_id'] ?? null;

        if ($userIdToActivate === null) {
            $response['message'] = 'ID do usuário não fornecido.';
            echo json_encode($response);
            exit();
        }

        $admsUserModel = new AdmsUser();
        $result = $admsUserModel->activateUser($userIdToActivate); // Novo método no AdmsUser

        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Conta de usuário ativada com sucesso.';
        } else {
            $response['message'] = 'Falha ao ativar a conta do usuário. Verifique os logs.';
        }

        echo json_encode($response);
        exit();
    }
}
