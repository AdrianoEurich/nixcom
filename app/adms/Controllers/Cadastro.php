<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: P\xc3\xa1gina n\xc3\xa3o encontrada!");
}

use Adms\Models\AdmsCadastro;
use Adms\CoreAdm\ConfigViewAdm;

class Cadastro
{
    private array $data = [];

    public function index(): void
    {
        // Inicia a sess\xc3\xa3o se ainda n\xc3\xa3o estiver iniciada (boa pr\xc3\xa1tica)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // NOVO: Se o usu\xc3\xa1rio j\xc3\xa1 estiver logado, redireciona para o dashboard
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $this->redirecionarParaDashboard();
        }

        // Recupera dados do formul\xc3\xa1rio da sess\xc3\xa3o (se houver erro na submiss\xc3\xa3o anterior sem AJAX)
        $this->data['form_data'] = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']); // Limpa ap\xc3\xb3s usar

        // Recupera mensagens da sess\xc3\xa3o
        $this->data['msg'] = $_SESSION['msg'] ?? [];
        unset($_SESSION['msg']); // Limpa ap\xc3\xb3s usar

        $loadView = new ConfigViewAdm("adms/Views/login/cadastro", $this->data);
        $loadView->loadViewLogin();
    }

    public function salvar(): void
    {
        // Define o cabe\xc3\xa7alho para indicar que a resposta ser\xc3\xa1 JSON
        header('Content-Type: application/json');

        // Inicializa a resposta padr\xc3\xa3o como erro
        $response = ['success' => false, 'message' => 'Erro desconhecido.'];

        // Inicia a sess\xc3\xa3o se ainda n\xc3\xa3o estiver iniciada (boa pr\xc3\xa1tica para usar $_POST)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se a requisi\xc3\xa7\xc3\xa3o \xc3\xa9 POST e se os dados de cadastro foram enviados
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cadastro'])) {
            $response['message'] = 'Erro: Nenhum dado recebido ou requisi\xc3\xa7\xc3\xa3o inv\xc3\xa1lida!';
            echo json_encode($response);
            exit();
        }

        // Filtra e sanitiza os dados recebidos do formul\xc3\xa1rio
        $cadastroData = filter_var_array($_POST['cadastro'], [
            'nome' => FILTER_SANITIZE_SPECIAL_CHARS,
            'email' => FILTER_SANITIZE_EMAIL,
            'senha' => FILTER_UNSAFE_RAW, 
            'confirmar_senha' => FILTER_UNSAFE_RAW 
        ]);

        // Instancia o modelo de cadastro
        $admsCadastro = new AdmsCadastro(); 

        // Tenta criar o cadastro
        if ($admsCadastro->create($cadastroData)) {
            // Se o cadastro for bem-sucedido
            $msg = $admsCadastro->getMsg(); 
            $response['success'] = true;
            $response['message'] = $msg['text'] ?? 'Cadastro realizado com sucesso! Fa\xc3\xa7a login para continuar.';
            $response['redirect'] = URLADM . "login"; 
        } else {
            // Se houver erro
            $msg = $admsCadastro->getMsg(); 
            $response['message'] = $msg['text'] ?? 'Erro ao cadastrar usu\xc3\xa1rio. Tente novamente.';
        }

        echo json_encode($response);
        exit(); 
    }

    /**
     * M\xc3\xa9todo auxiliar para redirecionar para o dashboard.
     */
    private function redirecionarParaDashboard(): void
    {
        header("Location: " . URLADM . "dashboard");
        exit();
    }
}
