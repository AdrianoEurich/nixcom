<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\Models\AdmsAnuncio;
use Adms\CoreAdm\ConfigViewAdm;

/**
 * Controller melhorado para visualização de anúncios
 * Versão simplificada e mais robusta
 */
class AnuncioMelhorado
{
    private array $data = [];

    public function __construct()
    {
        $this->verifySession();
    }

    /**
     * Verifica se o usuário está logado
     */
    private function verifySession(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['msg'] = ['type' => 'danger', 'text' => 'Acesso negado. Faça login para continuar.'];
            header("Location: " . URLADM . "login");
            exit();
        }
    }

    /**
     * Visualiza o anúncio do usuário logado ou de um anúncio específico (para admin)
     */
    public function visualizarAnuncio(): void
    {
        error_log("DEBUG ANUNCIO MELHORADO: Método visualizarAnuncio() chamado.");

        $userId = $_SESSION['user_id'];
        $admsAnuncioModel = new AdmsAnuncio();

        // Se for admin e tiver um 'id' na URL, busca o anúncio por esse ID
        $anuncioIdFromUrl = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $existingAnuncio = null;

        if ($this->isAdmin() && $anuncioIdFromUrl) {
            // Admin pode visualizar qualquer anúncio pelo ID
            $existingAnuncio = $admsAnuncioModel->getAnuncioById($anuncioIdFromUrl, true);
            error_log("DEBUG ANUNCIO MELHORADO: Admin acessando anúncio ID: {$anuncioIdFromUrl}.");
        } else {
            // Usuário normal visualiza seu próprio anúncio
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            error_log("DEBUG ANUNCIO MELHORADO: Usuário ID: {$userId} acessando seu próprio anúncio.");
        }

        if ($existingAnuncio) {
            $this->data['anuncio_data'] = $existingAnuncio;
            $this->data['has_anuncio'] = true;
            $this->data['anuncio_id'] = $existingAnuncio['id'];
            error_log("DEBUG ANUNCIO MELHORADO: Anúncio encontrado - ID: " . $existingAnuncio['id']);
        } else {
            $this->data['anuncio_data'] = [];
            $this->data['has_anuncio'] = false;
            $this->data['anuncio_id'] = null;
            error_log("DEBUG ANUNCIO MELHORADO: Nenhum anúncio encontrado.");
        }

        // Atualiza a sessão
        $_SESSION['has_anuncio'] = $this->data['has_anuncio'];
        $_SESSION['anuncio_status'] = $existingAnuncio['status'] ?? 'not_found';
        $_SESSION['anuncio_id'] = $this->data['anuncio_id'];

        // Verifica se é uma requisição AJAX
        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjaxRequest) {
            // Para requisições AJAX, carrega apenas o conteúdo
            $this->loadContentView();
        } else {
            // Para requisições normais, carrega a página completa
            $this->loadFullPage();
        }
    }

    /**
     * Carrega apenas o conteúdo da view (para AJAX)
     */
    private function loadContentView(): void
    {
        error_log("DEBUG ANUNCIO MELHORADO: Carregando conteúdo via AJAX.");
        
        if (!$this->data['has_anuncio']) {
            echo '<div class="alert alert-info text-center">Você ainda não possui um anúncio para visualizar. <a href="' . URLADM . 'anuncio/index">Crie um primeiro!</a></div>';
            return;
        }

        // Carrega a view melhorada
        $this->loadView('anuncio/visualizar_anuncio_melhorado');
    }

    /**
     * Carrega a página completa
     */
    private function loadFullPage(): void
    {
        error_log("DEBUG ANUNCIO MELHORADO: Carregando página completa.");
        
        if (!$this->data['has_anuncio']) {
            $_SESSION['msg'] = ['type' => 'info', 'text' => 'Você ainda não possui um anúncio para visualizar. Crie um primeiro!'];
            header("Location: " . URLADM . "anuncio/index");
            exit();
        }

        // Carrega a view melhorada
        $this->loadView('anuncio/visualizar_anuncio_melhorado');
    }

    /**
     * Carrega uma view específica
     */
    private function loadView(string $viewPath): void
    {
        $loadView = new ConfigViewAdm($viewPath, $this->data);
        $loadView->loadView();
    }

    /**
     * Verifica se o usuário é administrador
     */
    private function isAdmin(): bool
    {
        $userLevel = $_SESSION['user_level_numeric'] ?? 0;
        return $userLevel >= 3;
    }

    /**
     * Envia resposta JSON
     */
    private function sendJsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
