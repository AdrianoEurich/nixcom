<?php

namespace Sts\Models\Helper;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class LoginHelper
{
    /**
     * Verifica se o usuário está logado
     */
    public static function isLoggedIn(): bool
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Retorna o ID do usuário logado
     */
    public static function getUserId(): ?int
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Retorna o nome do usuário logado
     */
    public static function getUserName(): ?string
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_name'] ?? null;
    }
    
    /**
     * Retorna o nível de acesso do usuário
     */
    public static function getUserLevel(): ?string
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_level'] ?? null;
    }
    
    /**
     * Retorna a URL de redirecionamento baseada no status de login
     */
    public static function getRedirectUrl(string $defaultUrl): string
    {
        if (self::isLoggedIn()) {
            // Se estiver logado, redireciona para o dashboard
            return URLADM . "dashboard";
        } else {
            // Se não estiver logado, vai para a página de cadastro
            return $defaultUrl;
        }
    }
    
    /**
     * Retorna o texto do botão baseado no status de login
     */
    public static function getButtonText(string $defaultText): string
    {
        if (self::isLoggedIn()) {
            return "Meu Dashboard";
        } else {
            return $defaultText;
        }
    }
    
    /**
     * Retorna a classe CSS do botão baseada no status de login
     */
    public static function getButtonClass(string $defaultClass): string
    {
        if (self::isLoggedIn()) {
            return $defaultClass . " logged-in";
        } else {
            return $defaultClass;
        }
    }
}
