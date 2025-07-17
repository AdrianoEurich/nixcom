<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsAnuncio;
use Adms\Models\AdmsUser;

class Anuncio
{
    private array $data = [];

    public function index(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método index() (Criar/Editar Anúncio) chamado.");
        $viewToLoad = "adms/Views/anuncio/anuncio";

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

        // Verificar se o usuário já possui um anúncio
        $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
        $this->data['has_anuncio'] = !empty($existingAnuncio); // Define has_anuncio
        $this->data['anuncio_data'] = $existingAnuncio; // Passa os dados do anúncio se existir
        error_log("DEBUG CONTROLLER ANUNCIO: index() - Has Anuncio: " . ($this->data['has_anuncio'] ? 'true' : 'false'));

        // Define o modo do formulário: 'create' ou 'edit'
        $this->data['form_mode'] = ($this->data['has_anuncio']) ? 'edit' : 'create'; 
        error_log("DEBUG CONTROLLER ANUNCIO: index() - Form Mode: " . $this->data['form_mode']);


        // Determine se a requisição é AJAX.
        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                             || (isset($_GET['ajax']) && $_GET['ajax'] === 'true');

        $loadView = new ConfigViewAdm($viewToLoad, $this->data);

        if ($isAjaxRequest) {
            error_log("DEBUG CONTROLLER ANUNCIO: index() - Requisição AJAX. Carregando ContentView.");
            $loadView->loadContentView(); 
        } else {
            error_log("DEBUG CONTROLLER ANUNCIO: index() - Requisição Full Page. Carregando View.");
            $loadView->loadView();     
        }
    }

    /**
     * Método para carregar o formulário com os dados do anúncio existente para edição.
     */
    public function editarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método editarAnuncio() chamado.");
        $viewToLoad = "adms/Views/anuncio/anuncio"; // A mesma view do formulário

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("ERRO CONTROLLER ANUNCIO: editarAnuncio() - User ID não encontrado na sessão. Redirecionando para login.");
            header("Location: " . URLADM . "login");
            exit();
        }

        $admsAnuncioModel = new AdmsAnuncio();
        $admsUserModel = new AdmsUser();

        // Obter o tipo de plano do usuário
        $this->data['user_plan_type'] = $admsUserModel->getUserPlanType($userId);
        error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - User Plan Type: " . $this->data['user_plan_type']);

        // Buscar o anúncio existente do usuário
        $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
        error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Resultado de getAnuncioByUserId: " . ($existingAnuncio ? 'Anúncio encontrado' : 'Nenhum anúncio encontrado'));

        if ($existingAnuncio) {
            $this->data['has_anuncio'] = true; // Define has_anuncio
            $this->data['anuncio_data'] = $existingAnuncio;
            $this->data['form_mode'] = 'edit'; // Define o modo do formulário como 'edit'
            error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Anúncio encontrado. Form Mode definido para 'edit'. Anuncio Data: " . print_r($this->data['anuncio_data'], true));
        } else {
            // Se não houver anúncio, redireciona para a página de criação de anúncio
            $_SESSION['msg'] = ['type' => 'info', 'text' => 'Você ainda não possui um anúncio para editar. Crie um primeiro!'];
            error_log("INFO CONTROLLER ANUNCIO: editarAnuncio() - Nenhum anúncio encontrado para edição. Redirecionando para criação.");
            header("Location: " . URLADM . "anuncio/index");
            exit();
        }

        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                             || (isset($_GET['ajax']) && $_GET['ajax'] === 'true');

        $loadView = new ConfigViewAdm($viewToLoad, $this->data);

        if ($isAjaxRequest) {
            error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Requisição AJAX. Carregando ContentView.");
            $loadView->loadContentView();
        } else {
            error_log("DEBUG CONTROLLER ANUNCIO: editarAnuncio() - Requisição Full Page. Carregando View.");
            $loadView->loadView();
        }
    }

    /**
     * Método para salvar um novo anúncio no banco de dados.
     * Espera uma requisição POST via AJAX.
     */
    public function salvarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método salvarAnuncio() chamado.");
        header('Content-Type: application/json; charset=UTF-8'); 
        $response = ['success' => false, 'message' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'É necessário estar logado para criar um anúncio.';
                error_log("ERRO CONTROLLER ANUNCIO: salvarAnuncio() - User ID não encontrado na sessão.");
                echo json_encode($response);
                exit();
            }
            $userId = $_SESSION['user_id'];
            $anuncioData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            $admsAnuncioModel = new AdmsAnuncio();

            if ($admsAnuncioModel->createAnuncio($anuncioData, $_FILES, $userId)) {
                $response['success'] = true;
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                // Redireciona para a página de edição após a criação
                $response['redirect'] = URLADM . 'anuncio/editarAnuncio'; 
                error_log("DEBUG CONTROLLER ANUNCIO: salvarAnuncio() - Anúncio criado com sucesso. Redirecionando para edição.");
            } else {
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                if (isset($admsAnuncioModel->getMsg()['errors'])) {
                    $response['errors'] = $admsAnuncioModel->getMsg()['errors'];
                }
                error_log("ERRO CONTROLLER ANUNCIO: salvarAnuncio() - Falha ao criar anúncio. Mensagem: " . $response['message']);
            }
        } else {
            $response['message'] = 'Método de requisição inválido. Use POST.';
            error_log("ERRO CONTROLLER ANUNCIO: salvarAnuncio() - Método de requisição inválido: " . $_SERVER['REQUEST_METHOD']);
        }

        echo json_encode($response);
        exit(); 
    }

    /**
     * Método para atualizar um anúncio existente no banco de dados.
     * Espera uma requisição POST via AJAX.
     */
    public function atualizarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método atualizarAnuncio() chamado.");
        header('Content-Type: application/json; charset=UTF-8'); 
        $response = ['success' => false, 'message' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'É necessário estar logado para atualizar um anúncio.';
                error_log("ERRO CONTROLLER ANUNCIO: atualizarAnuncio() - User ID não encontrado na sessão.");
                echo json_encode($response);
                exit();
            }
            $userId = $_SESSION['user_id'];
            $anuncioData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            $anuncioId = $anuncioData['anuncio_id'] ?? null; // Obtém o ID do anúncio do formulário

            if (!$anuncioId) {
                $response['message'] = 'ID do anúncio não fornecido para atualização.';
                error_log("ERRO CONTROLLER ANUNCIO: atualizarAnuncio() - ID do anúncio não fornecido.");
                echo json_encode($response);
                exit();
            }

            $admsAnuncioModel = new AdmsAnuncio();

            // Chamar o método de atualização no modelo
            if ($admsAnuncioModel->updateAnuncio($anuncioData, $_FILES, $anuncioId, $userId)) {
                $response['success'] = true;
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                // Redireciona para a própria página de edição após a atualização
                $response['redirect'] = URLADM . 'anuncio/editarAnuncio'; 
                error_log("DEBUG CONTROLLER ANUNCIO: atualizarAnuncio() - Anúncio atualizado com sucesso.");
            } else {
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                if (isset($admsAnuncioModel->getMsg()['errors'])) {
                    $response['errors'] = $admsAnuncioModel->getMsg()['errors'];
                }
                error_log("ERRO CONTROLLER ANUNCIO: atualizarAnuncio() - Falha ao atualizar anúncio. Mensagem: " . $response['message']);
            }
        } else {
            $response['message'] = 'Método de requisição inválido. Use POST.';
            error_log("ERRO CONTROLLER ANUNCIO: atualizarAnuncio() - Método de requisição inválido: " . $_SERVER['REQUEST_METHOD']);
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Método para visualizar os detalhes do anúncio do usuário logado.
     * Busca os dados do anúncio e os exibe em uma view somente leitura.
     */
    public function visualizarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método visualizarAnuncio() chamado.");
        $viewToLoad = "adms/Views/anuncio/visualizar_anuncio"; // Nova view para visualização

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("ERRO CONTROLLER ANUNCIO: visualizarAnuncio() - User ID não encontrado na sessão. Redirecionando para login.");
            header("Location: " . URLADM . "login");
            exit();
        }

        $admsAnuncioModel = new AdmsAnuncio();
        
        // Buscar o anúncio existente do usuário
        $existingAnuncio = $admsAnuncioModel->getAnuncioByUserId($userId);
        error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Resultado de getAnuncioByUserId: " . ($existingAnuncio ? 'Anúncio encontrado' : 'Nenhum anúncio encontrado'));

        if ($existingAnuncio) {
            $this->data['anuncio_data'] = $existingAnuncio;
            $this->data['has_anuncio'] = true; // ATUALIZADO AQUI: Define has_anuncio para a view
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Anúncio encontrado para visualização. Anuncio Data: " . print_r($this->data['anuncio_data'], true));
        } else {
            // Se não houver anúncio, redireciona para a página de criação de anúncio
            $_SESSION['msg'] = ['type' => 'info', 'text' => 'Você ainda não possui um anúncio para visualizar. Crie um primeiro!'];
            error_log("INFO CONTROLLER ANUNCIO: visualizarAnuncio() - Nenhum anúncio encontrado para visualização. Redirecionando para criação.");
            header("Location: " . URLADM . "anuncio/index");
            exit();
        }

        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                             || (isset($_GET['ajax']) && $_GET['ajax'] === 'true');

        $loadView = new ConfigViewAdm($viewToLoad, $this->data);

        if ($isAjaxRequest) {
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Requisição AJAX. Carregando ContentView.");
            $loadView->loadContentView();
        } else {
            error_log("DEBUG CONTROLLER ANUNCIO: visualizarAnuncio() - Requisição Full Page. Carregando View.");
            $loadView->loadView();
        }
    }

    /**
     * Método para pausar o anúncio do usuário logado.
     * Espera uma requisição POST via AJAX.
     */
    public function pausarAnuncio(): void
    {
        error_log("DEBUG CONTROLLER ANUNCIO: Método pausarAnuncio() chamado.");
        header('Content-Type: application/json; charset=UTF-8'); 
        $response = ['success' => false, 'message' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'É necessário estar logado para pausar um anúncio.';
                error_log("ERRO CONTROLLER ANUNCIO: pausarAnuncio() - User ID não encontrado na sessão.");
                echo json_encode($response);
                exit();
            }
            $userId = $_SESSION['user_id'];
            $admsAnuncioModel = new AdmsAnuncio();

            // Chama o método no modelo para pausar o anúncio
            if ($admsAnuncioModel->pauseAnuncio($userId)) {
                $response['success'] = true;
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                error_log("DEBUG CONTROLLER ANUNCIO: pausarAnuncio() - Anúncio pausado com sucesso.");
            } else {
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                error_log("ERRO CONTROLLER ANUNCIO: pausarAnuncio() - Falha ao pausar anúncio. Mensagem: " . $response['message']);
            }
        } else {
            $response['message'] = 'Método de requisição inválido. Use POST.';
            error_log("ERRO CONTROLLER ANUNCIO: pausarAnuncio() - Método de requisição inválido: " . $_SERVER['REQUEST_METHOD']);
        }

        echo json_encode($response);
        exit();
    }
}
