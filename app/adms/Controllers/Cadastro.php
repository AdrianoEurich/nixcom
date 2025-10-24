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

        // NOVO: Se o usuário já estiver logado, redireciona baseado no nível de acesso
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $this->redirecionarBaseadoNoNivel();
        }

        // Recupera dados do formulário da sessão (se houver erro na submissão anterior sem AJAX)
        $this->data['form_data'] = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']); // Limpa após usar

        // Recupera mensagens da sessão
        $this->data['msg'] = $_SESSION['msg'] ?? [];
        unset($_SESSION['msg']); // Limpa após usar

        // Recupera plano selecionado da sessão
        $this->data['selected_plan'] = $_SESSION['selected_plan'] ?? 'free';

        $loadView = new ConfigViewAdm("adms/Views/cadastro/cadastro", $this->data);
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

        // Verifica se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response['message'] = 'Método não permitido';
            echo json_encode($response);
            exit();
        }

        // Verifica se os dados foram enviados via JSON ou POST tradicional
        $cadastroData = null;
        
        if (isset($_POST['cadastro'])) {
            // Dados via POST tradicional
            $cadastroData = filter_var_array($_POST['cadastro'], [
                'nome' => FILTER_SANITIZE_SPECIAL_CHARS,
                'email' => FILTER_SANITIZE_EMAIL,
                'telefone' => FILTER_SANITIZE_SPECIAL_CHARS,
                'cpf' => FILTER_SANITIZE_SPECIAL_CHARS,
                'senha' => FILTER_UNSAFE_RAW, 
                'confirmar_senha' => FILTER_UNSAFE_RAW,
                'plan_type' => FILTER_SANITIZE_SPECIAL_CHARS
            ]);
        } else {
            // Dados via JSON
            $input = file_get_contents('php://input');
            $jsonData = json_decode($input, true);
            
            if ($jsonData && isset($jsonData['cadastro'])) {
                $cadastroData = $jsonData['cadastro'];
            }
        }

        if (!$cadastroData) {
            $response['message'] = 'Nenhum dado de cadastro recebido';
            echo json_encode($response);
            exit();
        }

        // Salvar o plano selecionado na sessão para uso posterior
        if (isset($cadastroData['plan_type'])) {
            $_SESSION['selected_plan'] = $cadastroData['plan_type'];
        }

        // Importante: não fechar a sessão antes da criação do usuário, pois o modelo pode gravar dados na sessão

        // Instancia o modelo de cadastro
        $admsCadastro = new AdmsCadastro(); 

        // Tenta criar o cadastro
        if ($admsCadastro->create($cadastroData)) {
            // Se o cadastro for bem-sucedido
            $msg = $admsCadastro->getMsg(); 
            $response['success'] = true;
            $response['message'] = $msg['text'] ?? 'Cadastro realizado com sucesso!';
            
            // Determinar redirecionamento baseado no plano
            $planType = $cadastroData['plan_type'] ?? 'free';
            
            if ($planType === 'free') {
                // Plano gratuito - redirecionar para dashboard (pode criar anúncio imediatamente)
                $response['redirect'] = URLADM . "dashboard/index";
                $response['plan_type'] = 'free';
            } else if ($planType === 'basic') {
                // Plano básico - redirecionar para dashboard primeiro, depois pagamento
                $response['redirect'] = URLADM . "dashboard/index";
                $response['plan_type'] = 'basic';
            } else if ($planType === 'premium') {
                // Plano premium - redirecionar para dashboard primeiro, depois pagamento
                $response['redirect'] = URLADM . "dashboard/index";
                $response['plan_type'] = 'premium';
            } else {
                // Fallback - redirecionar para dashboard
                $response['redirect'] = URLADM . "dashboard/index";
                $response['plan_type'] = 'free';
            } 
        } else {
            // Se houver erro
            $msg = $admsCadastro->getMsg(); 
            $response['message'] = $msg['text'] ?? 'Erro ao cadastrar usuário. Tente novamente.';
        }

        // Após montar a resposta, agora sim podemos fechar a sessão para evitar locks
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        echo json_encode($response);
        exit(); 
    }

    /**
     * Método auxiliar para redirecionar baseado no nível de acesso do usuário.
     */
    private function redirecionarBaseadoNoNivel(): void
    {
        $userLevel = $_SESSION['user_level'] ?? 'usuario';
        
        if ($userLevel === 'administrador') {
            // Administrador vai para o painel administrativo de usuários
            header("Location: " . URLADM . "admin-users");
        } else {
            // Usuário comum vai para o dashboard de usuários
            header("Location: " . URLADM . "dashboard");
        }
        exit();
    }

    /**
     * Método auxiliar para redirecionar para o dashboard (mantido para compatibilidade).
     */
    private function redirecionarParaDashboard(): void
    {
        header("Location: " . URLADM . "dashboard");
        exit();
    }
}
