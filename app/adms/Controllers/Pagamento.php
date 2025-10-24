<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Models\AdmsPlanos;
use Adms\Models\AdmsPagamento;

class Pagamento
{
    private array $data = [];

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(): void
    {
        // Se o usuário já estiver logado, redireciona para o dashboard
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            header("Location: " . URLADM . "dashboard");
            exit();
        }

        // Verificar se há um plano selecionado
        $planoTipo = $_GET['plan'] ?? $_SESSION['selected_plan'] ?? null;
        
        if (!$planoTipo || !in_array($planoTipo, ['basic', 'premium'])) {
            header("Location: " . URLADM . "cadastro");
            exit();
        }

        // Buscar dados do plano
        $admsPlanos = new AdmsPlanos();
        $plano = $admsPlanos->getPlanByType($planoTipo);
        
        if (!$plano) {
            header("Location: " . URLADM . "cadastro");
            exit();
        }

        $this->data = [
            'title' => 'Pagamento - ' . SITE_NAME,
            'favicon' => URLADM . 'assets/images/favicon.ico',
            'plano' => $plano,
            'plan_name' => $plano['nome'],
            'plan_price' => 'R$ ' . number_format($plano['preco_mensal'], 2, ',', '.') . '/mês'
        ];

        $loadView = new ConfigViewAdm("adms/Views/pagamento/pagamento", $this->data);
        $loadView->loadViewLogin();
    }

    public function processar(): void
    {
        // Define o cabeçalho para indicar que a resposta será JSON
        header('Content-Type: application/json');

        // Inicializa a resposta padrão como erro
        $response = ['success' => false, 'message' => 'Erro desconhecido.'];

        // Inicia a sessão se ainda não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response['message'] = 'Método não permitido';
            echo json_encode($response);
            return;
        }

        // Lê os dados JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            $response['message'] = 'Dados inválidos';
            echo json_encode($response);
            return;
        }

        // Validar dados obrigatórios
        $requiredFields = ['plano_id', 'periodo'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $response['message'] = "Campo obrigatório: {$field}";
                echo json_encode($response);
                return;
            }
        }

        // Validar período
        if (!in_array($data['periodo'], ['1_mes', '6_meses', '12_meses'])) {
            $response['message'] = 'Período inválido';
            echo json_encode($response);
            return;
        }

        try {
            // Verificar se usuário está logado
            if (!isset($_SESSION['user_id'])) {
                $response['message'] = 'Usuário não logado';
                echo json_encode($response);
                return;
            }

            // Criar pagamento PIX
            $admsPagamento = new AdmsPagamento();
            $paymentResult = $admsPagamento->createPixPayment(
                $_SESSION['user_id'],
                (int)$data['plano_id'],
                $data['periodo']
            );

            if ($paymentResult['success']) {
                $response = [
                    'success' => true,
                    'message' => 'Pagamento PIX criado com sucesso!',
                    'payment_id' => $paymentResult['payment_id'],
                    'qr_code' => $paymentResult['qr_code'],
                    'qr_code_base64' => $paymentResult['qr_code_base64'],
                    'pix_copy_paste' => $paymentResult['pix_copy_paste'],
                    'expires_at' => $paymentResult['expires_at']
                ];
            } else {
                $response['message'] = $paymentResult['message'];
            }

        } catch (\Exception $e) {
            error_log("Pagamento::processar - Erro: " . $e->getMessage());
            $response['message'] = 'Erro interno do servidor';
        }

        echo json_encode($response);
    }

    /**
     * Verifica status do pagamento
     */
    public function status(): void
    {
        header('Content-Type: application/json');
        
        $paymentId = $_GET['payment_id'] ?? null;
        
        if (!$paymentId) {
            echo json_encode(['success' => false, 'message' => 'ID do pagamento não fornecido']);
            return;
        }
        
        $admsPagamento = new AdmsPagamento();
        $result = $admsPagamento->checkPaymentStatus($paymentId);
        
        echo json_encode($result);
    }
    
    /**
     * Webhook para receber notificações do Mercado Pago
     */
    public function webhook(): void
    {
        $admsPagamento = new AdmsPagamento();
        $admsPagamento->webhook();
    }
}
