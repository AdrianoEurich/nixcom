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
        // URL do projeto
        define('URL', 'http://localhost/nixcom/');
        define('URLADM', 'http://localhost/nixcom/adms/');

        // Controllers padrão
        define('CONTROLLER', 'Home');
        define('CONTROLLERERRO', 'Erro');
        define('CONTROLLERADM', 'Login'); // Adicionado controller padrão para ADM

        // Credenciais do banco de dados
        define('HOST', 'localhost');
        define('USER', 'root');
        define('PASS', '');
        define('DBNAME', 'nixcom');
        define('PORT', 3306);

        define('SITE_NAME', 'Nixcom');
        define('SITE_DESC', 'Sistema de Gerenciamento');

        define('EMAILADM', 'adriano.eurich@gmail.com');
    }
}