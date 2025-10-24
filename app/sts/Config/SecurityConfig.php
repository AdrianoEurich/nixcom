<?php

namespace Sts\Config;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class SecurityConfig
{
    // Configurações de sessão
    public const SESSION_TIMEOUT = 1800; // 30 minutos
    public const SESSION_REGENERATE_INTERVAL = 300; // 5 minutos
    public const SESSION_HTTP_ONLY = true;
    public const SESSION_SECURE = false; // true em produção com HTTPS
    public const SESSION_SAME_SITE = 'Strict';
    
    // Configurações de login
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOCKOUT_TIME = 900; // 15 minutos
    public const PASSWORD_MIN_LENGTH = 8;
    public const PASSWORD_REQUIRE_SPECIAL = true;
    
    // Configurações de IP
    public const CHECK_IP_CHANGE = false; // true para maior segurança
    public const ALLOWED_IPS = []; // IPs permitidos (vazio = todos)
    
    // Configurações de logs
    public const LOG_SECURITY_EVENTS = true;
    public const LOG_RETENTION_DAYS = 30;
    
    // Configurações de tokens
    public const TOKEN_LENGTH = 32;
    public const TOKEN_EXPIRY = 3600; // 1 hora
    
    /**
     * Configurações de sessão segura
     */
    public static function configureSecureSession(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            // Configurações de cookie de sessão
            ini_set('session.cookie_httponly', self::SESSION_HTTP_ONLY ? '1' : '0');
            ini_set('session.cookie_secure', self::SESSION_SECURE ? '1' : '0');
            ini_set('session.cookie_samesite', self::SESSION_SAME_SITE);
            
            // Configurações de sessão
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_lifetime', self::SESSION_TIMEOUT);
            
            // Iniciar sessão
            session_start();
        }
    }
    
    /**
     * Verifica se a requisição é segura
     */
    public static function isSecureRequest(): bool
    {
        // Verificar HTTPS em produção
        if (self::SESSION_SECURE && !isset($_SERVER['HTTPS'])) {
            return false;
        }
        
        // Verificar IP permitido
        if (!empty(self::ALLOWED_IPS)) {
            $clientIp = self::getClientIp();
            if (!in_array($clientIp, self::ALLOWED_IPS)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtém o IP real do cliente
     */
    public static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Padrão
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Pegar o primeiro IP se houver múltiplos
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Gera token seguro
     */
    public static function generateSecureToken(int $length = null): string
    {
        $length = $length ?? self::TOKEN_LENGTH;
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Valida força da senha
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = "Senha deve ter pelo menos " . self::PASSWORD_MIN_LENGTH . " caracteres";
        }
        
        if (self::PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = "Senha deve conter pelo menos um caractere especial";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Senha deve conter pelo menos uma letra maiúscula";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Senha deve conter pelo menos uma letra minúscula";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Senha deve conter pelo menos um número";
        }
        
        return $errors;
    }
    
    /**
     * Sanitiza entrada do usuário
     */
    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Verifica se é uma requisição AJAX
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Headers de segurança
     */
    public static function setSecurityHeaders(): void
    {
        // Prevenir clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevenir MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (básico)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");
    }
}
