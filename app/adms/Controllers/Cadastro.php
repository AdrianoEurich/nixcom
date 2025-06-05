<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\Models\AdmsCadastro;
use Adms\CoreAdm\ConfigViewAdm;

class Cadastro
{
    private array $data = [];

    public function index(): void
    {
        // Inicia a sessão se ainda não estiver iniciada (boa prática)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Recupera dados do formulário da sessão (se houver erro na submissão anterior sem AJAX)
        // Isso é mantido para casos de acesso direto ou submit não-AJAX por algum motivo.
        $this->data['form_data'] = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']); // Limpa após usar

        // Recupera mensagens da sessão
        $this->data['msg'] = $_SESSION['msg'] ?? [];
        unset($_SESSION['msg']); // Limpa após usar

        $loadView = new ConfigViewAdm("adms/Views/login/cadastro", $this->data);
        $loadView->loadViewLogin();
    }

    public function salvar(): void
    {
        // Define o cabeçalho para indicar que a resposta será JSON
        header('Content-Type: application/json');

        // Inicializa a resposta padrão como erro
        $response = ['success' => false, 'message' => 'Erro desconhecido.'];

        // Inicia a sessão se ainda não estiver iniciada (boa prática para usar $_POST)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se a requisição é POST e se os dados de cadastro foram enviados
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cadastro'])) {
            $response['message'] = 'Erro: Nenhum dado recebido ou requisição inválida!';
            echo json_encode($response);
            exit();
        }

        // Filtra e sanitiza os dados recebidos do formulário
        $cadastroData = filter_var_array($_POST['cadastro'], [
            'nome' => FILTER_SANITIZE_SPECIAL_CHARS,
            'email' => FILTER_SANITIZE_EMAIL,
            'senha' => FILTER_UNSAFE_RAW, // Senha não deve ser sanitizada, apenas hash
            'confirmar_senha' => FILTER_UNSAFE_RAW // Confirmar senha também
        ]);

        // Instancia o modelo de cadastro
        $admsCadastro = new AdmsCadastro(); // Renomeado para seguir o padrão

        // Tenta criar o cadastro
        if ($admsCadastro->create($cadastroData)) {
            // Se o cadastro for bem-sucedido
            $msg = $admsCadastro->getMsg(); // Pega a mensagem da Model
            $response['success'] = true;
            $response['message'] = $msg['text'] ?? 'Cadastro realizado com sucesso! Faça login para continuar.';
            $response['redirect'] = URLADM . "login"; // Opcional: Sugere redirecionamento ao JS
        } else {
            // Se houver erro
            $msg = $admsCadastro->getMsg(); // Pega a mensagem de erro da Model
            $response['message'] = $msg['text'] ?? 'Erro ao cadastrar usuário. Tente novamente.';
            // Não é necessário armazenar form_data na sessão para AJAX, o JS mantém o estado do formulário.
        }

        echo json_encode($response);
        exit(); // Garante que nenhum HTML extra seja enviado
    }
}