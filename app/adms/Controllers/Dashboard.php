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

    public function __construct()
    {
        $this->verifySession();
        if (isset($_SESSION['usuario'])) {
            $this->userData = $_SESSION['usuario'];
        } else {
            $this->userData = []; 
        }
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
        $hasAnuncio = false; // Valor padrão

        if ($userId) {
            $admsAnuncioModel = new AdmsAnuncio();
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            $hasAnuncio = !empty($existingAnuncio);
            error_log("DEBUG CONTROLLER DASHBOARD: index() - User ID: " . $userId . ", Has Anuncio: " . ($hasAnuncio ? 'true' : 'false'));
        } else {
            error_log("ERRO CONTROLLER DASHBOARD: index() - User ID n\xc3\xa3o encontrado na sess\xc3\xa3o.");
        }

        $this->data = [
            'user_data' => $this->userData,
            'sidebar_active' => 'dashboard', // Para marcar o item ativo na sidebar
            'dashboard_stats' => $this->getDashboardStats(), // Dados de exemplo
            'recent_activity' => $this->getRecentActivity(), // Dados de exemplo
            'has_anuncio' => $hasAnuncio // Adiciona o status do anúncio aqui
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

    // Funções de exemplo para obter dados do dashboard
    private function getDashboardStats(): array
    {
        return [
            'total_visits' => 12345,
            'total_payments' => 3210,
            'approval_rate' => '87%',
            'revenue' => 'R$48,245'
        ];
    }

    private function getRecentActivity(): array
    {
        return [
            ['name' => 'Nicolas de Cristo Eurich', 'category' => 'Imóveis', 'status' => 'Ativo', 'visits' => 1245, 'date' => '15/12/2023'],
            ['name' => 'Adriano de Cristo Eurich', 'category' => 'Informática', 'status' => 'Pendente', 'visits' => 4245, 'date' => '15/12/2021'],
            ['name' => 'Marcia Cristina', 'category' => 'Automóvel', 'status' => 'Excluído', 'visits' => 2245, 'date' => '15/12/2021'],
            ['name' => 'Ana Cristina', 'category' => 'Telefonia', 'status' => 'Parado', 'visits' => 8245, 'date' => '15/12/2021'],
        ];
    }
}
