<?php

namespace Sts\Controllers;

// Redirecionar ou para o processamento quando o usuário não acessa o arquivo index.php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}


/**
 * Controller da página Erro
 *
 * @author Adriano <adriano.eurich@gmail.com>
 */
class Erro
{

    /** @var array|string|null $dados Recebe os dados que devem ser enviados para VIEW */
    private array|string|null $data;

    /**
     * Instanciar a classe responsável em carregar a View
     * 
     * @return void
     */
    public function index()
    {
        $this->data = null;
        $loadViewErro= new \Core\ConfigView("sts/Views/erro/erroView", $this->data);
        $loadViewErro->loadViewErro();
    }
}