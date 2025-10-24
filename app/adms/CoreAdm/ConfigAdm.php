<?php
namespace Adms\CoreAdm;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

abstract class ConfigAdm
{
    protected function config(): void
    {
        error_log("DEBUG CONFIGADM: Método config() chamado. Definindo constantes."); 

        // URL do projeto
        if (!defined('URL')) { // Verifica se a constante já foi definida
            define('URL', 'http://localhost/nixcom/');
        }
        if (!defined('URLADM')) { // Verifica se a constante já foi definida
            define('URLADM', 'http://localhost/nixcom/adms/');
        }

        // Define o nome do diretório da área administrativa na URL
        if (!defined('ADM_DIR')) {
            define('ADM_DIR', 'adms'); 
        }

        // Controllers padrão
        if (!defined('CONTROLLER')) {
            define('CONTROLLER', 'Home'); // Controlador padrão da área pública
        }
        if (!defined('CONTROLLERERRO')) {
            define('CONTROLLERERRO', 'Erro'); // Controlador de erro
        }
        if (!defined('CONTROLLERADM')) {
            define('CONTROLLERADM', 'Dashboard'); // Controlador padrão para ADM após login
        }

        // Credenciais do banco de dados
        if (!defined('HOST')) {
            define('HOST', 'localhost');
        }
        if (!defined('USER')) {
            define('USER', 'root');
        }
        if (!defined('PASS')) {
            define('PASS', '');
        }
        if (!defined('DBNAME')) {
            define('DBNAME', 'nixcom');
        }
        if (!defined('PORT')) {
            define('PORT', 3306);
        }

        if (!defined('SITE_NAME')) {
            define('SITE_NAME', 'Nixcom');
        }
        if (!defined('SITE_DESC')) {
            define('SITE_DESC', 'Sistema de Gerenciamento');
        }

        if (!defined('EMAILADM')) {
            define('EMAILADM', 'adriano.eurich@gmail.com');
        }
    }

    /**
     * Define headers anti-cache para desenvolvimento
     */
    protected function setAntiCacheHeaders(): void
    {
        // Só aplicar em ambiente de desenvolvimento (localhost)
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        }
    }
}