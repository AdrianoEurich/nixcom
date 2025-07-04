<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsAnuncio; // Certifique-se de que este Model exista e esteja correto

class Anuncio
{
    private array $data = [];

    public function index(): void
    {
        // Define o nome da view relativa à pasta 'app/'
        // Ex: para carregar app/adms/Views/anuncio/anuncio.php, passe "adms/Views/anuncio/anuncio"
        $viewToLoad = "adms/Views/anuncio/anuncio";

        // 1. **Verificação Crucial:** Determine se a requisição é AJAX.
        // Use o cabeçalho X-Requested-With (comum para jQuery AJAX) ou seu parâmetro GET 'ajax=true'.
        $isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                         || (isset($_GET['ajax']) && $_GET['ajax'] === 'true');

        // Instancia o ConfigViewAdm com o caminho da view e os dados
        $loadView = new ConfigViewAdm($viewToLoad, $this->data);

        // Decide qual método de carregamento usar com base na requisição
        if ($isAjaxRequest) {
            $loadView->loadContentView(); // Para requisições AJAX (SPA), carrega APENAS o conteúdo
        } else {
            $loadView->loadView();       // Para requisições normais (primeira carga), carrega DENTRO do main.php
        }
    }

    /**
     * Método para salvar um novo anúncio no banco de dados.
     * Espera uma requisição POST via AJAX.
     */
    public function salvarAnuncio(): void
    {
        // Garante que a resposta seja JSON
        header('Content-Type: application/json');

        // Inicializa a resposta padrão
        $response = ['success' => false, 'message' => ''];

        // Verifica se a requisição é um POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verifica se o usuário está logado e obtém o ID do usuário
            // ATENÇÃO: Substitua 'user_id' pela chave real que você usa na sessão para o ID do usuário
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'É necessário estar logado para criar um anúncio.';
                echo json_encode($response);
                exit();
            }
            $userId = $_SESSION['user_id'];

            // Filtra os dados POST para segurança básica
            // FILTER_DEFAULT é um bom ponto de partida, mas considere filtros mais específicos para cada campo.
            $anuncioData = filter_input_array(INPUT_POST, FILTER_DEFAULT);

            // Instancia o modelo AdmsAnuncio
            // Certifique-se de que a classe AdmsAnuncio (em app/adms/Models/AdmsAnuncio.php) existe e está configurada.
            $admsAnuncioModel = new AdmsAnuncio();

            // Tenta criar o anúncio, passando os dados POST, FILES e o ID do usuário
            if ($admsAnuncioModel->createAnuncio($anuncioData, $_FILES, $userId)) {
                $response['success'] = true;
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                // Opcional: Você pode adicionar um redirecionamento ou ação para o frontend aqui
                // $response['redirect'] = URLADM . 'meus-anuncios';
            } else {
                $response['message'] = $admsAnuncioModel->getMsg()['text'];
                // Se houver erros específicos por campo (do validateInput no model)
                if (isset($admsAnuncioModel->getMsg()['errors'])) {
                    $response['errors'] = $admsAnuncioModel->getMsg()['errors'];
                }
            }
        } else {
            // Se não for uma requisição POST
            $response['message'] = 'Método de requisição inválido. Use POST.';
        }

        // Retorna a resposta JSON para o frontend
        echo json_encode($response);
        exit(); // Garante que nenhum outro conteúdo seja enviado
    }
}
