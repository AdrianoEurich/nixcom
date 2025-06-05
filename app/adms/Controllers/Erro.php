<?php
namespace Adms\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Erro
{
    public function index(): void
    {
        // Redireciona para o erro 500 por padrão
        $this->erro500();
    }

    public function erro500(): void
    {
        // Define o código de status HTTP
        http_response_code(500);
        
        // Carrega a view de erro 500
        $view = new \Adms\CoreAdm\ConfigViewAdm("erro/erro500");
        $view->loadView();
    }

    public function erro404(): void
    {
        http_response_code(404);
        $view = new \Adms\CoreAdm\ConfigViewAdm("erro/erro404");
        $view->loadView();
    }
}