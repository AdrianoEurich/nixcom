<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Incluir modelos necessários
require_once 'app/adms/Config/MercadoPagoConfig.php';
require_once 'app/adms/Models/AdmsSubscription.php';
require_once 'app/adms/Models/AdmsMercadoPago.php';
require_once 'app/adms/Models/AdmsNotificacao.php';

use Adms\Models\AdmsSubscription;
use Adms\Models\AdmsMercadoPago;
use Adms\Models\AdmsNotificacao;
use Adms\Models\AdmsPaymentLog;
use PDO;
use PDOException;
class PaymentController
{
    private AdmsSubscription $subscriptionModel;
    private AdmsMercadoPago $mercadoPagoModel;
    private AdmsNotificacao $notificationModel;
    private AdmsPaymentLog $paymentLogModel;
    private array $data = [];

    public function __construct()
    {
        $this->subscriptionModel = new AdmsSubscription();
        $this->mercadoPagoModel = new AdmsMercadoPago();
        $this->notificationModel = new AdmsNotificacao();
        $this->paymentLogModel = new AdmsPaymentLog();
        
        // Criar tabela de logs se não existir
        $this->paymentLogModel->createTable();
    }

    /**
     * Endpoint de desenvolvimento para simular aprovação de pagamento
     * Somente disponível em sandbox. Atualiza a assinatura para aprovada/ativa.
     * URL: /adms/payment/devApprove?subscription_id=123
     */
    public function devApprove(): void
    {
        // Limpar output e setar JSON
        if (ob_get_level()) { ob_clean(); }
        header('Content-Type: application/json');

        try {
            // Permitir apenas em sandbox
            $isSandbox = \Adms\Config\MercadoPagoConfig::isSandbox();
            if (!$isSandbox) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Endpoint disponível apenas em sandbox.']);
                return;
            }

            $subscriptionId = isset($_GET['subscription_id']) ? (int)$_GET['subscription_id'] : 0;
            if ($subscriptionId <= 0) {
                echo json_encode(['success' => false, 'message' => 'subscription_id inválido']);
                return;
            }

            // Buscar assinatura
            $subscription = $this->subscriptionModel->getSubscriptionById($subscriptionId);
            if (!$subscription) {
                echo json_encode(['success' => false, 'message' => 'Assinatura não encontrada']);
                return;
            }

            // Marcar como aprovada/ativa
            $this->subscriptionModel->approveSubscription($subscriptionId);

            // Opcional: notificar admins no sandbox também
            try { $this->notifyAdminsPaymentReceived($subscription); } catch (\Throwable $t) {}

            echo json_encode(['success' => true, 'status' => 'approved', 'message' => 'Assinatura aprovada (simulação).']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtém conexão com o banco de dados
     */
    private function getConnection(): PDO
    {
        try {
            $host = 'localhost';
            $dbname = 'nixcom';
            $username = 'root';
            $password = '';
            
            $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            error_log("PaymentController::getConnection - Erro de conexão: " . $e->getMessage());
            throw new \Exception("Erro de conexão com o banco de dados");
        }
    }

    /**
     * Página principal de pagamento
     */
    public function index(): void
    {
        $plan = $_GET['plan'] ?? 'basic';
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            $urlAdm = defined('URLADM') ? URLADM : '/adms/';
            header("Location: " . $urlAdm . "login");
            exit();
        }

        // Buscar dados do plano
        $planoData = $this->getPlanoData($plan);
        if (!$planoData) {
            $this->showError("Plano não encontrado");
            return;
        }

        // Verificar se já existe assinatura pendente
        $existingSubscription = $this->subscriptionModel->getActiveSubscription($userId);
        if ($existingSubscription && $existingSubscription['status'] === 'pending') {
            $this->loadPaymentView($existingSubscription, $planoData);
            return;
        }

        $this->loadPaymentView(null, $planoData);
    }

    /**
     * Cria nova assinatura e gera pagamento PIX real
     */
    public function create(): void
    {
        // Desabilitar exibição de erros
        error_reporting(0);
        ini_set('display_errors', 0);
        
        // Limpar qualquer output anterior
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
                return;
            }

            // Obter dados de entrada
            $input = null;
            $rawInput = file_get_contents('php://input');
            if ($rawInput) {
                $input = json_decode($rawInput, true);
            }
            
            if (!$input && !empty($_POST)) {
                $input = $_POST;
            }
            
            if (!$input) {
                echo json_encode(['success' => false, 'message' => 'Dados de entrada não encontrados']);
                return;
            }
            
            $planoId = $input['plano_id'] ?? 1;
            $period = $input['period'] ?? '6_meses';
            $planType = $input['plan_type'] ?? 'basic';

            // Buscar dados do plano
            $planoData = $this->getPlanoDataById($planoId);
            if (!$planoData) {
                echo json_encode(['success' => false, 'message' => 'Plano não encontrado']);
                return;
            }

            // Calcular valor baseado no período
            $amount = $this->calculateAmount($planoData, $period);
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valor do plano inválido. Verifique os preços do plano.']);
                return;
            }
            
            // Criar assinatura no banco
            $subscriptionId = $this->subscriptionModel->createSubscription(
                $userId, 
                $planoId, 
                $period, 
                $amount, 
                null
            );

            if (!$subscriptionId) {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar assinatura']);
                return;
            }

            // Normalizar encoding do nome do plano para UTF-8 (evitar 'B?sico') e gerar versão ASCII segura
            $planoNome = $planoData['nome'] ?? 'Plano';
            $planoNome = mb_convert_encoding($planoNome, 'UTF-8', 'UTF-8, ISO-8859-1, ASCII');
            $planoNome = @iconv('UTF-8', 'UTF-8//IGNORE', $planoNome);
            if ($planoNome === false || $planoNome === null || $planoNome === '') {
                $planoNome = 'Plano';
            }
            // Versão ASCII transliterada para uso na descrição do MP (evita caracteres especiais)
            $planoNomeAscii = @iconv('UTF-8', 'ASCII//TRANSLIT', $planoNome);
            if ($planoNomeAscii === false || $planoNomeAscii === null) {
                $planoNomeAscii = 'Plano';
            }
            // Remover quaisquer caracteres residuais não suportados
            $planoNomeAscii = preg_replace('/[^A-Za-z0-9 _\-]/', '', $planoNomeAscii);
            if ($planoNomeAscii === '') {
                $planoNomeAscii = 'Plano';
            }
            // Evitar duplicação de palavra 'Plano' e aplicar mapeamento simples por tipo
            $tipoPlano = $planoData['tipo'] ?? null;
            if ($tipoPlano === 'basic') {
                $planoNomeAscii = 'Plano Basico';
            } elseif ($tipoPlano === 'premium') {
                $planoNomeAscii = 'Plano Premium';
            } else {
                // Se já começar com 'Plano ', manter apenas um prefixo
                $planoNomeAscii = preg_replace('/^\s*Plano\s+/i', 'Plano ', $planoNomeAscii);
                if (stripos($planoNomeAscii, 'Plano ') !== 0) {
                    $planoNomeAscii = 'Plano ' . $planoNomeAscii;
                }
            }

            // Preparar dados para Mercado Pago
            $notificationUrl = \Adms\Config\MercadoPagoConfig::getWebhookUrl();
            // Em sandbox (XAMPP), não enviar notification_url para funcionar só com polling
            $isSandbox = \Adms\Config\MercadoPagoConfig::isSandbox();
            $isValidWebhook = !$isSandbox && filter_var($notificationUrl, FILTER_VALIDATE_URL) && (stripos($notificationUrl, 'localhost') === false);
            
            $paymentData = [
                'amount' => $amount,
                // Usar versão ASCII segura na descrição enviada ao MP (sem duplicar 'Plano')
                'description' => "Pagamento {$planoNomeAscii} - {$period}",
                'payer_email' => $_SESSION['user_email'] ?? 'usuario@exemplo.com',
                'payer_name' => $_SESSION['user_name'] ?? 'Usuário',
                'external_reference' => "subscription_{$subscriptionId}",
                'plan_type' => $planType,
                'user_id' => $userId
            ];
            if ($isValidWebhook) {
                $paymentData['notification_url'] = $notificationUrl;
            }

            // Debug no error_log: payload a ser enviado ao Mercado Pago (sem dados sensíveis)
            try {
                error_log('PaymentController::create - Payload (sanitized): ' . json_encode([
                    'transaction_amount' => (float)$paymentData['amount'],
                    'description' => $paymentData['description'],
                    'payer' => [
                        'email' => $paymentData['payer_email'],
                        'first_name' => $paymentData['payer_name']
                    ],
                    'external_reference' => $paymentData['external_reference'],
                    'has_notification_url' => isset($paymentData['notification_url'])
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } catch (\Throwable $t) {
                // ignore
            }

            // Criar pagamento PIX real via Mercado Pago
            $mercadoPagoResponse = $this->mercadoPagoModel->createPixPayment($paymentData);
            
            if ($mercadoPagoResponse) {
                // Log do pagamento criado
                $this->paymentLogModel->logPayment([
                    'user_id' => $userId,
                    'payment_id' => $mercadoPagoResponse['payment_id'],
                    'subscription_id' => $subscriptionId,
                    'action' => 'payment_created',
                    'status' => $mercadoPagoResponse['status'],
                    'amount' => $amount,
                    'plan_type' => $planType,
                    'payment_method' => 'pix',
                    'external_reference' => $paymentData['external_reference'],
                    'mercado_pago_data' => $mercadoPagoResponse
                ]);
                
                // Atualizar assinatura com dados do pagamento
                $this->subscriptionModel->updateQRCode(
                    $subscriptionId,
                    $mercadoPagoResponse['qr_code_base64'] ?? '',
                    $mercadoPagoResponse['qr_text'] ?? ''
                );

                // Persistir referência do pagamento (payment_id) e status pendente na assinatura
                $this->subscriptionModel->updateSubscription($subscriptionId, [
                    'provider_ref' => $mercadoPagoResponse['payment_id'],
                    'status' => 'pending'
                ]);

                // Retornar dados do pagamento
                echo json_encode([
                    'success' => true,
                    'subscription_id' => $subscriptionId,
                    'payment_id' => $mercadoPagoResponse['payment_id'],
                    'qr_code_base64' => $mercadoPagoResponse['qr_code_base64'],
                    'pix_copy_paste' => $mercadoPagoResponse['qr_text'],
                    'amount' => $amount,
                    'description' => $paymentData['description'],
                    'status' => $mercadoPagoResponse['status'],
                    'expires_at' => $mercadoPagoResponse['date_of_expiration']
                ]);
            } else {
                // Log detalhado temporário para depuração: gravar payload e resposta (quando nula)
                try {
                    $debugData = [
                        'user_id' => $userId,
                        'subscription_id' => $subscriptionId,
                        'payment_payload' => $paymentData,
                        'mercado_response' => $mercadoPagoResponse,
                        'timestamp' => date('c')
                    ];

                    // Além do arquivo, registrar um resumo no error_log (acessível)
                    error_log('PaymentController::create - MP creation failed. subscription_id=' . $subscriptionId . ' description=' . ($paymentData['description'] ?? ''));            

                    // Inserir no payment_logs também
                    $this->paymentLogModel->logPayment(array_merge([
                        'user_id' => $userId,
                        // Evitar NOT NULL violation em payment_id
                        'payment_id' => 'ERROR',
                        'subscription_id' => $subscriptionId,
                        'action' => 'payment_creation_no_response',
                        'status' => 'no_response',
                        'amount' => $amount,
                        'plan_type' => $planType,
                        'payment_method' => 'pix',
                        'external_reference' => $paymentData['external_reference']
                    ], ['debug' => $debugData]));

                    // Gravar arquivo temporário em logs/ para análise (remover depois)
                    $logsDir = __DIR__ . '/../../../logs';
                    if (!is_dir($logsDir)) {
                        @mkdir($logsDir, 0777, true);
                    }
                    $debugFile = $logsDir . '/payment_debug_' . time() . '_' . rand(1000,9999) . '.log';
                    @file_put_contents($debugFile, json_encode($debugData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    error_log('PaymentController::create - debug file written: ' . $debugFile);
                } catch (\Exception $e) {
                    error_log('PaymentController::create - failed to write debug file: ' . $e->getMessage());
                }

                // Log do erro
                $this->paymentLogModel->logPayment([
                    'user_id' => $userId,
                    // Evitar NOT NULL violation em payment_id
                    'payment_id' => 'ERROR',
                    'subscription_id' => $subscriptionId,
                    'action' => 'payment_creation_failed',
                    'status' => 'failed',
                    'amount' => $amount,
                    'plan_type' => $planType,
                    'payment_method' => 'pix',
                    'error_message' => 'Erro ao criar pagamento PIX via Mercado Pago'
                ]);
                
                // Marcar assinatura como failed para não acumular pendentes órfãs
                try {
                    $this->subscriptionModel->updateSubscription($subscriptionId, [
                        'status' => 'failed',
                        'provider_ref' => null
                    ]);
                } catch (\Throwable $t) {
                    error_log('PaymentController::create - failed to mark subscription as failed: ' . $t->getMessage());
                }
                
                // Retornar info de ambiente em sandbox para auxiliar debug (sem segredos)
                $envInfo = \Adms\Config\MercadoPagoConfig::getEnvironmentInfo();
                if ($envInfo['is_sandbox'] ?? false) {
                    echo json_encode(['success' => false, 'message' => 'Erro ao criar pagamento PIX', 'env' => [
                        'is_sandbox' => true,
                        'base_url' => $envInfo['base_url'] ?? null,
                        'has_token' => $envInfo['has_token'] ?? null
                    ]]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao criar pagamento PIX']);
                }
            }

        } catch (\Exception $e) {
            error_log("PaymentController::create - Erro: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
    }

    /**
     * Busca dados do plano por ID
     */
    private function getPlanoDataById(int $planoId): ?array
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SELECT * FROM planos WHERE id = ?");
            $stmt->execute([$planoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("PaymentController::getPlanoDataById - Erro: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcula valor baseado no plano e período
     */
    private function calculateAmount(array $planoData, string $period): float
    {
        $basePrice = (float) $planoData['preco_mensal'];
        
        switch ($period) {
            case '1_mes':
                return $basePrice;
            case '3_meses':
                return $basePrice * 3 * 0.9; // 10% desconto
            case '6_meses':
                return $basePrice * 6 * 0.85; // 15% desconto
            case '12_meses':
                return $basePrice * 12 * 0.8; // 20% desconto
            default:
                return $basePrice;
        }
    }

    /**
     * Verifica status do pagamento
     */
    public function status(): void
    {
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        try {
            $subscriptionId = $_GET['subscription_id'] ?? null;
            if (!$subscriptionId) {
                echo json_encode(['success' => false, 'message' => 'ID da assinatura não fornecido']);
                return;
            }

            // Buscar assinatura para obter o payment_id persistido
            $subscription = $this->subscriptionModel->getSubscriptionById((int)$subscriptionId);
            if (!$subscription) {
                echo json_encode(['success' => false, 'message' => 'Assinatura não encontrada']);
                return;
            }

            // Atender imediatamente com base no status local da assinatura
            $localStatus = $subscription['status'] ?? 'pending';
            if ($localStatus === 'paid_awaiting_admin') {
                echo json_encode(['success' => true, 'status' => 'paid_awaiting_admin', 'message' => 'Pagamento confirmado! Aguardando aprovação.']);
                return;
            }
            if ($localStatus === 'active') {
                echo json_encode(['success' => true, 'status' => 'approved', 'message' => 'Pagamento aprovado!']);
                return;
            }

            $paymentId = $subscription['provider_ref'] ?? null;
            if (!$paymentId) {
                echo json_encode(['success' => true, 'status' => 'pending', 'message' => 'Pagamento ainda pendente']);
                return;
            }

            // Consultar status real no Mercado Pago usando o model
            $mpStatus = $this->mercadoPagoModel->getPaymentStatus($paymentId);
            if ($mpStatus && ($mpStatus['status'] ?? '') === 'approved') {
                // Persistir estado aprovado (paid_awaiting_admin por padrão)
                try {
                    $this->processApprovedPayment($mpStatus);
                } catch (\Throwable $t) {
                    error_log('PaymentController::status persist approval error: ' . $t->getMessage());
                }
                echo json_encode(['success' => true, 'status' => 'approved', 'message' => 'Pagamento aprovado!']);
                return;
            }

            echo json_encode(['success' => true, 'status' => 'pending', 'message' => 'Pagamento ainda pendente']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
    }

    /**
     * Webhook do Mercado Pago
     */
    public function webhook(): void
    {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            error_log("Webhook Mercado Pago recebido: " . $input);

            if (!$data) {
                http_response_code(400);
                echo "Invalid JSON";
                return;
            }

            // Validar webhook
            if (!$this->mercadoPagoModel->validateWebhook($data, $_SERVER['HTTP_X_SIGNATURE'] ?? '')) {
                http_response_code(400);
                echo "Invalid signature";
                return;
            }

            // Processar notificação
            $paymentInfo = $this->mercadoPagoModel->processWebhookNotification($data);
            
            if ($paymentInfo && $paymentInfo['status'] === 'approved') {
                $this->processApprovedPayment($paymentInfo);
            }

            http_response_code(200);
            echo "OK";

        } catch (\Exception $e) {
            error_log("PaymentController::webhook - Erro: " . $e->getMessage());
            http_response_code(500);
            echo "Internal Server Error";
        }
    }

    /**
     * Carrega a view de pagamento
     */
    private function loadPaymentView(?array $subscription, array $planoData): void
    {
        $this->data = [
            'subscription' => $subscription,
            'plano' => $planoData,
            'user_data' => [
                'id' => $_SESSION['user_id'] ?? null,
                'nome' => $_SESSION['user_name'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'foto' => $_SESSION['usuario']['foto'] ?? 'usuario.png',
                'nivel_acesso' => $_SESSION['user_level'] ?? 'usuario'
            ],
            'sidebar_active' => 'payment'
        ];

        // Usar o sistema de view do ConfigViewAdm respeitando navegação SPA
        // Quando a requisição é AJAX (SPA), retornar apenas o conteúdo da view
        // para ser injetado em #dynamic-content. Caso contrário, carregar o layout completo.
        $loadView = new \Adms\CoreAdm\ConfigViewAdm('adms/Views/payment/payment_v2', $this->data);
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            $loadView->loadContentView();
        } else {
            $loadView->loadView();
        }
    }

    /**
     * Busca dados do plano por tipo
     */
    private function getPlanoData(string $planType): ?array
    {
        try {
            $conn = $this->getConnection();
            $query = "SELECT * FROM planos WHERE tipo = :tipo AND ativo = 1";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':tipo', $planType);
            $stmt->execute();
            
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("PaymentController::getPlanoData - Erro: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Processa pagamento aprovado
     */
    private function processApprovedPayment(array $paymentInfo): void
    {
        try {
            $externalRef = $paymentInfo['external_reference'];
            $subscriptionId = str_replace('subscription_', '', $externalRef);
            
            // Buscar assinatura
            $subscription = $this->subscriptionModel->getSubscriptionById($subscriptionId);
            if (!$subscription) {
                error_log("Assinatura não encontrada: " . $subscriptionId);
                return;
            }

            // Aprovar e ativar assinatura imediatamente
            $this->subscriptionModel->approveSubscription((int)$subscriptionId);

            // Sincronizar sessão do usuário para refletir imediatamente no layout/DOM
            try {
                if (session_status() === PHP_SESSION_NONE) { @session_start(); }
                $_SESSION['payment_status'] = 'paid';
                if (!empty($subscription['plano_tipo'])) {
                    $_SESSION['user_plan'] = $subscription['plano_tipo'];
                }
                // Opcional: permitir criação de anúncios imediatamente
                $_SESSION['can_create_ads'] = 1;
            } catch (\Throwable $t) {
                // apenas loga, não interrompe fluxo
                error_log('PaymentController::processApprovedPayment - falha ao sincronizar sessão: ' . $t->getMessage());
            }

            // Notificar administradores
            $this->notifyAdminsPaymentReceived($subscription);

        } catch (\Exception $e) {
            error_log("PaymentController::processApprovedPayment - Erro: " . $e->getMessage());
        }
    }

    /**
     * Notifica administradores sobre pagamento recebido
     */
    private function notifyAdminsPaymentReceived(array $subscription): void
    {
        try {
            $this->notificationModel->notifyNewPayment(
                $subscription['user_id'],
                $subscription['user_name'] ?? 'Usuário',
                $subscription['amount'],
                $subscription['plano_nome'] ?? 'Plano'
            );
        } catch (\Exception $e) {
            error_log("PaymentController::notifyAdminsPaymentReceived - Erro: " . $e->getMessage());
        }
    }

    /**
     * Retorna mensagem baseada no status
     */
    private function getStatusMessage(string $status): string
    {
        $messages = [
            'pending' => 'Aguardando pagamento...',
            'paid_awaiting_admin' => 'Pagamento confirmado! Aguardando aprovação.',
            'active' => 'Pagamento aprovado!',
            'suspended' => 'Assinatura suspensa.',
            'cancelled' => 'Pagamento cancelado.',
            'failed' => 'Pagamento falhou.'
        ];

        return $messages[$status] ?? 'Status desconhecido';
    }

    /**
     * Exibe erro
     */
    private function showError(string $message): void
    {
        $this->data = ['error_message' => $message];
        // Carregar a view de erro usando o sistema de view do ConfigViewAdm
        $loadView = new \Adms\CoreAdm\ConfigViewAdm('error/erro500', $this->data);
        $loadView->loadView();
    }
    
    /**
     * Gera QR Code SVG simples
     */
    private function generateSimpleQRCode($data) {
        // QR Code SVG simples (placeholder)
        $size = 200;
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg">
    <rect width="' . $size . '" height="' . $size . '" fill="white"/>
    <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="12" fill="black">QR Code</text>
    <text x="50%" y="60%" text-anchor="middle" dy=".3em" font-family="Arial" font-size="10" fill="gray">PIX Payment</text>
</svg>';
        return $svg;
    }

    /**
     * Método para URLs amigáveis - PIX
     */
    public function pix(string $plan = 'basic'): void
    {
        // Definir o plano baseado no parâmetro da URL
        $_GET['plan'] = $plan;
        $this->index();
    }

    /**
     * Método para URLs amigáveis - Pagar
     */
    public function pagar(string $plan = 'basic'): void
    {
        // Definir o plano baseado no parâmetro da URL
        $_GET['plan'] = $plan;
        $this->index();
    }

    /**
     * Método para URLs amigáveis - Checkout
     */
    public function checkout(string $plan = 'basic'): void
    {
        // Definir o plano baseado no parâmetro da URL
        $_GET['plan'] = $plan;
        $this->index();
    }

    /**
     * Método para URLs amigáveis - Assinatura
     */
    public function assinatura(string $plan = 'basic'): void
    {
        // Definir o plano baseado no parâmetro da URL
        $_GET['plan'] = $plan;
        $this->index();
    }

    /**
     * Método para URLs amigáveis - Planos
     */
    public function planos(): void
    {
        $this->index();
    }
}
