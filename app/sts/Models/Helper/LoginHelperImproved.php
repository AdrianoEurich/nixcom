<?php

namespace Sts\Models\Helper;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class LoginHelperImproved
{
    private static int $sessionTimeout = 1800; // 30 minutos
    private static function startSessionSafe(): void
    {
        if (session_status() === \PHP_SESSION_NONE) {
            $path = (string)ini_get('session.save_path');
            if (!$path) {
                $path = 'C:/xampp/tmp';
            }
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            if (!is_writable($path)) {
                $fallback = sys_get_temp_dir();
                if ($fallback && (!is_dir($fallback))) { @mkdir($fallback, 0777, true); }
                if ($fallback) { @ini_set('session.save_path', $fallback); }
            } else {
                @ini_set('session.save_path', $path);
            }
            @session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        self::startSessionSafe();
        
        // Verificar se user_id existe
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }
        
        // Verificar timeout de sessão
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > self::$sessionTimeout)) {
            self::logout();
            return false;
        }
        
        // Atualizar última atividade
        $_SESSION['LAST_ACTIVITY'] = time();
        
        return true;
    }
    
    public static function getUserId(): ?int
    {
        self::startSessionSafe();
        
        return self::isLoggedIn() ? ($_SESSION['user_id'] ?? null) : null;
    }
    
    public static function getUserName(): ?string
    {
        self::startSessionSafe();
        
        return self::isLoggedIn() ? ($_SESSION['user_name'] ?? null) : null;
    }
    
    public static function getUserLevel(): ?string
    {
        self::startSessionSafe();
        
        return self::isLoggedIn() ? ($_SESSION['user_level'] ?? null) : null;
    }
    
    public static function getRedirectUrl(string $defaultUrl): string
    {
        if (self::isLoggedIn()) {
            return URLADM . "dashboard";
        } else {
            return $defaultUrl;
        }
    }
    
    public static function getButtonText(string $defaultText): string
    {
        if (self::isLoggedIn()) {
            return "Meu Dashboard";
        } else {
            return $defaultText;
        }
    }
    
    public static function getButtonClass(string $defaultClass): string
    {
        if (self::isLoggedIn()) {
            return $defaultClass . " btn-success"; // Verde para logado
        } else {
            return $defaultClass . " btn-primary"; // Azul para não logado
        }
    }
    
    public static function logout(): void
    {
        self::startSessionSafe();
        
        // Limpar todas as variáveis de sessão
        $_SESSION = array();
        
        // Destruir a sessão
        session_destroy();
        
        // Remover cookie de sessão
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Método para regenerar ID de sessão (proteção básica contra session fixation)
    public static function regenerateSessionId(): void
    {
        self::startSessionSafe();
        
        session_regenerate_id(true);
    }
    
    // Método para verificar se a sessão está próxima do timeout
    public static function isSessionNearTimeout(): bool
    {
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            return false;
        }
        
        $timeRemaining = self::$sessionTimeout - (time() - $_SESSION['LAST_ACTIVITY']);
        return $timeRemaining < 300; // 5 minutos restantes
    }
    
    // Método para obter tempo restante da sessão
    public static function getSessionTimeRemaining(): int
    {
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            return 0;
        }
        
        return max(0, self::$sessionTimeout - (time() - $_SESSION['LAST_ACTIVITY']));
    }
}
