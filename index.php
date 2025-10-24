<?php
// Exibe erros para facilitar a depuração durante o desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configurar timezone para o Brasil
date_default_timezone_set('America/Sao_Paulo');

// Inicia a sessão para controle de login, mensagens etc.
session_start();

// Ativa o buffer de saída para permitir redirecionamentos e controle de headers
ob_start();

// Define constante de segurança para impedir acesso direto a arquivos internos
define('C7E3L8K9E5', true);

// Carrega o autoload do Composer (carrega as classes automaticamente)
require './vendor/autoload.php';

// Usa a classe ConfigController do namespace Core para gerenciar URLs e carregamento de controllers
use Core\ConfigController;

// Instancia a classe responsável por processar a URL
$url = new ConfigController();

// Chama o método que irá localizar e executar a controller e método corretos
$url->loadPage();
