<?php

// Ativar exibição de erros - Ideal para desenvolvimento, desabilitar em produção
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Adms\CoreAdm\ConfigControllerAdm;

// Inicia a sessão para controle de login, mensagens etc.
session_start();

// Ativa o buffer de saída para permitir redirecionamentos e controle de headers
ob_start();

// Define constante de segurança para impedir acesso direto a arquivos internos
define('C7E3L8K9E5', true);

// Carrega o autoload do Composer (carrega as classes automaticamente)
require './vendor/autoload.php';

try {
    // Instancia a classe responsável por processar a URL
    $url = new ConfigControllerAdm();

    // Chama o método que irá localizar e executar a controller e método corretos
    $url->loadPage();
} catch (\Throwable $e) {
    // Log do erro em vez de exibi-lo diretamente ao usuário em produção
    error_log("Erro crítico na aplicação: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine());

    // Redireciona para uma página de erro genérica ou exibe uma mensagem amigável
    // Em um ambiente de produção, você pode simplesmente redirecionar para uma página de erro 500
    if (ini_get('display_errors')) { // Apenas exibe detalhes do erro se display_errors estiver ativo
        die("Erro crítico na aplicação. Detalhes: " . $e->getMessage());
    } else {
        header("Location: /erro"); // Supondo que você tenha uma rota /erro para erros genéricos
        exit();
    }
}

// Limpa o buffer de saída e envia o conteúdo para o navegador
ob_end_flush();