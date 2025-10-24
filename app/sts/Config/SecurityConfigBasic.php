<?php

namespace Sts\Config;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class SecurityConfigBasic
{
    // Configurações de Sessão Básicas
    public const SESSION_TIMEOUT_SECONDS = 1800; // 30 minutos
    public const SESSION_WARNING_TIME = 300; // 5 minutos antes do timeout
    
    // Headers de Segurança Básicos
    public const X_FRAME_OPTIONS = "SAMEORIGIN"; // Previne Clickjacking
    public const X_XSS_PROTECTION = "1; mode=block"; // Ativa proteção XSS
    public const X_CONTENT_TYPE_OPTIONS = "nosniff"; // Previne MIME-sniffing
    
    // Configurações de Senha Básicas
    public const PASSWORD_MIN_LENGTH = 6; // Mínimo 6 caracteres
    
    // Outras configurações básicas
    public const LOG_SECURITY_EVENTS = false; // Desativado para nível básico
    public const LOG_FILE_PATH = __DIR__ . '/../../security.log';
    
    // Níveis de acesso básicos
    public const ACCESS_LEVEL_ADMIN = 'administrador';
    public const ACCESS_LEVEL_USER = 'usuario';
    public const ACCESS_LEVEL_GUEST = 'guest';
    
    // Método para aplicar headers de segurança básicos
    public static function applySecurityHeaders(): void
    {
        // Aplicar headers de segurança básicos
        header("X-Frame-Options: " . self::X_FRAME_OPTIONS);
        header("X-XSS-Protection: " . self::X_XSS_PROTECTION);
        header("X-Content-Type-Options: " . self::X_CONTENT_TYPE_OPTIONS);
    }
    
    // Método para validar senha básica
    public static function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = "A senha deve ter pelo menos " . self::PASSWORD_MIN_LENGTH . " caracteres";
        }
        
        return $errors;
    }
    
    // Método para sanitizar entrada básica
    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}
