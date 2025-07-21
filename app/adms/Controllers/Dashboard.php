<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsAnuncio; // Importa o modelo AdmsAnuncio

class Dashboard
{
    private array $data = [];
    private array $userData;
    private int $page;
    private string $searchTerm;
    private string $filterStatus;

    public function __construct()
    {
        $this->verifySession();
        if (isset($_SESSION['usuario'])) {
            $this->userData = $_SESSION['usuario'];
        } else {
            $this->userData = []; 
        }

        // Inicializa parâmetros de busca e paginação
        $this->page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
        $this->searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $this->filterStatus = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all'; // 'all', 'active', 'pending', 'rejected', 'paused'
    }

    private function verifySession(): void
    {
        if (!isset($_SESSION['usuario']['id'])) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
            $this->redirect(URLADM . "login");
        }
    }

    /**
     * Método principal para a página Dashboard.
     * Ele decide se carrega o layout completo ou apenas o conteúdo para SPA.
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? null; // Obtém o ID do usuário da sessão
        $userLevel = $_SESSION['user_level'] ?? 0; // Obtém o nível de acesso do usuário
        $hasAnuncio = false; // Valor padrão
        $existingAnuncio = null; // Inicializa como null

        $admsAnuncioModel = new AdmsAnuncio();

        if ($userId) {
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            $hasAnuncio = !empty($existingAnuncio);
            error_log("DEBUG CONTROLLER DASHBOARD: index() - User ID: " . $userId . ", Has Anuncio: " . ($hasAnuncio ? 'true' : 'false') . ", Anuncio Status: " . ($existingAnuncio['status'] ?? 'N/A'));
        } else {
            error_log("ERRO CONTROLLER DASHBOARD: index() - User ID não encontrado na sessão.");
        }

        $listAnuncios = [];
        $totalAnuncios = 0;
        $total_pages = 1;
        $limit = 5; // Limite de anúncios por página

        // Apenas carrega a lista de anúncios e estatísticas se o usuário for administrador (user_level >= 3)
        if ($userLevel >= 3) { 
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Usuário é ADMIN. Carregando dados da tabela de anúncios.");
            // Obtém os anúncios mais recentes para a tabela do administrador
            $listAnuncios = $admsAnuncioModel->getLatestAnuncios($this->page, $limit, $this->searchTerm, $this->filterStatus);
            
            // Obtém o total de anúncios para a paginação
            $totalAnuncios = $admsAnuncioModel->getTotalAnuncios($this->searchTerm, $this->filterStatus);
            $total_pages = ceil($totalAnuncios / $limit);
            if ($total_pages === 0) { // Evita divisão por zero se não houver anúncios
                $total_pages = 1;
            }
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Total Anúncios: " . $totalAnuncios . ", Total Páginas: " . $total_pages);
        } else {
            error_log("DEBUG CONTROLLER DASHBOARD: index() - Usuário não é ADMIN. Não carregando dados da tabela de anúncios.");
        }

        $this->data = [
            'user_data' => $this->userData,
            'sidebar_active' => 'dashboard', // Para marcar o item ativo na sidebar
            'dashboard_stats' => $this->getDashboardStats(), // Dados de exemplo
            'recent_activity' => $this->getRecentActivity(), // Dados de exemplo (será substituído pelos anúncios reais)
            'has_anuncio' => $hasAnuncio, // Adiciona se o usuário tem anúncio
            'anuncio_data' => $existingAnuncio, // ADICIONADO: Passa os dados completos do anúncio, incluindo o status
            'listAnuncios' => $listAnuncios, // Dados reais para a tabela de anúncios
            'pagination_data' => [ // Dados para a paginação
                'current_page' => $this->page,
                'total_pages' => $total_pages,
                'search_term' => $this->searchTerm,
                'filter_status' => $this->filterStatus
            ],
            'user_level' => $userLevel // Passa o nível do usuário para a view
        ];

        // Verifica se a requisição é AJAX (parâmetro 'ajax=true' na URL)
        $isAjaxRequest = (isset($_GET['ajax']) && $_GET['ajax'] === 'true');

        if ($isAjaxRequest) {
            // Se a requisição é AJAX, carrega APENAS o conteúdo da view
            $loadView = new ConfigViewAdm('adms/Views/dashboard/content_dashboard', $this->data);
            $loadView->loadContentView(); // Usa o método para carregar apenas o conteúdo
        } else {
            // Se a requisição NÃO é AJAX (carga inicial da página), carrega o layout completo
            $loadView = new ConfigViewAdm('adms/Views/dashboard/content_dashboard', $this->data);
            $loadView->loadView(); // Usa o método para carregar a view de conteúdo dentro do main.php
        }
    }

    private function redirect(string $url): void
    {
        header("Location: " . $url);
        exit();
    }

    // Funções de exemplo para obter dados do dashboard (podem ser substituídas por dados reais do DB)
    private function getDashboardStats(): array
    {
        // Em um ambiente real, você buscaria esses dados do banco de dados
        // Ex: total de visitas, total de pagamentos, taxa de aprovação, receita
        $admsAnuncioModel = new AdmsAnuncio();
        $totalAnuncios = $admsAnuncioModel->getTotalAnuncios('','all'); // Total de todos os anúncios
        $activeAnuncios = $admsAnuncioModel->getTotalAnuncios('','active');
        $pendingAnuncios = $admsAnuncioModel->getTotalAnuncios('','pending');

        $approvalRate = ($totalAnuncios > 0) ? round(($activeAnuncios / $totalAnuncios) * 100) : 0;

        return [
            'total_anuncios' => $totalAnuncios,
            'active_anuncios' => $activeAnuncios,
            'pending_anuncios' => $pendingAnuncios,
            'approval_rate' => $approvalRate . '%',
            // 'total_visits' => 12345, // Exemplo, buscar do DB
            // 'total_payments' => 3210, // Exemplo, buscar do DB
            // 'revenue' => 'R$48,245' // Exemplo, buscar do DB
        ];
    }

    private function getRecentActivity(): array
    {
        // Esta função não será mais usada para a tabela de anúncios,
        // pois getLatestAnuncios já faz isso.
        // Pode ser removida ou usada para outro tipo de "atividade recente"
        // que não seja a lista de anúncios.
        return []; 
    }
}
