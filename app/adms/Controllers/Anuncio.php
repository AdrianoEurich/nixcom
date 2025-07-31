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
     * Verifica se o usuário logado é um administrador.
     * @return bool True se for administrador, false caso contrário.
     */
    private function isAdmin(): bool
    {
        return isset($_SESSION['user_id']) && ($_SESSION['user_level_numeric'] ?? 0) >= 3;
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

        // Verificar se o usuário já possui um anúncio (apenas anúncios NÃO deletados)
        $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId); // Este método já filtra deleted_at IS NULL
        
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
            $userId = $_SESSION['user_id'];
            $anuncioData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            $admsAnuncioModel = new AdmsAnuncio();

            if ($admsAnuncioModel->createAnuncio($anuncioData, $_FILES, $userId)) {
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

            if ($admsAnuncioModel->updateAnuncio($anuncioData, $_FILES, $anuncioId, $anuncioOwnerId)) { // Passa o ID do proprietário
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
        error_log("DEBUG CONTROLLER ANUNCIO: Método visualizarAnuncio() chamado.");

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("ERRO CONTROLLER ANUNCIO: visualizarAnuncio() - User ID não encontrado na sessão. Redirecionando para login.");
            // Se for AJAX, retorna JSON de erro
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || (isset($_GET['ajax_data_only']) && $_GET['ajax_data_only'] === 'true')) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Usuário não logado.']);
            }
            header("Location: " . URLADM . "login");
            exit();
        }

        $admsAnuncioModel = new AdmsAnuncio();

        // Se for admin e tiver um 'id' na URL, busca o anúncio por esse ID
        $anuncioIdFromUrl = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $existingAnuncio = null;

        if ($this->isAdmin() && $anuncioIdFromUrl) {
            // Admin pode visualizar qualquer anúncio pelo ID, incluindo os deletados
            $existingAnuncio = $admsAnuncioModel->getAnuncioById($anuncioIdFromUrl, true); 
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Admin acessando anúncio ID: {$anuncioIdFromUrl}.");
        } else {
            // Usuário normal (ou admin sem 'id' na URL) visualiza seu próprio anúncio
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Usuário ID: {$userId} acessando seu próprio anúncio.");
        }

        error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Resultado de getAnuncioByUserId/Id: " . ($existingAnuncio ? 'Anúncio encontrado' : 'Nenhum anúncio encontrado'));

        if ($existingAnuncio) {
            // Os dados já vêm do modelo AdmsAnuncio::getAnuncioByUserId formatados e com URLs completas
            $this->data['anuncio_data'] = $existingAnuncio;
            $this->data['has_anuncio'] = true;
            $this->data['anuncio_id'] = $existingAnuncio['id']; // Adiciona o ID do anúncio
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Anúncio encontrado e dados mapeados para a view. Anuncio Data (parcial): " . print_r(array_slice($this->data['anuncio_data'], 0, 5), true) . " - Gender: " . ($this->data['anuncio_data']['gender'] ?? 'N/A') . " - Phone: " . ($this->data['anuncio_data']['phone_number'] ?? 'N/A')); // Log parcial e campos específicos
        } else {
            $this->data['anuncio_data'] = []; // Garante que $anuncio_data esteja vazio se não houver anúncio
            $this->data['has_anuncio'] = false;
            $this->data['anuncio_id'] = null; // Garante que é null
            error_log("INFO CONTROLLER ANUNCIO: visualizarAnuncio() - Nenhum anúncio encontrado para visualização.");
        }

        // Sincroniza a sessão com o estado atual do anúncio
        $_SESSION['has_anuncio'] = $this->data['has_anuncio'];
        $_SESSION['anuncio_status'] = $existingAnuncio['status'] ?? 'not_found';
        $_SESSION['user_role'] = $_SESSION['user_role'] ?? ($this->isAdmin() ? 'admin' : 'normal'); // Garante que user_role esteja na sessão
        $_SESSION['anuncio_id'] = $this->data['anuncio_id']; // Adiciona o ID do anúncio à sessão
        error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Sessão atualizada: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", user_role=" . $_SESSION['user_role'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));

        $isDataOnlyAjaxRequest = (isset($_GET['ajax_data_only']) && $_GET['ajax_data_only'] === 'true');
        $isSpaAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isDataOnlyAjaxRequest) {
            // Se for uma requisição AJAX APENAS PARA DADOS (do anuncio.js, por exemplo)
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Requisição AJAX de dados. Retornando JSON.");
            if ($this->data['has_anuncio']) {
                $this->sendJsonResponse(['success' => true, 'anuncio' => $this->data['anuncio_data']]);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Nenhum anúncio encontrado para este usuário.']);
            }
        } elseif ($isSpaAjaxRequest) {
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Requisição SPA AJAX. Gerando HTML diretamente.");
            
            // Torna as variáveis do controlador disponíveis na view
            extract($this->data); 
            
            // Construção do caminho para a view (CORRIGIDO)
            $basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR; // app/adms/
            $viewRelativePath = 'Views' . DIRECTORY_SEPARATOR . 'anuncio' . DIRECTORY_SEPARATOR . 'visualizar_anuncio.php';
            $viewFullPath = $basePath . $viewRelativePath;

            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Caminho da View SPA: " . $viewFullPath);
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - file_exists(): " . (file_exists($viewFullPath) ? 'true' : 'false'));
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - is_readable(): " . (is_readable($viewFullPath) ? 'true' : 'false'));
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - realpath(): " . (realpath($viewFullPath) ?: 'N/A'));


            if (!file_exists($viewFullPath)) {
                error_log("ERRO CONTROLLER ANUNCIO: visualizarAnuncio() - Conteúdo da view para SPA não encontrado: " . $viewFullPath);
                echo "<!-- Erro: Conteúdo da view de visualização não encontrado. -->"; // Mensagem para debug no cliente
                exit();
            }

            // Inicia o buffer de saída para capturar o HTML da view
            ob_start();
            include $viewFullPath; // Inclui o conteúdo da view
            echo ob_get_clean(); // Obtém o conteúdo do buffer e o limpa, enviando-o como resposta
            exit(); // IMPORTANTE: Parar a execução aqui
        } else {
            // Lógica para requisição de página completa (HTML)
            $viewToLoad = "adms/Views/anuncio/visualizar_anuncio";
            if (!$this->data['has_anuncio']) {
                $_SESSION['msg'] = ['type' => 'info', 'text' => 'Você ainda não possui um anúncio para visualizar. Crie um primeiro!'];
                error_log("INFO CONTROLLER ANUNCIO: visualizarAnuncio() - Nenhum anúncio encontrado para visualização. Redirecionando para criação.");
                header("Location: " . URLADM . "anuncio/index");
                exit();
            }
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Requisição Full Page. Carregando View.");
            $loadView = new ConfigViewAdm($viewToLoad, $this->data);
            $loadView->loadView();
        }
    }

    /**
     * Método para pausar/ativar o anúncio do usuário logado (ação do próprio anunciante).
     * Espera uma requisição POST via AJAX.
     */
    public function pausarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método pausarAnuncio() (usuário) chamado.");
        
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
     * NOVO MÉTODO: Exclui o anúncio do usuário logado (soft delete).
     * Esta ação é para o próprio anunciante.
     * Espera uma requisição POST via AJAX.
     */
    public function deleteMyAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método deleteMyAnuncio() (usuário) chamado.");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(['success' => false, 'message' => 'É necessário estar logado para excluir um anúncio.']);
            }
            $userId = $_SESSION['user_id'];
            $admsAnuncioModel = new AdmsAnuncio();

            // Primeiro, verifica se o usuário realmente tem um anúncio para excluir (não deletado)
            $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
            if (!$existingAnuncio) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Você não possui um anúncio para excluir.']);
                return;
            }

            // Chama o método no modelo para excluir (soft delete) o anúncio do usuário logado
            // Passamos o ID do anúncio e o ID do usuário para validação no modelo
            if ($admsAnuncioModel->deleteAnuncio($existingAnuncio['id'], $userId)) {
                // Atualiza a sessão após a exclusão bem-sucedida
                $_SESSION['has_anuncio'] = false;
                $_SESSION['anuncio_status'] = 'not_found';
                $_SESSION['anuncio_id'] = null; // Remove o ID do anúncio da sessão
                error_log("DEBUG CONTROLLER ANUNCIO: deleteMyAnuncio() - Sessão atualizada após exclusão: has_anuncio=" . ($_SESSION['has_anuncio'] ? 'true' : 'false') . ", anuncio_status=" . $_SESSION['anuncio_status'] . ", anuncio_id=" . ($_SESSION['anuncio_id'] ?? 'null'));
                
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'],
                    'has_anuncio' => $_SESSION['has_anuncio'], // Envia o estado atualizado
                    'anuncio_status' => $_SESSION['anuncio_status'], // Envia o estado atualizado
                    'redirect' => URLADM . 'anuncio/index' // Redireciona para a página de criação de anúncio
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
        $this->handleAdminAction('active', 'Anúncio aprovado com sucesso!', true);
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
     * @param string $newStatus O novo status para o anúncio ('active', 'inactive', 'rejected', 'pending').
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
            if ($admsAnuncioModel->updateAnuncioStatus($anuncioId, $newStatus, $anuncianteUserId)) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $admsAnuncioModel->getMsg()['text'] ?? $successMessage,
                    'new_anuncio_status' => $newStatus, // Envia o novo status
                    'has_anuncio' => $hasAnuncio // Envia se tem anúncio (sempre true para esses status)
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
}
