<?php

// Ativar exibição de erros - ESSENCIAL PARA DESENVOLVIMENTO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Adms\CoreAdm\ConfigControllerAdm;

// Ativa o buffer de saída para permitir redirecionamentos e controle de headers
ob_start();

// Define constante de segurança para impedir acesso direto a arquivos internos
define('C7E3L8K9E5', true);

// Carrega o autoload do Composer (carrega as classes automaticamente)
require './vendor/autoload.php';

// INICIA A SESSÃO AQUI, UMA ÚNICA VEZ
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// NOVO: LOGS DE DEPURACAO DE SESSAO NO INICIO DE CADA REQUISICAO
error_log("DEBUG INDEX_ADMIN: Início da requisi\xc3\xa7\xc3\xa3o. Session ID: " . session_id());
error_log("DEBUG INDEX_ADMIN: user_id na sess\xc3\xa3o: " . ($_SESSION['user_id'] ?? 'N/A'));
error_log("DEBUG INDEX_ADMIN: user_level_numeric na sess\xc3\xa3o: " . ($_SESSION['user_level_numeric'] ?? 'N/A'));


// REMOVIDO TEMPORARIAMENTE PARA DEPURACAO: try-catch block
// try { 
    // Instancia a classe responsável por processar a URL
    $url = new ConfigControllerAdm();

    // Chama o método que irá localizar e executar a controller e método corretos
    $url->loadPage();
// } catch (\Throwable $e) {
//     // Log do erro em vez de exibi-lo diretamente ao usuário em produção
//     error_log("Erro crítico na aplicação: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine());

//     // Redireciona para uma página de erro genérica ou exibe uma mensagem amigável
//     if (ini_get('display_errors')) { // Apenas exibe detalhes do erro se display_errors estiver ativo
//         die("Erro crítico na aplicação. Detalhes: " . $e->getMessage());
//     } else {
//         header("Location: /erro"); 
//         exit();
//     }
// }

// Limpa o buffer de saída e envia o conteúdo para o navegador
ob_end_flush();
