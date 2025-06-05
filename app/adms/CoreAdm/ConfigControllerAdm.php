<?php

namespace Adms\CoreAdm;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Controllers\LoginController as StsLoginController;
use Adms\Controllers\Login as AdmsLoginController;
use Adms\CoreAdm\ConfigAdm;

class ConfigControllerAdm extends ConfigAdm
{
    private string $url;
    private array $urlArray;
    private string $urlController;
    private ?string $urlMethod;
    private string $urlSlugController;
    private array $format;
    private string $classLoad;
    private object $classPage;

    public function __construct()
    {
        $this->config();
        $this->processUrl();
    }

    private function processUrl(): void
    {
        if (!empty(filter_input(INPUT_GET, 'url', FILTER_DEFAULT))) {
            $this->url = filter_input(INPUT_GET, 'url', FILTER_DEFAULT);
            $this->clearUrl();
            $this->urlArray = explode("/", $this->url);

            // Verifica se é uma rota da área administrativa
            if (isset($this->urlArray[0]) && $this->urlArray[0] === 'adms') {
                $this->processAdmRoutes();
            } else {
                $this->processDefaultRoutes();
            }
        } else {
            // Rota padrão da área administrativa
            $this->urlController = $this->slugController(CONTROLLERADM);
            $this->urlMethod = 'index';
        }
    }

    private function processAdmRoutes(): void
    {
        // Rotas da área administrativa
        if (isset($this->urlArray[1])) {
            $this->urlController = $this->slugController($this->urlArray[1]);
            $this->urlMethod = isset($this->urlArray[2]) ? $this->urlArray[2] : 'index';

            // Rotas específicas da dashboard
            if ($this->urlArray[1] === 'dashboard') {
                // A lógica para dashboard pode ser mais simples aqui, já que o controller já é "Dashboard"
                // e o método já está sendo pego da URL.
                // Ex: adms/dashboard/detalhes -> Controller: Dashboard, Método: detalhes
                // Ex: adms/dashboard -> Controller: Dashboard, Método: index
            } 
            // Rotas específicas do perfil
            else if ($this->urlArray[1] === 'perfil') {
                // Se a URL for adms/perfil/atualizarFoto ou adms/perfil/atualizarNome
                // O método já é 'atualizarFoto' ou 'atualizarNome'
                // Se for adms/perfil, o método é 'index'
                // A variável $this->urlMethod já está configurada corretamente acima
            }
            // Você pode adicionar mais condições 'else if' para outros controladores
            // específicos que precisem de lógica de roteamento especial.
            // Caso contrário, ele segue a lógica padrão de pegar o controlador e o método da URL.
        } else {
            // Se não houver urlArray[1], volta para o controlador padrão da ADM (ex: Dashboard)
            $this->urlController = $this->slugController(CONTROLLERADM);
            $this->urlMethod = 'index';
        }
    }

    private function processDefaultRoutes(): void
    {
        $this->urlController = isset($this->urlArray[0])
            ? $this->slugController($this->urlArray[0])
            : $this->slugController(CONTROLLER);
        $this->urlMethod = isset($this->urlArray[1]) ? $this->urlArray[1] : 'index';
    }

    private function clearUrl(): void
    {
        $this->url = strip_tags(trim(rtrim($this->url, "/")));
        $this->format['a'] = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]?;:.,\\\'<>°ºª ';
        $this->format['b'] = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr-------------------------------------------------------------------------------------------------';
        $this->url = strtr(iconv("UTF-8", "ISO-8859-1", $this->url), iconv("UTF-8", "ISO-8859-1", $this->format['a']), $this->format['b']);
    }

    private function slugController(string $slugController): string
    {
        return str_replace(" ", "", ucwords(str_replace("-", " ", strtolower($slugController))));
    }

    public function loadPage(): void
    {
        $namespaceBase = (strpos($_SERVER['REQUEST_URI'], '/adms') !== false)
            ? '\\Adms\\Controllers\\'
            : '\\Sts\\Controllers\\';

        $this->classLoad = $namespaceBase . $this->urlController;

        if (class_exists($this->classLoad)) {
            $this->loadClass();
        } else {
            $this->handleControllerNotFound($namespaceBase);
        }
    }

    private function handleControllerNotFound(string $namespaceBase): void
    {
        if ($this->urlController != $this->slugController(CONTROLLERERRO)) {
            $this->urlController = $this->slugController(CONTROLLERERRO);
            $this->classLoad = $namespaceBase . $this->urlController;
            $this->loadClass();
        } else {
            die("Erro: Página de erro não encontrada.");
        }
    }

    private function loadClass(): void
    {
        $this->classPage = new $this->classLoad();

        if (method_exists($this->classPage, $this->urlMethod)) {
            $this->callControllerMethod();
        } else {
            $this->handleMethodNotFound();
        }
    }

    private function callControllerMethod(): void
    {
        $reflectionMethod = new \ReflectionMethod($this->classPage, $this->urlMethod);
        $parameters = $reflectionMethod->getParameters();

        if (empty($parameters)) {
            $this->classPage->{$this->urlMethod}();
        } else {
            $this->callMethodWithParameters($reflectionMethod, $parameters);
        }
    }

    private function callMethodWithParameters(\ReflectionMethod $reflectionMethod, array $parameters): void
    {
        $args = [];
        foreach ($parameters as $param) {
            // Se o parâmetro for 'formData' e a requisição for POST, passa o $_POST inteiro
            if ($param->getName() === 'formData' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $args[] = $_POST;
            } 
            // Para upload de arquivos, você também pode precisar de um parâmetro para $_FILES
            // Exemplo: if ($param->getName() === 'fileData' && $_SERVER['REQUEST_METHOD'] === 'POST') { $args[] = $_FILES; }
            // Ou o seu controlador pode pegar diretamente $_FILES
            elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $this->handleMissingParameter($param);
            }
        }
        $this->classPage->{$this->urlMethod}(...$args);
    }

    private function handleMissingParameter(\ReflectionParameter $param): void
    {
        error_log("ERRO: Parâmetro obrigatório '" . $param->getName() . "' para " . $this->urlMethod . " não pode ser resolvido.");
        // Em um ambiente AJAX, você pode querer retornar um JSON de erro aqui em vez de redirecionar.
        // header("Location: " . URLADM . "erro/index/500");
        // exit(); 
        // Para AJAX, você pode fazer algo como:
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: Parâmetro ausente para ' . $this->urlMethod]);
            exit();
        } else {
            header("Location: " . URLADM . "erro/index/500");
            exit();
        }
    }

    private function handleMethodNotFound(): void
    {
        if (method_exists($this->classPage, 'index')) {
            $this->callIndexMethod();
        } else {
            die("Erro: Método '{$this->urlMethod}' ou 'index' não encontrado na controller {$this->classLoad}. Contate o administrador: " . EMAILADM);
        }
    }

    private function callIndexMethod(): void
    {
        $reflectionMethod = new \ReflectionMethod($this->classPage, 'index');
        $parameters = $reflectionMethod->getParameters();

        if (empty($parameters)) {
            $this->classPage->index();
        } else {
            $this->callIndexWithParameters($parameters);
        }
    }

    private function callIndexWithParameters(array $parameters): void
    {
        $args = [];
        foreach ($parameters as $param) {
            if ($param->isOptional()) {
                $args[] = $param->getDefaultValue();
            } elseif (isset($this->urlArray[2])) {
                $args[] = $this->urlArray[2];
            } else {
                $this->handleMissingParameter($param);
            }
        }
        $this->classPage->index(...$args);
    }
}