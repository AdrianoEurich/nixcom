<?php

namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Adms\CoreAdm\ConfigViewAdm;
use Adms\Config\MercadoPagoConfig;

class WebhookConfigController
{
    private array $data = [];

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Página de configuração de webhook
     */
    public function index(): void
    {
        // Verificar se é admin
        if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] !== 'administrador') {
            header("Location: " . URLADM . "login");
            exit();
        }

        $this->data = [
            'title' => 'Configuração de Webhook - ' . SITE_NAME,
            'sidebar_active' => 'webhook-config',
            'webhook_url' => MercadoPagoConfig::getWebhookUrl(),
            'environment_info' => MercadoPagoConfig::getEnvironmentInfo()
        ];

        $loadView = new ConfigViewAdm("adms/Views/webhook-config/webhook-config", $this->data);
        $loadView->loadViewAdm();
    }

    /**
     * Configura webhook via API do Mercado Pago
     */
    public function configure(): void
    {
        header('Content-Type: application/json');
        
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || !isset($data['webhook_url'])) {
                echo json_encode(['success' => false, 'message' => 'URL do webhook é obrigatória']);
                return;
            }
            
            $webhookUrl = $data['webhook_url'];
            $topics = $data['topics'] ?? ['payment'];
            
            // Validar URL
            if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
                echo json_encode(['success' => false, 'message' => 'URL inválida']);
                return;
            }
            
            // Verificar se é HTTPS em produção
            if (!MercadoPagoConfig::isSandbox() && !str_starts_with($webhookUrl, 'https://')) {
                echo json_encode(['success' => false, 'message' => 'Em produção, o webhook deve usar HTTPS']);
                return;
            }
            
            $result = $this->configureWebhookWithMercadoPago($webhookUrl, $topics);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Webhook configurado com sucesso',
                    'webhook_id' => $result['webhook_id']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
            
        } catch (\Exception $e) {
            error_log("WebhookConfigController::configure - Erro: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
    }

    /**
     * Testa webhook enviando notificação de teste
     */
    public function test(): void
    {
        header('Content-Type: application/json');
        
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || !isset($data['webhook_url'])) {
                echo json_encode(['success' => false, 'message' => 'URL do webhook é obrigatória']);
                return;
            }
            
            $webhookUrl = $data['webhook_url'];
            
            // Simular notificação de teste
            $testNotification = [
                'id' => 'test_' . uniqid(),
                'type' => 'payment',
                'action' => 'payment.created',
                'data' => [
                    'id' => 'test_payment_' . uniqid()
                ],
                'date_created' => date('c'),
                'user_id' => 'test_user',
                'api_version' => 'v1',
                'live_mode' => !MercadoPagoConfig::isSandbox()
            ];
            
            $result = $this->sendTestNotification($webhookUrl, $testNotification);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notificação de teste enviada com sucesso',
                    'response' => $result['response']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
            
        } catch (\Exception $e) {
            error_log("WebhookConfigController::test - Erro: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
    }

    /**
     * Configura webhook via API do Mercado Pago
     */
    private function configureWebhookWithMercadoPago(string $webhookUrl, array $topics): array
    {
        try {
            $accessToken = MercadoPagoConfig::getAccessToken();
            $baseUrl = MercadoPagoConfig::getBaseUrl();
            
            $webhookData = [
                'url' => $webhookUrl,
                'topics' => $topics
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $baseUrl . '/v1/webhooks',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($webhookData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                    'X-Idempotency-Key: ' . uniqid()
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return ['success' => false, 'message' => 'Erro cURL: ' . $error];
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'webhook_id' => $responseData['id'] ?? null,
                    'message' => 'Webhook configurado com sucesso'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Erro ao configurar webhook'
                ];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envia notificação de teste para o webhook
     */
    private function sendTestNotification(string $webhookUrl, array $notification): array
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $webhookUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($notification),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: MercadoPago-Webhook-Test/1.0'
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return ['success' => false, 'message' => 'Erro cURL: ' . $error];
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'response' => $response,
                    'message' => 'Notificação enviada com sucesso'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Webhook retornou código HTTP: ' . $httpCode
                ];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

