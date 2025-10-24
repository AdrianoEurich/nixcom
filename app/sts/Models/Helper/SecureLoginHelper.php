<?php

namespace Sts\Models\Helper;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PDO;
use PDOException;

class SecureLoginHelper
{
    private static ?PDO $pdo = null;
    private const SESSION_TIMEOUT = 1800; // 30 minutos
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutos
    
    /**
     * Inicializa conexão com banco de dados
     */
    private static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            try {
                $host = 'localhost';
                $dbname = 'nixcom';
                $username = 'root';
                $password = '';
                
                self::$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log("ERRO CONEXÃO SECURE LOGIN: " . $e->getMessage());
                throw new \Exception("Erro de conexão com banco de dados");
            }
        }
        return self::$pdo;
    }
    
    /**
     * Verifica se o usuário está logado com validações de segurança
     */
    public static function isLoggedIn(): bool
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificações básicas
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }
        
        // Verificar timeout da sessão
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
                self::destroySession();
                return false;
            }
        }
        
        // Verificar se o usuário ainda existe e está ativo
        if (!self::validateUserExists($_SESSION['user_id'])) {
            self::destroySession();
            return false;
        }
        
        // Verificar IP (opcional, pode ser muito restritivo)
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            // Log de possível sequestro de sessão
            error_log("SECURITY WARNING: IP changed for user " . $_SESSION['user_id']);
            // Opcional: destruir sessão por segurança
            // self::destroySession();
            // return false;
        }
        
        // Atualizar última atividade
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Valida se o usuário ainda existe no banco
     */
    private static function validateUserExists(int $userId): bool
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, status 
                FROM usuarios 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user !== false;
        } catch (PDOException $e) {
            error_log("ERRO VALIDAR USUÁRIO: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retorna o ID do usuário logado com validação
     */
    public static function getUserId(): ?int
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Retorna o nome do usuário logado com validação
     */
    public static function getUserName(): ?string
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user_name'] ?? null;
    }
    
    /**
     * Retorna o nível de acesso do usuário com validação
     */
    public static function getUserLevel(): ?string
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user_level'] ?? null;
    }
    
    /**
     * Verifica se o usuário tem permissão específica
     */
    public static function hasPermission(string $permission): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $userLevel = self::getUserLevel();
        
        // Mapeamento de permissões por nível
        $permissions = [
            'administrador' => ['admin', 'user', 'view'],
            'usuario' => ['user', 'view'],
            'normal' => ['view']
        ];
        
        return in_array($permission, $permissions[$userLevel] ?? []);
    }
    
    /**
     * Retorna a URL de redirecionamento segura
     */
    public static function getRedirectUrl(string $defaultUrl): string
    {
        if (self::isLoggedIn()) {
            // Verificar se o usuário tem permissão para acessar o dashboard
            if (self::hasPermission('user')) {
                return URLADM . "dashboard";
            } else {
                return URLADM . "login";
            }
        } else {
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
    
    /**
     * Destrói a sessão de forma segura
     */
    public static function destroySession(): void
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            // Limpar todas as variáveis de sessão
            $_SESSION = [];
            
            // Destruir o cookie de sessão
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destruir a sessão
            session_destroy();
        }
    }
    
    /**
     * Regenera o ID da sessão para prevenir session fixation
     */
    public static function regenerateSessionId(): void
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    /**
     * Verifica tentativas de login e bloqueia se necessário
     */
    public static function checkLoginAttempts(string $ip): bool
    {
        try {
            $pdo = self::getConnection();
            
            // Verificar tentativas recentes
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE ip_address = ? 
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ip, self::LOCKOUT_TIME]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['attempts'] < self::MAX_LOGIN_ATTEMPTS;
        } catch (PDOException $e) {
            error_log("ERRO VERIFICAR TENTATIVAS: " . $e->getMessage());
            return true; // Em caso de erro, permitir tentativa
        }
    }
    
    /**
     * Registra tentativa de login
     */
    public static function logLoginAttempt(string $ip, bool $success): void
    {
        try {
            $pdo = self::getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO login_attempts (ip_address, success, attempt_time) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$ip, $success ? 1 : 0]);
        } catch (PDOException $e) {
            error_log("ERRO REGISTRAR TENTATIVA: " . $e->getMessage());
        }
    }
    
    /**
     * Limpa tentativas antigas de login
     */
    public static function cleanOldAttempts(): void
    {
        try {
            $pdo = self::getConnection();
            
            $stmt = $pdo->prepare("
                DELETE FROM login_attempts 
                WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("ERRO LIMPAR TENTATIVAS: " . $e->getMessage());
        }
    }
}
