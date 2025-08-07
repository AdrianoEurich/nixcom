<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: P\xc3\xa1gina n\xc3\xa3o encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsAnuncio; 

/**
 * Controlador da p\xc3\xa1gina de Dashboard.
 * Respons\xc3\xa1vel por carregar os dados para o dashboard, incluindo a lista de an\xc3\xabncios recentes.
 */
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
        $this->filterStatus = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all';
        error_log("DEBUG CONTROLLER DASHBOARD: Construtor - Page: {$this->page}, Search: '{$this->searchTerm}', Status: '{$this->filterStatus}'");
    }

    private function verifySession(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Fa\xc3\xa7a login para continuar.'];
            $this->redirect(URLADM . "login");
        }

        $userLevelName = $_SESSION['user_level'] ?? '';
        $numericUserLevel = 0;

        if ($userLevelName === 'administrador') {
            $numericUserLevel = 3; 
        } elseif ($userLevelName === 'usuario') {
            $numericUserLevel = 1; 
        }
        $_SESSION['user_level_numeric'] = $numericUserLevel;

        error_log("DEBUG CONTROLLER DASHBOARD: verifySession - User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", User Level Name: " . $userLevelName . ", Numeric User Level: " . $numericUserLevel);
    }

    /**
     * M\xc3\xa9todo principal para a p\xc3\xa1gina Dashboard.
     * Este m\xc3\xa9todo carrega a view HTML do dashboard, com ou sem o layout completo,
     * dependendo se a requisi\xc3\xa7\xc3\xa3o \xc3\xa9 AJAX.
     */
    public function index(): void
    {
        $this->_prepareDashboardData();
        
        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        error_log("DEBUG CONTROLLER DASHBOARD: index() - Is AJAX Request: " . ($isAjaxRequest ? 'true' : 'false'));

        $loadView = new ConfigViewAdm('adms/Views/dashboard/content_dashboard', $this->data);

        if ($isAjaxRequest) {
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Carregando apenas o conte\xc3\xbado da view para requisi\xc3\xa7\xc3\xa3o AJAX.");
            $loadView->loadContentView(); 
        } else {
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Carregando a view HTML completa do dashboard.");
            $loadView->loadView(); 
        }
    }

    /**
     * M\xc3\xa9todo para obter os dados de an\xc3\xabncios via AJAX.
     * Este m\xc3\xa9todo retorna APENAS JSON.
     */
    public function getAnunciosData(): void
    {
        error_log("DEBUG CONTROLLER DASHBOARD: getAnunciosData() - M\xc3\xa9todo AJAX alcan\xc3\xa7ado.");
        $this->_prepareDashboardData();

        error_log("DEBUG CONTROLLER DASHBOARD: getAnunciosData() - Retornando dados JSON para an\xc3\xabncios.");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'anuncios' => $this->data['listAnuncios'] ?? [],
            'pagination' => $this->data['pagination_data'] ?? [],
            'dashboard_stats' => $this->data['dashboard_stats'] ?? [],
            'message' => 'Dados de an\xc3\xabncios carregados via AJAX.'
        ]);
    }

    private function _prepareDashboardData(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $userLevel = $_SESSION['user_level_numeric'] ?? 0; 
        $hasAnuncio = false;
        $existingAnuncio = null;

        $admsAnuncioModel = new AdmsAnuncio();

        if ($userId) {
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            $hasAnuncio = !empty($existingAnuncio);
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - User ID: " . $userId . ", Has Anuncio: " . ($hasAnuncio ? 'true' : 'false') . ", Anuncio Status: " . ($existingAnuncio['status'] ?? 'N/A'));
        } else {
            error_log("ERRO CONTROLLER DASHBOARD: _prepareDashboardData() - User ID n\xc3\xa3o encontrado na sess\xc3\xa3o.");
        }

        $listAnuncios = [];
        $totalAnuncios = 0;
        $total_pages = 1;

        if ($userLevel >= 3) { 
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usu\xc3\xa1rio \xc3\xa9 ADMIN. Carregando dados da tabela de an\xc3\xabncios.");
            $listAnuncios = $admsAnuncioModel->getLatestAnuncios($this->page, $this->limit, $this->searchTerm, $this->filterStatus);
            $totalAnuncios = $admsAnuncioModel->getTotalAnuncios($this->searchTerm, $this->filterStatus);
            $total_pages = ceil($totalAnuncios / $this->limit);
            if ($total_pages === 0) {
                $total_pages = 1;
            }
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Total An\xc3\xabncios: " . $totalAnuncios . ", Total P\xc3\xa1ginas: " . $total_pages);
        } else {
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usu\xc3\xa1rio n\xc3\xa3o \xc3\xa9 ADMIN. N\xc3\xa3o carregando dados da tabela de an\xc3\xabncios.");
        }

        $this->data = [
            'user_data' => $this->userData,
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
        // Novo: Obtém a contagem de anúncios excluídos.
        $deletedAnuncios = $admsAnuncioModel->getTotalAnuncios('','deleted');

        $approvalRate = ($totalAnuncios > 0) ? round(($activeAnuncios / $totalAnuncios) * 100) : 0;

        return [
            'total_anuncios' => $totalAnuncios,
            'active_anuncios' => $activeAnuncios,
            'pending_anuncios' => $pendingAnuncios,
            'rejected_anuncios' => $rejectedAnuncios,
            'inactive_anuncios' => $inactiveAnuncios,
            'deleted_anuncios' => $deletedAnuncios, // Adicionado para a view
            'approval_rate' => $approvalRate . '%',
        ];
    }
}
