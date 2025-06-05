<?php

namespace Core;

if (!defined('C7E3L8K9E5')) {
    // Redireciona para a raiz caso o arquivo seja acessado diretamente
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Controllers\LoginController as StsLoginController; // Alias para evitar conflito
use Adms\Controllers\LoginController as AdmsLoginController;

class ConfigController extends Config
{
    private string $url; // Variável para armazenar a URL recebida
    private array $urlArray; // Array que armazenará os segmentos da URL
    private string $urlController; // Controlador a ser carregado
    private ?string $urlMethod; // Método a ser carregado
    private string $urlSlugController; // Controlador "slug" (com nome formatado)
    private array $format; // Formatação para limpar caracteres especiais
    private string $classLoad; // Nome completo da classe para ser carregada

    // Construtor da classe
    public function __construct()
    {
        $this->config(); // Configurações gerais
        $this->processUrl(); // Processa a URL recebida
    }

    // Processa a URL para extrair o controlador e a ação
    private function processUrl(): void
    {
        // Verifica se há uma URL na query string
        if (!empty(filter_input(INPUT_GET, 'url', FILTER_DEFAULT))) {
            $this->url = filter_input(INPUT_GET, 'url', FILTER_DEFAULT);
            $this->clearUrl(); // Limpa a URL para evitar caracteres indesejados
            $this->urlArray = explode("/", $this->url); // Divide a URL em segmentos

            // Define o controlador
            $this->urlController = isset($this->urlArray[0])
                ? $this->slugController($this->urlArray[0])
                : $this->slugController(CONTROLLERERRO);

            // Define o método (se existir)
            $this->urlMethod = isset($this->urlArray[1]) ? $this->urlArray[1] : 'index';
        } else {
            // Caso não haja URL, carrega o controlador padrão e o método index
            $this->urlController = $this->slugController(CONTROLLER);
            $this->urlMethod = 'index';
        }
    }

    // Limpa a URL de caracteres especiais e formata
    private function clearUrl(): void
    {
        // Remove tags HTML e espaços extras
        $this->url = strip_tags(trim(rtrim($this->url, "/")));
        // Define a tabela de substituição para caracteres especiais
        $this->format['a'] = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]?;:.,\\\'<>°ºª ';
        $this->format['b'] = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr-------------------------------------------------------------------------------------------------';
        // Realiza a substituição dos caracteres especiais
        $this->url = strtr(iconv("UTF-8", "ISO-8859-1", $this->url), iconv("UTF-8", "ISO-8859-1", $this->format['a']), $this->format['b']);
    }

    // Formata o nome do controlador (slug para o formato correto)
    private function slugController(string $slugController): string
    {
        return str_replace(" ", "", ucwords(str_replace("-", " ", strtolower($slugController))));
    }

    // Carrega a página e o controlador apropriado
    public function loadPage(): void
    {
        // Verifica se é uma requisição para área administrativa ou para o site
        if (strpos($_SERVER['REQUEST_URI'], '/adms') !== false) {
            $namespaceBase = '\\Adms\\Controllers\\';
        } else {
            $namespaceBase = '\\Sts\\Controllers\\';
        }

        $this->classLoad = $namespaceBase . $this->urlController; // Define a classe a ser carregada

        // Se a classe existir, carrega
        if (class_exists($this->classLoad)) {
            $this->loadClass();
        } else {
            // Caso contrário, redireciona para a página de erro
            if ($this->urlController != $this->slugController(CONTROLLERERRO)) {
                $this->urlController = $this->slugController(CONTROLLERERRO);
                $this->loadPage(); // Redireciona para a página de erro
            } else {
                die("Erro: Página de erro não encontrada.");
            }
        }
    }

    // Carrega a classe do controlador e executa o método
    private function loadClass(): void
    {
        $classPage = new $this->classLoad();

        // Verifica se o método existe e chama-o
        if (method_exists($classPage, $this->urlMethod)) {
            $classPage->{$this->urlMethod}();
        } else {
            // Se o método não existir, tenta carregar o método 'index' ou exibe erro
            if (method_exists($classPage, 'index')) {
                $classPage->index();
            } else {
                die("Erro: Método '{$this->urlMethod}' ou 'index' não encontrado na controller {$this->classLoad}. Contate o administrador: " . EMAILADM);
            }
        }
    }
}
