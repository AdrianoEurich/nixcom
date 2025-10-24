<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsAnuncio;
use Adms\Models\AdmsUser;
use Exception;

class Anuncio
{
    private array $data = [];

    /**
     * Construtor do controlador.
     * Garante que a sessão esteja iniciada e o usuário logado.
     */
    public function __construct()
    {
        // Inicia a sessão se ainda não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está logado e tem permissão (nível 1 para Dashboard)
        // Se não estiver logado ou não tiver permissão, redireciona para o login
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_level_numeric'] ?? 0) < 1) {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Para acessar a página de anúncio, faça login!'];
            header("Location: " . URLADM . "login/index");
            exit();
        }
    }

    /**
     * Helper para enviar respostas JSON e encerrar a execução.
     * @param array $response O array de dados a ser convertido para JSON.
     */
    private function sendJsonResponse(array $response): void
    {
        // Limpa qualquer buffer de saída anterior para evitar caracteres indesejados
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        // Adiciona JSON_UNESCAPED_UNICODE para evitar que caracteres UTF-8 sejam escapados
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }


    /**
     * Verifica o status atual do anúncio do usuário para atualização em tempo real
     */
    public function statusCheck(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não logado']);
            return;
        }

        try {
            $admsAnuncioModel = new AdmsAnuncio();
            
            // Buscar anúncio do usuário
            $anuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            
            if ($anuncio) {
                $this->sendJsonResponse([
                    'success' => true,
                    'has_anuncio' => true,
                    'anuncio_id' => $anuncio['id'],
                    'anuncio_status' => $anuncio['status'] ?? 'not_found',
                    'user_id' => $userId,
                    'timestamp' => time()
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => true,
                    'has_anuncio' => false,
                    'anuncio_id' => null,
                    'anuncio_status' => 'not_found',
                    'user_id' => $userId,
                    'timestamp' => time()
                ]);
            }
        } catch (Exception $e) {
            error_log("ERRO CONTROLLER ANUNCIO: statusCheck - " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Método para alternar o status do anúncio (pausar/ativar) via AJAX.
     * Recebe requisições POST com JSON.
     */
    public function toggleStatus(): void
    {
        // Verifica se é uma requisição AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        // Verifica se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        // Obtém o corpo da requisição JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $anuncioId = $input['anuncio_id'] ?? null;
        $newStatus = $input['new_status'] ?? null;

        if (!$anuncioId || !$newStatus) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Dados incompletos']);
            return;
        }

        // Valida o status
        $validStatuses = ['active', 'inactive'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Status inválido']);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não autenticado']);
            return;
        }

        try {
            $admsAnuncioModel = new AdmsAnuncio();
            
            // Verifica se o anúncio pertence ao usuário logado
            $anuncio = $admsAnuncioModel->getAnuncioById($anuncioId);
            if (!$anuncio || $anuncio['user_id'] != $userId) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Anúncio não encontrado ou não pertence ao usuário']);
                return;
            }

            // Executa a alteração do status
            $result = $admsAnuncioModel->toggleAnuncioStatus($userId);
            
            if ($result) {
                $action = ($newStatus === 'active') ? 'ativado' : 'pausado';
                $this->sendJsonResponse([
                    'success' => true, 
                    'message' => "Anúncio {$action} com sucesso!"
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false, 
                    'message' => $admsAnuncioModel->getMsg()['text'] ?? 'Erro ao alterar status do anúncio'
                ]);
            }

        } catch (Exception $e) {
            error_log("ERRO CONTROLLER ANUNCIO: toggleStatus - " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Verifica se o usuário logado é um administrador.
     * @return bool True se for administrador, false caso contrário.
     */
    private function isAdmin(): bool
    {
        return isset($_SESSION['user_id']) && ($_SESSION['user_level_numeric'] ?? 0) >= 3;
    }

    /**
     * Método para alternar o status do anúncio (pausar/ativar) via AJAX.
     * Recebe requisições POST com FormData.
     */
    public function toggleAnuncioStatus(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método toggleAnuncioStatus() chamado.");
        error_log("DEBUG CONTROLLER ANUNCIO: Método HTTP: " . $_SERVER['REQUEST_METHOD']);
        error_log("DEBUG CONTROLLER ANUNCIO: POST data: " . json_encode($_POST));
        
        // Verifica se é uma requisição AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            error_log("ERRO CONTROLLER ANUNCIO: toggleAnuncioStatus - Requisição não é AJAX");
            $this->sendJsonResponse(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        // Verifica se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("ERRO CONTROLLER ANUNCIO: toggleAnuncioStatus - Método não é POST");
            $this->sendJsonResponse(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

        error_log("DEBUG CONTROLLER ANUNCIO: toggleAnuncioStatus - user_id: " . $userId . ", action: " . $action);

        if (!$userId || !$action) {
            error_log("ERRO CONTROLLER ANUNCIO: toggleAnuncioStatus - Dados incompletos");
            $this->sendJsonResponse(['success' => false, 'message' => 'Dados incompletos']);
            return;
        }

        // Verifica se o usuário logado é o mesmo que está tentando alterar o anúncio
        if ($userId != ($_SESSION['user_id'] ?? null)) {
            error_log("ERRO CONTROLLER ANUNCIO: toggleAnuncioStatus - Acesso negado. User ID da sessão: " . ($_SESSION['user_id'] ?? 'null'));
            $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado']);
            return;
        }

        try {
            $admsAnuncioModel = new AdmsAnuncio();
            
            error_log("DEBUG CONTROLLER ANUNCIO: toggleAnuncioStatus - Chamando toggleAnuncioStatus no modelo");
            
            // Executa a alteração do status
            $result = $admsAnuncioModel->toggleAnuncioStatus($userId);
            
            error_log("DEBUG CONTROLLER ANUNCIO: toggleAnuncioStatus - Resultado do modelo: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                // Atualiza a sessão após pausar/ativar
                $latestAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
                error_log("DEBUG CONTROLLER ANUNCIO: toggleAnuncioStatus - latestAnuncio retornado: " . json_encode($latestAnuncio));
                
                $_SESSION['has_anuncio'] = !empty($latestAnuncio);
                $_SESSION['anuncio_status'] = $latestAnuncio['status'] ?? 'not_found';
                $_SESSION['anuncio_id'] = $latestAnuncio['id'] ?? null;
                
                error_log("DEBUG CONTROLLER ANUNCIO: toggleAnuncioStatus - Sessão atualizada: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));
                
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'new_anuncio_status' => $_SESSION['anuncio_status'],
                    'has_anuncio' => $_SESSION['has_anuncio'],
                    'anuncio_id' => $_SESSION['anuncio_id']
                ]);
            } else {
                error_log("ERRO CONTROLLER ANUNCIO: toggleAnuncioStatus - Falha no modelo: " . ($admsAnuncioModel->getMsg()['text'] ?? 'Erro desconhecido'));
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $admsAnuncioModel->getMsg()['text'] ?? 'Erro ao alterar status do anúncio'
                ]);
            }

        } catch (Exception $e) {
            error_log("ERRO CONTROLLER ANUNCIO: toggleAnuncioStatus - " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }

    /**
     * Método principal para criar/editar anúncio (formulário).
     * Carrega o formulário e os dados existentes se for uma edição.
     */
    public function index(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método index() (Criar/Editar Anúncio) chamado.");
        $viewToLoad = "adms/Views/anuncio/anuncio"; // Caminho da view do formulário

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("ERRO CONTROLLER ANUNCIO: index() - User ID não encontrado na sessão. Redirecionando.");
            header("Location: " . URLADM . "login");
            exit();
        }

        $admsAnuncioModel = new AdmsAnuncio();
        $admsUserModel = new AdmsUser();

        // Obter o tipo de plano do usuário
        $this->data['user_plan_type'] = $admsUserModel->getUserPlanType($userId);
        error_log("DEBUG CONTROLLER ANUNCIO: index() - User Plan Type: " . $this->data['user_plan_type']);
        error_log("DEBUG CONTROLLER ANUNCIO: index() - Data array completo: " . print_r($this->data, true));

        // Verificar se o usuário já possui um anúncio (apenas anúncios NÃO deletados)
        $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId); // Este método busca anúncio do usuário
        
        if ($existingAnuncio) {
            $this->data['has_anuncio'] = true;
            $this->data['anuncio_data'] = $existingAnuncio;
            $this->data['anuncio_id'] = $existingAnuncio['id']; // Adiciona o ID do anúncio
        } else {
            $this->data['has_anuncio'] = false;
            $this->data['anuncio_data'] = [];
            $this->data['anuncio_id'] = null; // Garante que é null se não houver anúncio
        }

        // Define o modo do formulário: 'create' ou 'edit'
        $this->data['form_mode'] = ($this->data['has_anuncio']) ? 'edit' : 'create';
        error_log("DEBUG CONTROLLER ANUNCIO: index() - Form Mode: " . $this->data['form_mode']);

        // Sincroniza a sessão com o estado atual do anúncio
        $_SESSION['has_anuncio'] = $this->data['has_anuncio'];
        $_SESSION['anuncio_status'] = $existingAnuncio['status'] ?? 'not_found';
        $_SESSION['user_role'] = $_SESSION['user_role'] ?? ($this->isAdmin() ? 'admin' : 'normal');
        $_SESSION['anuncio_id'] = $this->data['anuncio_id']; // Adiciona o ID do anúncio à sessão
        error_log("DEBUG CONTROLLER ANUNCIO: index() - Sessão atualizada: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", user_role=" . $_SESSION['user_role'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));

        // Determine se a requisição é AJAX (SPA).
        $isSpaAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $loadView = new ConfigViewAdm($viewToLoad, $this->data);

        if ($isSpaAjaxRequest) {
            error_log("DEBUG CONTROLLER ANUNCIO: index() - Requisição SPA AJAX. Carregando ContentView.");
            $loadView->loadContentView();
        } else {
            error_log("DEBUG CONTROLLER ANUNCIO: index() - Requisição Full Page. Carregando View.");
            $loadView->loadView();
        }
    }

    /**
     * Método para carregar o formulário com os dados do anúncio existente para edição.
     * Pode ser acessado por um usuário para editar seu próprio anúncio ou por um admin.
     */
    public function editarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método editarAnuncio() chamado.");
        $viewToLoad = "adms/Views/anuncio/anuncio"; // A mesma view do formulário

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("ERRO CONTROLLER ANUNCIO: editarAnuncio() - User ID não encontrado na sessão. Redirecionando para login.");
            // Se for AJAX, retorna JSON de erro
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || (isset($_GET['ajax_data_only']) && $_GET['ajax_data_only'] === 'true')) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não logado.']);
            }
            header("Location: " . URLADM . "login");
            exit();
        }

        $admsAnuncioModel = new AdmsAnuncio();
        $admsUserModel = new AdmsUser();

        // Obter o tipo de plano do usuário
        $this->data['user_plan_type'] = $admsUserModel->getUserPlanType($userId);
        error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - User Plan Type: " . $this->data['user_plan_type']);

        // Se for admin e tiver um 'id' na URL, busca o anúncio por esse ID
        $anuncioIdFromUrl = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $existingAnuncio = null;

        if ($this->isAdmin() && $anuncioIdFromUrl) {
            // Admin pode editar qualquer anúncio pelo ID, incluindo os deletados
            $existingAnuncio = $admsAnuncioModel->getAnuncioById($anuncioIdFromUrl, true); 
            // Se o anúncio for encontrado, o user_id do anunciante é o user_id do anúncio
            if ($existingAnuncio) {
                $this->data['anunciante_user_id'] = $existingAnuncio['user_id'];
                error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Admin acessando anúncio ID: {$anuncioIdFromUrl} do usuário ID: {$existingAnuncio['user_id']}.");
                error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Plan type do anunciante: " . ($existingAnuncio['plan_type'] ?? 'NÃO DEFINIDO'));
            } else {
                // Se o anúncio não for encontrado para o admin, e for uma requisição de dados, retorna JSON de erro
                if (isset($_GET['ajax_data_only']) && $_GET['ajax_data_only'] === 'true') {
                    $this->sendJsonResponse(['success' => false, 'message' => 'Anúncio não encontrado.']);
                }
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Anúncio não encontrado.'];
                error_log("INFO CONTROLLER ANUNCIO: editarAnuncio() - Admin tentou acessar anúncio ID: {$anuncioIdFromUrl} que não existe.");
                header("Location: " . URLADM . "dashboard"); // Redireciona admin para dashboard
                exit();
            }
        } else {
            // Usuário normal (ou admin sem 'id' na URL) edita seu próprio anúncio
            // Busca apenas anúncios NÃO deletados para o próprio usuário
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            if ($existingAnuncio) {
                $this->data['anunciante_user_id'] = $userId; // O próprio usuário
                error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Usuário ID: {$userId} acessando seu próprio anúncio.");
            }
        }
        
        error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Resultado de getAnuncioByUserId/Id: " . ($existingAnuncio ? 'Anúncio encontrado' : 'Nenhum anúncio encontrado'));

        if ($existingAnuncio) {
            $this->data['has_anuncio'] = true;
            $this->data['anuncio_data'] = $existingAnuncio;
            $this->data['form_mode'] = 'edit';
            $this->data['anuncio_id'] = $existingAnuncio['id']; // Adiciona o ID do anúncio
            
            // Buscar dados do usuário (anunciante) com informações de localização
            $anuncianteUserId = $existingAnuncio['user_id'];
            $anuncioId = $existingAnuncio['id'];
            $userData = $admsUserModel->getUserWithLocationData($anuncianteUserId, $anuncioId);
            if ($userData) {
                $this->data['anuncio_data']['nome'] = $userData['nome'] ?? 'N/A';
                $this->data['anuncio_data']['email'] = $userData['email'] ?? '';
                $this->data['anuncio_data']['estado'] = $userData['estado_nome'] ?? 'N/A';
                // Plan type para a seção "Informações do Plano"
                $this->data['anuncio_data']['plan_type'] = strtolower($userData['plan_type'] ?? ($this->data['anuncio_data']['plan_type'] ?? 'free'));
                error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Dados do usuário carregados: Nome=" . ($userData['nome'] ?? 'N/A') . ", Email=" . ($userData['email'] ?? '') . ", Estado=" . ($userData['estado_nome'] ?? 'N/A') . ", Plano=" . ($this->data['anuncio_data']['plan_type'] ?? 'free'));
            } else {
                $this->data['anuncio_data']['nome'] = 'N/A';
                $this->data['anuncio_data']['email'] = '';
                $this->data['anuncio_data']['estado'] = 'N/A';
                $this->data['anuncio_data']['plan_type'] = $this->data['anuncio_data']['plan_type'] ?? 'free';
                error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Dados do usuário não encontrados para ID: " . $anuncianteUserId);
            }
            
            error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Anúncio encontrado. Form Mode definido para 'edit'. Anuncio Data (parcial): " . print_r(array_slice($this->data['anuncio_data'], 0, 5), true)); // Log parcial
        } else {
            // Se não houver anúncio, e for uma requisição de dados, retorna JSON de erro
            if (isset($_GET['ajax_data_only']) && $_GET['ajax_data_only'] === 'true') {
                $this->sendJsonResponse(['success' => false, 'message' => 'Nenhum anúncio encontrado para este usuário.']);
            }
            // Se não for requisição de dados, redireciona para a página de criação de anúncio
            $_SESSION['msg'] = ['type' => 'info', 'text' => 'Você ainda não possui um anúncio para editar. Crie um primeiro!'];
            error_log("INFO CONTROLLER ANUNCIO: editarAnuncio() - Nenhum anúncio encontrado para edição. Redirecionando para criação.");
            header("Location: " . URLADM . "anuncio/index");
            exit();
        }

        // Sincroniza a sessão com o estado atual do anúncio
        $_SESSION['has_anuncio'] = $this->data['has_anuncio'];
        $_SESSION['anuncio_status'] = $existingAnuncio['status'] ?? 'not_found';
        $_SESSION['user_role'] = $_SESSION['user_role'] ?? ($this->isAdmin() ? 'admin' : 'normal'); // Garante que user_role esteja na sessão
        $_SESSION['anuncio_id'] = $this->data['anuncio_id']; // Adiciona o ID do anúncio à sessão
        
        // DEBUG: Log detalhado do status
        error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Status do anúncio no banco: " . ($existingAnuncio['status'] ?? 'NULL/VAZIO'));
        error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Status definido na sessão: " . $_SESSION['anuncio_status']);
        error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Sessão atualizada: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", user_role=" . $_SESSION['user_role'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));

        $isDataOnlyAjaxRequest = (isset($_GET['ajax_data_only']) && $_GET['ajax_data_only'] === 'true');
        $isSpaAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isDataOnlyAjaxRequest) {
            error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Requisição AJAX de dados. Retornando JSON.");
            if ($this->data['has_anuncio']) {
                $this->sendJsonResponse(['success' => true, 'anuncio' => $this->data['anuncio_data']]);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Nenhum anúncio encontrado para este usuário.']);
            }
        } elseif ($isSpaAjaxRequest) {
            error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Requisição SPA AJAX. Carregando ContentView.");
            $loadView = new ConfigViewAdm($viewToLoad, $this->data);
            $loadView->loadContentView();
        } else {
            error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Requisição Full Page. Carregando View.");
            $loadView = new ConfigViewAdm($viewToLoad, $this->data);
            $loadView->loadView();
        }
    }

    /**
     * Método para criar um novo anúncio no banco de dados.
     * Espera uma requisição POST via AJAX.
     */
    public function createAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método createAnuncio() chamado.");
        
        error_log('DEBUG PHP: Conteúdo de $_FILES: ' . print_r($_FILES, true));
        error_log('DEBUG PHP: Conteúdo de $_POST: ' . print_r($_POST, true));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(['success' => false, 'message' => 'É necessário estar logado para criar um anúncio.']);
            }
            
            // Validação CSRF - Temporariamente desabilitada para debug
            // require_once __DIR__ . '/../CoreAdm/Helpers/CsrfHelper.php';
            // $csrfToken = $_POST['csrf_token'] ?? '';
            // if (!\CoreAdm\Helpers\CsrfHelper::validateToken($csrfToken)) {
            //     $this->sendJsonResponse(['success' => false, 'message' => 'Token CSRF inválido']);
            // }
            
            $userId = $_SESSION['user_id'];
            $anuncioData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            
            // Fallback para $_POST se filter_input_array retornar null (usado em testes)
            if ($anuncioData === null || $anuncioData === false) {
                $anuncioData = $_POST;
                error_log("DEBUG CONTROLLER ANUNCIO: filter_input_array retornou null, usando \$_POST como fallback");
            }
            
            
            $admsAnuncioModel = new AdmsAnuncio();

            // Log personalizado para debug
            $debugLog = __DIR__ . '/debug_anuncio.log';
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: Chamando createAnuncio com userId: " . $userId . "\n", FILE_APPEND);
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: Dados enviados: " . print_r($anuncioData, true) . "\n", FILE_APPEND);
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: Files enviados: " . print_r($_FILES, true) . "\n", FILE_APPEND);
            
            // Debug específico dos arquivos obrigatórios
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: confirmation_video existe? " . (isset($_FILES['confirmation_video']) ? 'SIM' : 'NÃO') . "\n", FILE_APPEND);
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: foto_capa existe? " . (isset($_FILES['foto_capa']) ? 'SIM' : 'NÃO') . "\n", FILE_APPEND);
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: fotos_galeria_upload_0 existe? " . (isset($_FILES['fotos_galeria_upload_0']) ? 'SIM' : 'NÃO') . "\n", FILE_APPEND);
            
            if (isset($_FILES['confirmation_video'])) {
                file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: confirmation_video details: " . print_r($_FILES['confirmation_video'], true) . "\n", FILE_APPEND);
            }
            if (isset($_FILES['foto_capa'])) {
                file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: foto_capa details: " . print_r($_FILES['foto_capa'], true) . "\n", FILE_APPEND);
            }

            try {
                $userRole = $_SESSION['user_role'] ?? 'user';
                $result = $admsAnuncioModel->createAnuncio($anuncioData, $_FILES, $userId, $userRole);
                file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: Resultado do createAnuncio: " . ($result ? 'true' : 'false') . "\n", FILE_APPEND);
                
                if (!$result) {
                    file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: Erro na criação - Mensagem: " . print_r($admsAnuncioModel->getMsg(), true) . "\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: EXCEÇÃO capturada: " . $e->getMessage() . "\n", FILE_APPEND);
                file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] DEBUG CONTROLLER ANUNCIO: Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Erro interno: ' . $e->getMessage(),
                    'errors' => []
                ]);
                return;
            }

            if ($result) {
                // Atualiza a sessão após a criação bem-sucedida
                $latestAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
                $_SESSION['has_anuncio'] = !empty($latestAnuncio);
                $_SESSION['anuncio_status'] = $latestAnuncio['status'] ?? 'not_found';
                $_SESSION['anuncio_id'] = $latestAnuncio['id'] ?? null; // Adiciona o ID do anúncio criado à sessão
                error_log("DEBUG CONTROLLER ANUNCIO: createAnuncio() - Sessão atualizada após criação: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));

                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'anuncio_id' => $latestAnuncio['id'] ?? null, // Adiciona o ID do anúncio criado
                    'has_anuncio' => $_SESSION['has_anuncio'], // Garante que o JS receba o estado atualizado
                    'anuncio_status' => $_SESSION['anuncio_status'] // Garante que o JS receba o estado atualizado
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'errors' => $admsAnuncioModel->getMsg()['errors'] ?? []
                ]);
            }
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método de requisição inválido. Use POST.']);
        }
    }

    /**
     * Método para atualizar um anúncio existente no banco de dados.
     * Espera uma requisição POST via AJAX.
     */
    public function updateAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método updateAnuncio() chamado.");
        
        error_log('DEBUG PHP: Conteúdo de $_FILES: ' . print_r($_FILES, true));
        error_log('DEBUG PHP: Conteúdo de $_POST: ' . print_r($_POST, true));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(['success' => false, 'message' => 'É necessário estar logado para atualizar um anúncio.']);
            }
            $userId = $_SESSION['user_id'];
            $anuncioData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            $anuncioId = $anuncioData['anuncio_id'] ?? null;

            if (!$anuncioId) {
                $this->sendJsonResponse(['success' => false, 'message' => 'ID do anúncio não fornecido para atualização.']);
            }

            error_log("DEBUG CONTROLLER ANUNCIO: Dados recebidos para galeria:");
            error_log("DEBUG CONTROLLER ANUNCIO: existing_gallery_paths: " . print_r($anuncioData['existing_gallery_paths'] ?? [], true));
            error_log("DEBUG CONTROLLER ANUNCIO: removed_gallery_paths: " . print_r($anuncioData['removed_gallery_paths'] ?? [], true));
            error_log("DEBUG CONTROLLER ANUNCIO: fotos_galeria (novas): " . print_r($_FILES['fotos_galeria']['name'] ?? [], true));

            $admsAnuncioModel = new AdmsAnuncio();

            // NOVO: Verifica se o usuário é administrador ou se é o proprietário do anúncio
            $anuncioOwnerId = $admsAnuncioModel->getAnuncioOwnerId($anuncioId); // Método para buscar o ID do proprietário
            if (!$this->isAdmin() && $anuncioOwnerId !== $userId) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Você não tem permissão para editar este anúncio.']);
                exit();
            }

            $userRole = $_SESSION['user_role'] ?? 'user';
            if ($admsAnuncioModel->updateAnuncio($anuncioData, $_FILES, $anuncioId, $anuncioOwnerId, $userRole)) { // Passa o ID do proprietário
                // Atualiza a sessão após a atualização bem-sucedida
                $latestAnuncio = $admsAnuncioModel->getAnuncioByUserId($anuncioOwnerId); // Usa o ID do proprietário para buscar
                $_SESSION['has_anuncio'] = !empty($latestAnuncio);
                $_SESSION['anuncio_status'] = $latestAnuncio['status'] ?? 'not_found';
                $_SESSION['anuncio_id'] = $anuncioId; // Mantém o ID do anúncio atualizado na sessão
                error_log("DEBUG CONTROLLER ANUNCIO: updateAnuncio() - Sessão atualizada após atualização: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));

                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'anuncio_id' => $anuncioId, // Mantém o ID do anúncio atualizado
                    'has_anuncio' => $_SESSION['has_anuncio'], // Garante que o JS receba o estado atualizado
                    'anuncio_status' => $_SESSION['anuncio_status'] // Garante que o JS receba o estado atualizado
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'errors' => $admsAnuncioModel->getMsg()['errors'] ?? []
                ]);
            }
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método de requisição inválido. Use POST.']);
        }
    }

    /**
     * Método para visualizar os detalhes do anúncio do usuário logado ou de um anúncio específico (para admin).
     * Busca os dados do anúncio e os exibe em uma view somente leitura.
     * Retorna HTML para requisições SPA AJAX ou JSON para requisições de dados específicos.
     */
    public function visualizarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método visualizarAnuncio() chamado - Redirecionando para STS.");
        error_log("DEBUG CONTROLLER ANUNCIO: Timestamp: " . date('Y-m-d H:i:s'));

        $userId = $_SESSION['user_id'] ?? null;
        error_log("DEBUG CONTROLLER ANUNCIO: User ID da sessão: " . ($userId ?? 'NULL'));
        
        if (!$userId) {
            error_log("ERRO CONTROLLER ANUNCIO: visualizarAnuncio() - User ID não encontrado na sessão. Redirecionando para login.");
            header("Location: " . URLADM . "login");
            exit();
        }

        $anuncioIdFromUrl = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        // Determinar qual anúncio buscar
        if ($this->isAdmin() && $anuncioIdFromUrl) {
            // Admin visualiza anúncio específico
            $anuncioId = $anuncioIdFromUrl;
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Admin redirecionando para STS anúncio ID: {$anuncioId}.");
        } else {
            // Usuário normal visualiza seu próprio anúncio
            $admsAnuncioModel = new AdmsAnuncio();
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            if (!$existingAnuncio) {
                error_log("ERRO CONTROLLER ANUNCIO: visualizarAnuncio() - Usuário não possui anúncio.");
                header("Location: " . URLADM . "anuncio");
                exit();
            }
            $anuncioId = $existingAnuncio['id'];
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Usuário redirecionando para STS anúncio ID: {$anuncioId}.");
        }
        
        // Redirecionar para a visualização STS
        $stsUrl = URL . "anuncio/visualizar/{$anuncioId}";
        error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Redirecionando para: {$stsUrl}");
        
        // Forçar redirecionamento para fora do SPA
        echo "<script>window.location.href = '{$stsUrl}';</script>";
        echo "<meta http-equiv='refresh' content='0;url={$stsUrl}'>";
        exit();
    }

    /**
     * Método para pausar/ativar o anúncio do usuário logado (ação do próprio anunciante).
     * Espera uma requisição POST via AJAX.
     */
    public function pausarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método pausarAnuncio() (usuário) chamado.");
        error_log("DEBUG CONTROLLER ANUNCIO: Método HTTP: " . $_SERVER['REQUEST_METHOD']);
        error_log("DEBUG CONTROLLER ANUNCIO: Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(['success' => false, 'message' => 'É necessário estar logado para pausar/ativar um anúncio.']);
            }
            $userId = $_SESSION['user_id'];
            $admsAnuncioModel = new AdmsAnuncio();

            // Chama o método no modelo para pausar/ativar o anúncio do usuário logado
            if ($admsAnuncioModel->toggleAnuncioStatus($userId)) { // Novo método no modelo para alternar
                // Atualiza a sessão após pausar/ativar
                $latestAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
                $_SESSION['has_anuncio'] = !empty($latestAnuncio);
                $_SESSION['anuncio_status'] = $latestAnuncio['status'] ?? 'not_found';
                $_SESSION['anuncio_id'] = $latestAnuncio['id'] ?? null; // Adiciona o ID do anúncio à sessão
                error_log("DEBUG CONTROLLER ANUNCIO: pausarAnuncio() - Sessão atualizada após operação: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));
                
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'new_anuncio_status' => $_SESSION['anuncio_status'], // Envia o novo status
                    'has_anuncio' => $_SESSION['has_anuncio'] // Envia se tem anúncio
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $admsAnuncioModel->getMsg()['text']
                ]);
            }
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método de requisição inválido. Use POST.']);
        }
    }

    /**
     * Método para aprovar um anúncio (ação do administrador).
     * Requer que o usuário logado seja um administrador.
     * Espera um POST com 'anuncio_id' e 'anunciante_user_id' no corpo da requisição (FormData).
     */
    public function approveAnuncio(): void
    {
        $this->handleAdminAction('active', 'Anúncio aprovado e ativado com sucesso!', true);
    }

    /**
     * Método para rejeitar um anúncio (ação do administrador).
     * Requer que o usuário logado seja um administrador.
     * Espera um POST com 'anuncio_id' e 'anunciante_user_id' no corpo da requisição (FormData).
     */
    public function rejectAnuncio(): void
    {
        $this->handleAdminAction('rejected', 'Anúncio reprovado com sucesso!', true);
    }

    /**
     * Método para ativar um anúncio (ação do administrador).
     * Requer que o usuário logado seja um administrador.
     * Espera um POST com 'anuncio_id' e 'anunciante_user_id' no corpo da requisição (FormData).
     */
    public function activateAnuncio(): void
    {
        $this->handleAdminAction('active', 'Anúncio ativado com sucesso!', true);
    }

    /**
     * Método para pausar um anúncio (ação do administrador).
     * Requer que o usuário logado seja um administrador.
     * Espera um POST com 'anuncio_id' e 'anunciante_user_id' no corpo da requisição (FormData).
     */
    public function deactivateAnuncio(): void
    {
        $this->handleAdminAction('inactive', 'Anúncio pausado com sucesso!', true);
    }

    /**
     * Método para deletar um anúncio (ação do administrador - soft delete).
     * Requer que o usuário logado seja um administrador.
     * Espera um POST com 'anuncio_id' e 'anunciante_user_id' no corpo da requisição (FormData).
     */
    public function deleteAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método deleteAnuncio() (admin) chamado.");
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->isAdmin()) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem excluir anúncios.']);
                exit(); // Adicionado exit() para garantir que a execução pare aqui
            }

            $anuncioId = filter_input(INPUT_POST, 'anuncio_id', FILTER_VALIDATE_INT);
            $anuncianteUserId = filter_input(INPUT_POST, 'anunciante_user_id', FILTER_VALIDATE_INT);

            if (!$anuncioId || !$anuncianteUserId) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Dados inválidos para exclusão do anúncio.']);
                exit(); // Adicionado exit()
            }

            $admsAnuncioModel = new AdmsAnuncio();
            // Chama o método deleteAnuncio do modelo (que realiza o soft delete)
            if ($admsAnuncioModel->deleteAnuncio($anuncioId, $anuncianteUserId)) {
                // Não atualiza a sessão do admin, mas informa o JS para redirecionar
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'new_anuncio_status' => 'deleted', // Indica que o anúncio foi deletado
                    'has_anuncio' => true, // O anunciante ainda "tem" o registro do anúncio, mas ele está deletado
                    'redirect' => URLADM . 'dashboard' // Redireciona o admin para a dashboard
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $admsAnuncioModel->getMsg()['text']
                ]);
            }
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método de requisição inválido. Use POST.']);
        }
    }

    /**
     * Método auxiliar privado para lidar com ações de status do administrador.
     * @param string $newStatus O novo status para o anúncio ('active', 'pausado', 'approved', 'rejected', 'pending').
     * @param string $successMessage Mensagem de sucesso para a resposta JSON.
     * @param bool $hasAnuncio Novo valor para has_anuncio na tabela do usuário (sempre true para esses status).
     */
    private function handleAdminAction(string $newStatus, string $successMessage, bool $hasAnuncio): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: handleAdminAction() chamado para status: {$newStatus}.");
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->isAdmin()) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem realizar esta ação.']);
                exit(); // Adicionado exit()
            }

            $anuncioId = filter_input(INPUT_POST, 'anuncio_id', FILTER_VALIDATE_INT);
            $anuncianteUserId = filter_input(INPUT_POST, 'anunciante_user_id', FILTER_VALIDATE_INT);

            if (!$anuncioId || !$anuncianteUserId) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Dados inválidos para a ação do anúncio.']);
                exit(); // Adicionado exit()
            }

            $admsAnuncioModel = new AdmsAnuncio();
            error_log("DEBUG CONTROLLER ANUNCIO: handleAdminAction - Antes de updateAnuncioStatus - anuncioId: {$anuncioId}, newStatus: {$newStatus}, anuncianteUserId: {$anuncianteUserId}");
            
            if ($admsAnuncioModel->updateAnuncioStatus($anuncioId, $newStatus, $anuncianteUserId)) {
                error_log("DEBUG CONTROLLER ANUNCIO: handleAdminAction - updateAnuncioStatus retornou true");
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'] ?? $successMessage,
                    'new_anuncio_status' => $newStatus, // Envia o novo status
                    'has_anuncio' => $hasAnuncio // Envia se tem anúncio (sempre true para esses status)
                ]);
            } else {
                error_log("DEBUG CONTROLLER ANUNCIO: handleAdminAction - updateAnuncioStatus retornou false");
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $admsAnuncioModel->getMsg()['text']
                ]);
            }
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método de requisição inválido. Use POST.']);
        }
    }

    /**
     * Método para ações de administrador (aprovar, reprovar, ativar, pausar anúncios)
     * Espera uma requisição POST via AJAX com JSON.
     */
    public function adminAction(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método adminAction() chamado.");
        error_log("DEBUG CONTROLLER ANUNCIO: REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("DEBUG CONTROLLER ANUNCIO: REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        error_log("DEBUG CONTROLLER ANUNCIO: SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
        error_log("DEBUG CONTROLLER ANUNCIO: HTTP_HOST: " . $_SERVER['HTTP_HOST']);
        
        // Remover o teste e implementar a lógica real
        
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar se é admin
            if (!isset($_SESSION['user_level_numeric']) || $_SESSION['user_level_numeric'] < 3) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem realizar esta ação.']);
                return;
            }

            // Obter dados do JSON
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Dados inválidos.']);
                return;
            }

            $action = $data['action'] ?? '';
            $anuncioId = $data['anuncio_id'] ?? null;
            $anuncianteUserId = $data['anunciante_user_id'] ?? null;

            error_log("DEBUG CONTROLLER ANUNCIO: adminAction - Action: {$action}, AnuncioId: {$anuncioId}, AnuncianteUserId: {$anuncianteUserId}");

            if (empty($action) || empty($anuncioId)) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Parâmetros obrigatórios não fornecidos.']);
                return;
            }

            $admsAnuncioModel = new AdmsAnuncio();
            $successMessage = '';

            error_log("DEBUG CONTROLLER ANUNCIO: Executando ação: {$action} para anúncio ID: {$anuncioId}");

            switch ($action) {
                case 'approve':
                    $successMessage = 'Anúncio aprovado com sucesso!';
                    // Regra: para basic/premium exige pagamento 'paid' antes de ativar
                    try {
                        $admsUserModel = new AdmsUser();
                        $u = $admsUserModel->getUserById((int)$anuncianteUserId);
                        $planType = strtolower($u['plan_type'] ?? 'free');
                        $paymentStatus = strtolower($u['payment_status'] ?? 'pending');
                        if (in_array($planType, ['basic','premium'], true) && $paymentStatus !== 'paid') {
                            $this->sendJsonResponse(['success' => false, 'message' => 'Pagamento ainda não aprovado. Para planos pagos, o pagamento deve estar concluído para ativar o anúncio.']);
                            return;
                        }
                    } catch (\Exception $e) { error_log('WARN: Falha ao validar pagamento antes de approve: ' . $e->getMessage()); }
                    error_log("DEBUG CONTROLLER ANUNCIO: Chamando updateAnuncioStatus com status: active");
                    $result = $admsAnuncioModel->updateAnuncioStatus($anuncioId, 'active', $anuncianteUserId);
                    error_log("DEBUG CONTROLLER ANUNCIO: Resultado updateAnuncioStatus: " . ($result ? 'true' : 'false'));
                    break;
                
                case 'reject':
                    $successMessage = 'Anúncio reprovado com sucesso!';
                    error_log("DEBUG CONTROLLER ANUNCIO: Chamando updateAnuncioStatus com status: rejected");
                    $result = $admsAnuncioModel->updateAnuncioStatus($anuncioId, 'rejected', $anuncianteUserId);
                    error_log("DEBUG CONTROLLER ANUNCIO: Resultado updateAnuncioStatus: " . ($result ? 'true' : 'false'));
                    break;
                
                case 'activate':
                    $successMessage = 'Anúncio ativado com sucesso!';
                    // Regra: para basic/premium exige pagamento 'paid' antes de ativar
                    try {
                        $admsUserModel = new AdmsUser();
                        $u = $admsUserModel->getUserById((int)$anuncianteUserId);
                        $planType = strtolower($u['plan_type'] ?? 'free');
                        $paymentStatus = strtolower($u['payment_status'] ?? 'pending');
                        if (in_array($planType, ['basic','premium'], true) && $paymentStatus !== 'paid') {
                            $this->sendJsonResponse(['success' => false, 'message' => 'Pagamento ainda não aprovado. Para planos pagos, o pagamento deve estar concluído para ativar o anúncio.']);
                            return;
                        }
                    } catch (\Exception $e) { error_log('WARN: Falha ao validar pagamento antes de activate: ' . $e->getMessage()); }
                    error_log("DEBUG CONTROLLER ANUNCIO: Chamando updateAnuncioStatus com status: active");
                    $result = $admsAnuncioModel->updateAnuncioStatus($anuncioId, 'active', $anuncianteUserId);
                    error_log("DEBUG CONTROLLER ANUNCIO: Resultado updateAnuncioStatus: " . ($result ? 'true' : 'false'));
                    break;
                
                case 'deactivate':
                    $successMessage = 'Anúncio pausado com sucesso!';
                    error_log("DEBUG CONTROLLER ANUNCIO: Chamando updateAnuncioStatus com status: pausado");
                    $result = $admsAnuncioModel->updateAnuncioStatus($anuncioId, 'pausado', $anuncianteUserId);
                    error_log("DEBUG CONTROLLER ANUNCIO: Resultado updateAnuncioStatus: " . ($result ? 'true' : 'false'));
                    break;
                
                default:
                    $this->sendJsonResponse(['success' => false, 'message' => 'Ação inválida.']);
                    return;
            }

            if ($result) {
                // Aguardar um pouco para garantir que a transação foi commitada
                usleep(100000); // 100ms
                
                // Verificação direta no banco para debug
                try {
                    $pdo = new \PDO("mysql:host=localhost;dbname=nixcom", "root", "");
                    $stmt = $pdo->prepare("SELECT status FROM anuncios WHERE id = ?");
                    $stmt->execute([$anuncioId]);
                    $directResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                    error_log("DEBUG CONTROLLER ANUNCIO: Verificação direta no banco - Status: " . ($directResult['status'] ?? 'NULL'));
                } catch (Exception $e) {
                    error_log("DEBUG CONTROLLER ANUNCIO: Erro na verificação direta: " . $e->getMessage());
                }
                
                $newStatus = $admsAnuncioModel->getAnuncioStatus($anuncioId);
                error_log("DEBUG CONTROLLER ANUNCIO: Novo status obtido via modelo: " . ($newStatus ?? 'null'));
                
                // Verificar se o status foi realmente atualizado
                $expectedStatus = ($data['action'] === 'deactivate') ? 'pausado' : (($data['action'] === 'activate') ? 'active' : $newStatus);
                if ($newStatus !== $expectedStatus) {
                    error_log("DEBUG CONTROLLER ANUNCIO: Status não corresponde à ação esperada. Ação: " . $data['action'] . ", Status esperado: " . $expectedStatus . ", Status atual: " . $newStatus);
                }
                
                // Atualizar a sessão do usuário anunciante se for o próprio usuário logado
                if ($anuncianteUserId && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $anuncianteUserId) {
                    $_SESSION['anuncio_status'] = $newStatus;
                    error_log("DEBUG CONTROLLER ANUNCIO: Sessão do usuário atualizada - novo status: " . $newStatus);
                }
                
                // Sempre atualizar a sessão do administrador com o novo status para a dashboard
                $_SESSION['admin_last_action_status'] = $newStatus;
                $_SESSION['admin_last_action_anuncio_id'] = $anuncioId;
                
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $successMessage,
                    'new_status' => $newStatus
                ]);
            } else {
                $errorMsg = $admsAnuncioModel->getMsg()['text'] ?? 'Erro ao executar ação.';
                error_log("DEBUG CONTROLLER ANUNCIO: Erro na execução: " . $errorMsg);
                
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $errorMsg
                ]);
            }
        } else {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método de requisição inválido. Use POST.']);
        }
    }
}
