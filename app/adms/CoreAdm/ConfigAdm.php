<?php
namespace Adms\CoreAdm;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: P\xc3\xa1gina n\xc3\xa3o encontrada!");
}

abstract class ConfigAdm
{
    protected function config(): void
    {
        error_log("DEBUG CONFIGADM: M\xc3\xa9todo config() chamado. Definindo constantes."); 

        // URL do projeto
        if (!defined('URL')) { // Verifica se a constante j\xc3\xa3 foi definida
            define('URL', 'http://localhost/nixcom/');
        }
        if (!defined('URLADM')) { // Verifica se a constante j\xc3\xa3 foi definida
            define('URLADM', 'http://localhost/nixcom/adms/');
        }

        // Define o nome do diret\xc3\xb3rio da \xc3\xa1rea administrativa na URL
        if (!defined('ADM_DIR')) {
            define('ADM_DIR', 'adms'); 
        }

        // Controllers padr\xc3\xa3o
        if (!defined('CONTROLLER')) {
            define('CONTROLLER', 'Home'); // Controlador padr\xc3\xa3o da \xc3\xa1rea p\xc3\xbablica
        }
        if (!defined('CONTROLLERERRO')) {
            define('CONTROLLERERRO', 'Erro'); // Controlador de erro
        }
        if (!defined('CONTROLLERADM')) {
            define('CONTROLLERADM', 'Dashboard'); // Controlador padr\xc3\xa3o para ADM ap\xc3\xb3s login
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
}