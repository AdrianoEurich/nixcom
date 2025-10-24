<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsAnuncio; 
use Adms\Models\AdmsUser;

class Dashboard
{
    private array $data = [];
    private array $userData;
    private int $page;
    private string $searchTerm;
    private string $filterStatus;
    private int $limit = 5; 

    public function __construct()
    {
        $this->verifySession();
        $this->userData = $_SESSION['usuario'] ?? []; 

        $this->page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
        $this->searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        // AJUSTE AQUI:
        $this->filterStatus = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'active';
        error_log("DEBUG CONTROLLER DASHBOARD: Construtor - Page: {$this->page}, Search: '{$this->searchTerm}', Status: '{$this->filterStatus}'");
    }

    private function verifySession(): void
    {
        // Verificar se usuário está logado usando diferentes variáveis de sessão
        $userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
        
        if (!$userId) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
            $this->redirect(URLADM . "login");
        }

        // Usar as variáveis de sessão corretas definidas no cadastro
        $userLevelName = $_SESSION['user_level'] ?? $_SESSION['user_role'] ?? $_SESSION['nivel_acesso'] ?? 'usuario';
        $numericUserLevel = 0;

        if ($userLevelName === 'administrador') {
            $numericUserLevel = 3; 
        } elseif ($userLevelName === 'usuario' || $userLevelName === 'normal') {
            $numericUserLevel = 1; 
        }
        $_SESSION['user_level_numeric'] = $numericUserLevel;

        error_log("DEBUG CONTROLLER DASHBOARD: verifySession - User ID: " . $userId . ", User Level Name: " . $userLevelName . ", Numeric User Level: " . $numericUserLevel);

        // Sincronizar sessão com dados mais recentes do usuário (evita precisar relogar após admin editar)
        try {
            $userModel = new AdmsUser();
            $fresh = $userModel->getUserById((int)$userId);
            if ($fresh) {
                if (isset($fresh['nome'])) { $_SESSION['user_name'] = $fresh['nome']; }
                if (isset($fresh['email'])) { $_SESSION['user_email'] = $fresh['email']; }
                if (isset($fresh['nivel_acesso'])) { $_SESSION['user_role'] = $fresh['nivel_acesso']; }
                if (isset($fresh['status'])) { $_SESSION['user_status'] = $fresh['status']; }
                if (isset($fresh['plan_type'])) { $_SESSION['user_plan'] = $fresh['plan_type']; }
                if (isset($fresh['payment_status'])) { $_SESSION['payment_status'] = $fresh['payment_status']; }
                if (isset($fresh['foto'])) { $_SESSION['user_photo_path'] = $fresh['foto']; }
            }
        } catch (\Exception $e) { /* silencioso */ }
    }

    public function index(): void
    {
        $this->_prepareDashboardData();
        
        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        error_log("DEBUG CONTROLLER DASHBOARD: index() - Is AJAX Request: " . ($isAjaxRequest ? 'true' : 'false'));

        $loadView = new ConfigViewAdm('adms/Views/dashboard/content_dashboard', $this->data);

        if ($isAjaxRequest) {
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Carregando apenas o conteúdo da view para requisição AJAX.");
            $loadView->loadContentView(); 
        } else {
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Carregando a view HTML completa do dashboard.");
            $loadView->loadView(); 
        }
    }

    public function getAnunciosData(): void
    {
        error_log("DEBUG CONTROLLER DASHBOARD: getAnunciosData() - Método AJAX alcançado.");
        $this->_prepareDashboardData();

        error_log("DEBUG CONTROLLER DASHBOARD: getAnunciosData() - Retornando dados JSON para anúncios.");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'anuncios' => $this->data['listAnuncios'] ?? [],
            'pagination' => $this->data['pagination_data'] ?? [],
            'dashboard_stats' => $this->data['dashboard_stats'] ?? [],
            'message' => 'Dados de anúncios carregados via AJAX.'
        ]);
    }

    private function _prepareDashboardData(): void
    {
        $userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
        $userLevel = $_SESSION['user_level_numeric'] ?? 0; 
        $hasAnuncio = false;
        $existingAnuncio = null;

        $admsAnuncioModel = new AdmsAnuncio();

        if ($userId) {
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - existingAnuncio retornado: " . json_encode($existingAnuncio));
            
            $hasAnuncio = !empty($existingAnuncio);
            
            // Atualiza a sessão com os dados mais recentes do anúncio
            $_SESSION['has_anuncio'] = $hasAnuncio;
            $_SESSION['anuncio_status'] = $existingAnuncio['status'] ?? 'not_found';
            $_SESSION['anuncio_id'] = $existingAnuncio['id'] ?? null;
            
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - User ID: " . $userId . ", Has Anuncio: " . ($hasAnuncio ? 'true' : 'false') . ", Anuncio Status: " . ($existingAnuncio['status'] ?? 'N/A'));
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Sessão atualizada: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));
        } else {
            error_log("ERRO CONTROLLER DASHBOARD: _prepareDashboardData() - User ID não encontrado na sessão.");
        }

        $listAnuncios = [];
        $totalAnuncios = 0;
        $total_pages = 1;

        if ($userLevel >= 3) { 
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usuário é ADMIN. Carregando dados da tabela de anúncios.");
            $listAnuncios = $admsAnuncioModel->getLatestAnuncios($this->page, $this->limit, $this->searchTerm, $this->filterStatus);
            $totalAnuncios = $admsAnuncioModel->getTotalAnuncios($this->searchTerm, $this->filterStatus);
            $total_pages = ceil($totalAnuncios / $this->limit);
            if ($total_pages === 0) {
                $total_pages = 1;
            }
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Total Anúncios: " . $totalAnuncios . ", Total Páginas: " . $total_pages);
        } else {
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usuário não é ADMIN. Não carregando dados da tabela de anúncios.");
        }

        // Buscar dados do usuário incluindo plano e status de pagamento
        $userPlan = $_SESSION['user_plan'] ?? 'free';
        $paymentStatus = $_SESSION['payment_status'] ?? 'pending';
        
        // Se não estiver na sessão, buscar do banco
        if (!isset($_SESSION['user_plan']) || !isset($_SESSION['payment_status'])) {
            $userData = $this->userData;
            if (isset($userData['plan_type'])) {
                $userPlan = $userData['plan_type'];
                $_SESSION['user_plan'] = $userPlan;
            }
            if (isset($userData['payment_status'])) {
                $paymentStatus = $userData['payment_status'];
                $_SESSION['payment_status'] = $paymentStatus;
            }
        }
        
        error_log("DEBUG CONTROLLER DASHBOARD: user_plan=" . $userPlan . ", payment_status=" . $paymentStatus);

        $this->data = [
            'user_data' => array_merge($this->userData, [
                'plan_type' => $userPlan,
                'payment_status' => $paymentStatus
            ]),
            'sidebar_active' => 'dashboard',
            'dashboard_stats' => $this->getDashboardStats($admsAnuncioModel),
            'recent_activity' => [],
            'has_anuncio' => $hasAnuncio,
            'anuncio_data' => $existingAnuncio,
            'listAnuncios' => $listAnuncios,
            'pagination_data' => [
                'current_page' => $this->page,
                'total_pages' => $total_pages,
                'search_term' => $this->searchTerm,
                'filter_status' => $this->filterStatus,
                'limit' => $this->limit
            ],
            'user_level' => $userLevel
        ];
        error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Dados para a view: " . print_r($this->data, true));
    }

    private function redirect(string $url): void
    {
        header("Location: " . $url);
        exit();
    }

    private function getDashboardStats(AdmsAnuncio $admsAnuncioModel): array
    {
        $totalAnuncios = $admsAnuncioModel->getTotalAnuncios('','all');
        $activeAnuncios = $admsAnuncioModel->getTotalAnuncios('','active');
        $pendingAnuncios = $admsAnuncioModel->getTotalAnuncios('','pending');
        $rejectedAnuncios = $admsAnuncioModel->getTotalAnuncios('','rejected');
        $inactiveAnuncios = $admsAnuncioModel->getTotalAnuncios('','inactive');

        $approvalRate = ($totalAnuncios > 0) ? round(($activeAnuncios / $totalAnuncios) * 100) : 0;

        return [
            'total_anuncios' => $totalAnuncios,
            'active_anuncios' => $activeAnuncios,
            'pending_anuncios' => $pendingAnuncios,
            'rejected_anuncios' => $rejectedAnuncios,
            'inactive_anuncios' => $inactiveAnuncios,
            'approval_rate' => $approvalRate . '%',
        ];
    }
}