<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsAnuncio; // Importa o modelo AdmsAnuncio

/**
 * Controlador da página de Dashboard.
 * Responsável por carregar os dados para o dashboard, incluindo a lista de anúncios recentes.
 */
class Dashboard
{
    private array $data = [];
    private array $userData;
    private int $page;
    private string $searchTerm;
    private string $filterStatus;
    private int $limit = 5; // Limite de anúncios por página para o admin dashboard

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
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
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
     * Método principal para a página Dashboard.
     * Este método carrega a view HTML do dashboard, com ou sem o layout completo,
     * dependendo se a requisição é AJAX.
     */
    public function index(): void
    {
        $this->_prepareDashboardData();
        
        // Verifica se a requisição é AJAX usando o cabeçalho X-Requested-With
        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        error_log("DEBUG CONTROLLER DASHBOARD: index() - Is AJAX Request: " . ($isAjaxRequest ? 'true' : 'false'));

        $loadView = new ConfigViewAdm('adms/Views/dashboard/content_dashboard', $this->data);

        if ($isAjaxRequest) {
            // Se a requisição é AJAX, carrega APENAS o conteúdo da view
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Carregando apenas o conteúdo da view para requisição AJAX.");
            $loadView->loadContentView(); 
        } else {
            // Se a requisição NÃO é AJAX (carga inicial da página), carrega o layout completo
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Carregando a view HTML completa do dashboard.");
            $loadView->loadView(); // Este método em ConfigViewAdm já inclui main.php
        }
    }

    /**
     * Método para obter os dados de anúncios via AJAX.
     * Este método retorna APENAS JSON.
     */
    public function getAnunciosData(): void
    {
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
        exit();
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
            error_log("ERRO CONTROLLER DASHBOARD: _prepareDashboardData() - User ID não encontrado na sessão.");
        }

        $listAnuncios = [];
        $totalAnuncios = 0;
        $total_pages = 1;

        if ($userLevel >= 3) { 
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usuário é ADMIN. Carregando dados da tabela de anúncios.");
            // Passa o termo de busca e o status para o modelo
            $listAnuncios = $admsAnuncioModel->getLatestAnuncios($this->page, $this->limit, $this->searchTerm, $this->filterStatus);
            
            // Passa o termo de busca e o status para o modelo para o total
            $totalAnuncios = $admsAnuncioModel->getTotalAnuncios($this->searchTerm, $this->filterStatus);
            $total_pages = ceil($totalAnuncios / $this->limit);
            if ($total_pages === 0) {
                $total_pages = 1;
            }
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Total Anúncios: " . $totalAnuncios . ", Total Páginas: " . $total_pages);
        } else {
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usuário não é ADMIN. Não carregando dados da tabela de anúncios.");
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
        // Certifique-se de que esses métodos também podem receber o termo de busca, se necessário para as estatísticas
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
