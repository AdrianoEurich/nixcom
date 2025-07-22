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
        // Garante que $_SESSION['usuario'] existe para evitar notices
        $this->userData = $_SESSION['usuario'] ?? []; 

        // Inicializa parâmetros de busca e paginação
        $this->page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
        $this->searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $this->filterStatus = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all'; // 'all', 'active', 'pending', 'rejected', 'inactive'
        error_log("DEBUG CONTROLLER DASHBOARD: Construtor - Page: {$this->page}, Search: '{$this->searchTerm}', Status: '{$this->filterStatus}'");
    }

    /**
     * Verifica se o usuário está logado e define o nível de acesso numérico.
     */
    private function verifySession(): void
    {
        // Verifica se 'user_id' está na sessão (garante que o usuário está logado)
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
            $this->redirect(URLADM . "login");
        }

        // NOVO: Obtém o nome do nível de acesso da variável de sessão 'user_level' (definida pelo Login.php)
        $userLevelName = $_SESSION['user_level'] ?? ''; // ESTA LINHA É CRUCIAL!
        $numericUserLevel = 0; // Valor padrão para não-admin

        // Mapeia o nome do nível de acesso para um valor numérico
        // Adapte estes valores numéricos para os IDs reais dos seus níveis de acesso no banco de dados,
        // ou para uma hierarquia que você defina (ex: 1 para usuário, 3 para administrador).
        if ($userLevelName === 'administrador') {
            $numericUserLevel = 3; 
        } elseif ($userLevelName === 'usuario') {
            $numericUserLevel = 1; 
        }
        // ... adicione outros níveis conforme necessário

        // Armazena o nível numérico em uma nova chave da sessão para uso consistente
        $_SESSION['user_level_numeric'] = $numericUserLevel;

        error_log("DEBUG CONTROLLER DASHBOARD: verifySession - User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", User Level Name: " . $userLevelName . ", Numeric User Level: " . $numericUserLevel);
    }

    /**
     * Método principal para a página Dashboard.
     * Ele decide se carrega o layout completo ou apenas o conteúdo para SPA.
     */
    public function index(): void
    {
        // Prepara todos os dados necessários para a view
        $this->_prepareDashboardData();

        // Verifica se a requisição é AJAX (parâmetro 'ajax=true' na URL)
        $isAjaxRequest = (isset($_GET['ajax']) && $_GET['ajax'] === 'true');
        error_log("DEBUG CONTROLLER DASHBOARD: index() - Is AJAX Request: " . ($isAjaxRequest ? 'true' : 'false'));

        if ($isAjaxRequest) {
            // Se a requisição é AJAX, retorna APENAS os dados em JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'anuncios' => $this->data['listAnuncios'] ?? [],
                'pagination' => $this->data['pagination_data'] ?? [],
                'dashboard_stats' => $this->data['dashboard_stats'] ?? [], // Inclui stats para possível atualização via JS
                'message' => 'Dados carregados via AJAX.'
            ]);
            exit();
        } else {
            // Se a requisição NÃO é AJAX (carga inicial da página), carrega o layout completo
            $loadView = new ConfigViewAdm('adms/Views/dashboard/content_dashboard', $this->data);
            $loadView->loadView(); // Usa o método para carregar a view de conteúdo dentro do main.php
        }
    }

    /**
     * Prepara todos os dados necessários para o dashboard, tanto para a carga inicial quanto para AJAX.
     */
    private function _prepareDashboardData(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        // AGORA USAMOS o nível numérico armazenado na sessão
        $userLevel = $_SESSION['user_level_numeric'] ?? 0; 
        $hasAnuncio = false;
        $existingAnuncio = null;

        $admsAnuncioModel = new AdmsAnuncio();

        // Busca o anúncio do usuário logado (para a sidebar, etc.)
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

        // Apenas carrega a lista de anúncios e estatísticas se o usuário for administrador (user_level_numeric >= 3)
        if ($userLevel >= 3) { 
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usuário é ADMIN. Carregando dados da tabela de anúncios.");
            // Obtém os anúncios mais recentes para a tabela do administrador
            $listAnuncios = $admsAnuncioModel->getLatestAnuncios($this->page, $this->limit, $this->searchTerm, $this->filterStatus);
            
            // Obtém o total de anúncios para a paginação
            $totalAnuncios = $admsAnuncioModel->getTotalAnuncios($this->searchTerm, $this->filterStatus);
            $total_pages = ceil($totalAnuncios / $this->limit);
            if ($total_pages === 0) { // Evita divisão por zero se não houver anúncios
                $total_pages = 1;
            }
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Total Anúncios: " . $totalAnuncios . ", Total Páginas: " . $total_pages);
        } else {
            error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Usuário não é ADMIN. Não carregando dados da tabela de anúncios.");
        }

        $this->data = [
            'user_data' => $this->userData,
            'sidebar_active' => 'dashboard', // Para marcar o item ativo na sidebar
            'dashboard_stats' => $this->getDashboardStats($admsAnuncioModel), // Passa o modelo para a função de stats
            'recent_activity' => [], // Esta função não será mais usada para a tabela de anúncios
            'has_anuncio' => $hasAnuncio, // Adiciona se o usuário tem anúncio
            'anuncio_data' => $existingAnuncio, // ADICIONADO: Passa os dados completos do anúncio, incluindo o status
            'listAnuncios' => $listAnuncios, // Dados reais para a tabela de anúncios
            'pagination_data' => [ // Dados para a paginação
                'current_page' => $this->page,
                'total_pages' => $total_pages,
                'search_term' => $this->searchTerm,
                'filter_status' => $this->filterStatus,
                'limit' => $this->limit // Adiciona o limite para a view
            ],
            'user_level' => $userLevel // Passa o nível do usuário para a view (o numérico)
        ];
        error_log("DEBUG CONTROLLER DASHBOARD: _prepareDashboardData() - Dados para a view: " . print_r($this->data, true));
    }

    /**
     * Redireciona para uma URL.
     * @param string $url A URL para onde redirecionar.
     */
    private function redirect(string $url): void
    {
        header("Location: " . $url);
        exit();
    }

    /**
     * Funções para obter dados estatísticos do dashboard.
     * @param AdmsAnuncio $admsAnuncioModel Instância do modelo AdmsAnuncio.
     * @return array Array associativo com as estatísticas.
     */
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
            // 'total_visits' => 12345, // Exemplo, buscar do DB se existir
            // 'total_payments' => 3210, // Exemplo, buscar do DB se existir
            // 'revenue' => 'R$48,245' // Exemplo, buscar do DB se existir
        ];
    }
}
