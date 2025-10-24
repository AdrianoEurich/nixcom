<?php

namespace Adms\Config;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class MercadoPagoConfig
{
    // Configurações do Mercado Pago
    public const SANDBOX_ACCESS_TOKEN = 'TEST-8226898734680411-101115-25c2f162746562a2efce98e7072453ec-12767031';
    public const PRODUCTION_ACCESS_TOKEN = 'APP_USR-8226898734680411-101115-9d226eccd0f3b50c836673d870c039f7-12767031';
    
    // URLs da API
    public const SANDBOX_BASE_URL = 'https://api.mercadopago.com';
    public const PRODUCTION_BASE_URL = 'https://api.mercadopago.com';
    
    // Configurações do ambiente
    public const IS_SANDBOX = true; // true para desenvolvimento, false para produção
    
    // Configurações de webhook
    public const WEBHOOK_URL_PRODUCTION = 'https://seudominio.com/webhook/mercadopago.php';
    public const WEBHOOK_URL_SANDBOX = 'https://abc123.ngrok.io/webhook/mercadopago.php'; // Substitua pela URL do ngrok
    
    /**
     * Obtém o token de acesso baseado no ambiente
     */
    public static function getAccessToken(): string
    {
        return self::IS_SANDBOX ? self::SANDBOX_ACCESS_TOKEN : self::PRODUCTION_ACCESS_TOKEN;
    }
    
    /**
     * Obtém a URL base da API
     */
    public static function getBaseUrl(): string
    {
        return self::IS_SANDBOX ? self::SANDBOX_BASE_URL : self::PRODUCTION_BASE_URL;
    }
    
    /**
     * Verifica se está em modo sandbox
     */
    public static function isSandbox(): bool
    {
        return self::IS_SANDBOX;
    }
    
    /**
     * Obtém a URL do webhook baseada no ambiente
     */
    public static function getWebhookUrl(): string
    {
        return self::IS_SANDBOX ? self::WEBHOOK_URL_SANDBOX : self::WEBHOOK_URL_PRODUCTION;
    }
    
    /**
     * Obtém informações do ambiente
     */
    public static function getEnvironmentInfo(): array
    {
        return [
            'is_sandbox' => self::IS_SANDBOX,
            'base_url' => self::getBaseUrl(),
            'webhook_url' => self::getWebhookUrl(),
            'has_token' => !empty(self::getAccessToken())
        ];
    }
}
