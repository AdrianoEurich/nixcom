<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsUser;
use Adms\Models\AdmsAnuncio;

class AdminUsersController
{
    private array $data = [];
    private AdmsUser $admsUser;
    private AdmsAnuncio $admsAnuncio;
    private int $page;
    private int $limit = 5;
    private string $searchTerm;
    private string $filterPlan;
    private string $filterStatus;

    public function __construct()
    {
        $this->verifyAdminAccess();
        $this->admsUser = new AdmsUser();
        $this->admsAnuncio = new AdmsAnuncio();
        
        $this->page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
        $rawSearch = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '';
        $this->searchTerm = trim(strip_tags($rawSearch));
        $this->filterPlan = filter_input(INPUT_GET, 'plan', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all';
        $this->filterStatus = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all';
        
        error_log("DEBUG AdminUsersController: Page={$this->page}, Search='{$this->searchTerm}', Plan='{$this->filterPlan}', Status='{$this->filterStatus}'");
    }

    private function verifyAdminAccess(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
            $this->redirect(URLADM . "login");
        }

        $userLevel = $_SESSION['user_level'] ?? $_SESSION['user_role'] ?? 'usuario';
        if ($userLevel !== 'administrador') {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Apenas administradores podem acessar esta página.'];
            $this->redirect(URLADM . "dashboard");
        }
    }

    public function index(): void
    {
        error_log("DEBUG AdminUsersController::index - Método chamado");
        error_log("DEBUG AdminUsersController::index - IsAjaxRequest: " . (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ? 'true' : 'false'));
        
        $this->prepareUsersData();
        
        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        
        error_log("DEBUG AdminUsersController::index - Dados preparados: " . json_encode($this->data));
        
        $loadView = new ConfigViewAdm('adms/Views/users/users', $this->data);
        
        if ($isAjaxRequest) {
            error_log("DEBUG AdminUsersController::index - Carregando view via AJAX");
            $loadView->loadContentView();
        } else {
            error_log("DEBUG AdminUsersController::index - Carregando view completa");
            $loadView->loadView();
        }
    }

    public function listUsers(): void
    {
        header('Content-Type: application/json');
        
        error_log("DEBUG AdminUsersController::listUsers - GET search='" . ($_GET['search'] ?? '') . "', plan='" . ($_GET['plan'] ?? '') . "', page='" . ($_GET['page'] ?? '') . "'");
        error_log("DEBUG AdminUsersController::listUsers - Efetivo: page={$this->page}, search='" . $this->searchTerm . "', plan='{$this->filterPlan}', status='{$this->filterStatus}'");
        
        try {
            $users = $this->admsUser->getAllUsersWithFilters(
                $this->page, 
                $this->limit, 
                $this->searchTerm, 
                $this->filterPlan,
                $this->filterStatus
            );
            
            $totalUsers = $this->admsUser->getTotalUsersWithFilters(
                $this->searchTerm, 
                $this->filterPlan,
                $this->filterStatus
            );
            
            error_log("DEBUG AdminUsersController::listUsers - Usuários encontrados: " . count($users) . ", Total: $totalUsers");
            
            $totalPages = ceil($totalUsers / $this->limit);
            
            echo json_encode([
                'success' => true,
                'users' => $users,
                'pagination' => [
                    'current_page' => $this->page,
                    'total_pages' => $totalPages,
                    'total_users' => $totalUsers,
                    'limit' => $this->limit
                ],
                'filters' => [
                    'search' => $this->searchTerm,
                    'plan' => $this->filterPlan,
                    'status' => $this->filterStatus
                ],
                // DEBUG TEMPORÁRIO (compat e detalhado)
                'debug_detected_columns' => method_exists($this->admsUser, 'getLastListDetectedColumns') ? $this->admsUser->getLastListDetectedColumns() : (method_exists($this->admsUser, 'getLastDetectedColumns') ? $this->admsUser->getLastDetectedColumns() : []),
                'debug_sql' => method_exists($this->admsUser, 'getLastListQuery') ? $this->admsUser->getLastListQuery() : (method_exists($this->admsUser, 'getLastQuery') ? $this->admsUser->getLastQuery() : ''),
                'debug_params' => method_exists($this->admsUser, 'getLastListParams') ? $this->admsUser->getLastListParams() : (method_exists($this->admsUser, 'getLastParams') ? $this->admsUser->getLastParams() : []),
                'debug_list_detected_columns' => method_exists($this->admsUser, 'getLastListDetectedColumns') ? $this->admsUser->getLastListDetectedColumns() : [],
                'debug_list_sql' => method_exists($this->admsUser, 'getLastListQuery') ? $this->admsUser->getLastListQuery() : '',
                'debug_list_params' => method_exists($this->admsUser, 'getLastListParams') ? $this->admsUser->getLastListParams() : [],
                'debug_count_detected_columns' => method_exists($this->admsUser, 'getLastCountDetectedColumns') ? $this->admsUser->getLastCountDetectedColumns() : [],
                'debug_count_sql' => method_exists($this->admsUser, 'getLastCountQuery') ? $this->admsUser->getLastCountQuery() : '',
                'debug_count_params' => method_exists($this->admsUser, 'getLastCountParams') ? $this->admsUser->getLastCountParams() : []
            ]);
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::listUsers: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar usuários: ' . $e->getMessage()
            ]);
        }
    }

    public function viewUser(): void
    {
        header('Content-Type: application/json');
        
        $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$userId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID do usuário não fornecido'
            ]);
            return;
        }
        
        try {
            $user = $this->admsUser->getUserById($userId);
            
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ]);
                return;
            }
            
            // Buscar anúncios do usuário
            $anuncios = $this->admsAnuncio->getAnunciosByUserId($userId);
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'anuncios' => $anuncios
            ]);
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::viewUser: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar dados do usuário: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtém detalhes do usuário para edição rápida
     */
    public function getUserDetails(): void
    {
        header('Content-Type: application/json');
        
        // Extrair ID da URL (formato: /admin-users/getUserDetails/123)
        $requestUri = $_SERVER['REQUEST_URI'];
        $pathParts = explode('/', trim($requestUri, '/'));
        $userId = end($pathParts);
        
        // Validar se é um número
        $userId = filter_var($userId, FILTER_VALIDATE_INT);
        
        if (!$userId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID do usuário não fornecido'
            ]);
            return;
        }
        
        try {
            $user = $this->admsUser->getUserById($userId);
            
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ]);
                return;
            }
            
            // Retornar apenas os dados necessários para o modal
            $userDetails = [
                'id' => $user['id'],
                'nome' => $user['nome'] ?? 'Nome não informado',
                'email' => $user['email'] ?? 'Email não informado',
                'status' => $user['status'] ?? 'ativo',
                'plan_type' => $user['plan_type'] ?? 'free',
                'payment_status' => $user['payment_status'] ?? 'pending',
                'created_at' => $user['created_at'] ?? null,
                'ultimo_acesso' => $user['ultimo_acesso'] ?? null
            ];
            
            echo json_encode([
                'success' => true,
                'user' => $userDetails
            ]);
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::getUserDetails: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar detalhes do usuário: ' . $e->getMessage()
            ]);
        }
    }

    public function updateUserStatus(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $status = $input['status'] ?? null;
        
        if (!$userId || !$status) {
            echo json_encode([
                'success' => false,
                'message' => 'Dados insuficientes'
            ]);
            return;
        }
        
        try {
            $result = $this->admsUser->updateUserStatus($userId, $status);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status do usuário atualizado com sucesso'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar status do usuário'
                ]);
            }
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::updateUserStatus: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
    }

    public function updateUserPlan(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $planType = $input['plan_type'] ?? null;
        
        if (!$userId || !$planType) {
            echo json_encode([
                'success' => false,
                'message' => 'Dados insuficientes'
            ]);
            return;
        }
        
        try {
            $result = $this->admsUser->updateUserPlan($userId, $planType);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Plano do usuário atualizado com sucesso'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar plano do usuário'
                ]);
            }
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::updateUserPlan: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteUser(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID do usuário não fornecido'
            ]);
            return;
        }
        
        // Verificar se não é o próprio administrador
        if ($userId == $_SESSION['user_id']) {
            echo json_encode([
                'success' => false,
                'message' => 'Você não pode excluir sua própria conta'
            ]);
            return;
        }
        
        try {
            // Exclusão definitiva (remoção total) com CASCADE para anúncios vinculados
            $result = $this->admsUser->deleteUser($userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário excluído com sucesso'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao excluir usuário'
                ]);
            }
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::deleteUser: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
    }

    public function getStats(): void
    {
        header('Content-Type: application/json');
        
        error_log("DEBUG AdminUsersController::getStats - Método chamado");
        
        try {
            $stats = $this->admsUser->getUsersStats();
            
            error_log("DEBUG AdminUsersController::getStats - Estatísticas: " . json_encode($stats));
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::getStats: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar estatísticas: ' . $e->getMessage()
            ]);
        }
    }

    private function prepareUsersData(): void
    {
        $this->data = [
            'title' => 'Gerenciar Usuários',
            'sidebar_active' => 'admin-users',
            'page' => $this->page,
            'search_term' => $this->searchTerm,
            'filter_plan' => $this->filterPlan,
            'limit' => $this->limit
        ];
    }

    /**
     * Atualiza usuário completo (status, plano e pagamento)
     */
    public function updateUser(): void
    {
        header('Content-Type: application/json');
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Dados inválidos'
            ]);
            return;
        }
        
        $userId = filter_var($data['user_id'], FILTER_VALIDATE_INT);
        $status = $data['status'] ?? null;
        $planType = $data['plan_type'] ?? null;
        $paymentStatus = $data['payment_status'] ?? null;
        $nome = $data['nome'] ?? null;
        $email = $data['email'] ?? null;
        $telefone = $data['telefone'] ?? null;
        $cpf = $data['cpf'] ?? null;
        
        if (!$userId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID do usuário inválido'
            ]);
            return;
        }
        
        try {
            $updated = false;
            
            // Atualizar status se fornecido
            if ($status && in_array($status, ['ativo', 'inativo', 'suspenso'])) {
                $ok = $this->admsUser->updateUserStatus($userId, $status);
                $updated = $updated || $ok;
            }
            
            // Atualizar plano se fornecido
            if ($planType && in_array($planType, ['free', 'basic', 'premium'])) {
                $ok = $this->admsUser->updateUserPlan($userId, $planType);
                $updated = $updated || $ok;
            }
            
            // Atualizar status de pagamento se fornecido (mapear valores da UI -> enum do BD)
            if ($paymentStatus) {
                $mapUiToDb = [ 'approved' => 'paid', 'rejected' => 'failed' ];
                $dbPayment = $mapUiToDb[$paymentStatus] ?? $paymentStatus; // mantém 'pending' como está
                $allowedDbPayments = ['pending','paid','failed'];
                if (in_array($dbPayment, $allowedDbPayments, true)) {
                    $ok = $this->admsUser->updatePaymentStatus($userId, $dbPayment);
                    $updated = $updated || $ok;
                }
            }
            
            // Atualizar dados básicos (nome, email, telefone, cpf) se enviados
            $partial = [];
            if ($nome !== null) { $partial['nome'] = trim($nome); }
            if ($email !== null) { $partial['email'] = trim($email); }
            if ($telefone !== null) { $partial['telefone'] = trim($telefone); }
            if ($cpf !== null) { $partial['cpf'] = trim($cpf); }
            if (!empty($partial)) {
                $ok = $this->admsUser->updateUserPartial($userId, $partial);
                $updated = $updated || $ok;
            }
            
            if ($updated) {
                // Se o usuário editado é o usuário logado, atualizar sessão para refletir imediatamente
                if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$userId) {
                    $fresh = $this->admsUser->getUserById($userId);
                    if ($fresh) {
                        // Campos comuns utilizados no sistema
                        if (isset($fresh['nome'])) { $_SESSION['user_name'] = $fresh['nome']; }
                        if (isset($fresh['email'])) { $_SESSION['user_email'] = $fresh['email']; }
                        if (isset($fresh['nivel_acesso'])) { $_SESSION['user_role'] = $fresh['nivel_acesso']; }
                        if (isset($fresh['status'])) { $_SESSION['user_status'] = $fresh['status']; }
                        if (isset($fresh['plan_type'])) { $_SESSION['user_plan'] = $fresh['plan_type']; }
                        if (isset($fresh['payment_status'])) { $_SESSION['payment_status'] = $fresh['payment_status']; }
                        if (isset($fresh['foto'])) { $_SESSION['user_photo'] = $fresh['foto']; }
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário atualizado com sucesso'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhuma alteração válida fornecida'
                ]);
            }
            
        } catch (\Exception $e) {
            error_log("ERRO AdminUsersController::updateUser: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Atualiza status de pagamento do usuário
     */
    public function updatePaymentStatus(): void
    {
        header('Content-Type: application/json');
        
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || !isset($data['user_id']) || !isset($data['payment_status'])) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
                return;
            }
            
            $userId = (int) $data['user_id'];
            $paymentStatus = $data['payment_status'];
            
            // Validar status
            if (!in_array($paymentStatus, ['pending', 'approved', 'rejected', 'cancelled'])) {
                echo json_encode(['success' => false, 'message' => 'Status inválido']);
                return;
            }
            
            $result = $this->admsUser->updatePaymentStatus($userId, $paymentStatus);
            
            if ($result) {
                // Se status for aprovado, ativar funcionalidades do plano
                if ($paymentStatus === 'approved') {
                    $userData = $this->admsUser->getUserById($userId);
                    if ($userData && isset($userData['plan_type'])) {
                        $this->activateUserPlanFeatures($userId, $userData['plan_type']);
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Status de pagamento atualizado com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status de pagamento']);
            }
            
        } catch (\Exception $e) {
            error_log("AdminUsersController::updatePaymentStatus - Erro: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
    }

    /**
     * Ativa funcionalidades do plano do usuário
     */
    private function activateUserPlanFeatures(int $userId, string $planType): void
    {
        try {
            error_log("AdminUsersController::activateUserPlanFeatures - Usuário {$userId} teve plano {$planType} ativado");
            
            // Criar notificação para o usuário
            $notificationModel = new \Adms\Models\AdmsNotificacao();
            $notificationModel->createNotification(
                $userId,
                'Pagamento Aprovado',
                "Seu pagamento foi aprovado! O plano {$planType} está agora ativo.",
                'success'
            );
            
        } catch (\Exception $e) {
            error_log("AdminUsersController::activateUserPlanFeatures - Erro: " . $e->getMessage());
        }
    }

    private function redirect(string $url): void
    {
        header("Location: " . $url);
        exit();
    }
}