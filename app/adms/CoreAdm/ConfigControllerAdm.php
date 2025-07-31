<?php

namespace Adms\CoreAdm;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: P\xc3\xa1gina n\xc3\xa3o encontrada!");
}

// Importações dos controladores
use Sts\Controllers\LoginController as StsLoginController; 
use Adms\Controllers\Login as AdmsLoginController; 
use Adms\Controllers\AdminUsersController; 
use Adms\Controllers\Anuncio; 
use Adms\Controllers\Dashboard; 
use Adms\Controllers\Perfil; 
use Adms\Controllers\Logout; 
use Adms\Controllers\Cadastro; 
use Adms\Controllers\RecoverPassword; 
use Adms\Controllers\UpdatePassword; 

class ConfigControllerAdm extends ConfigAdm // Assumindo que ConfigAdm é sua classe base
{
    private string $url;
    private array $urlArray;
    private string $urlController;
    private ?string $urlMethod;
    private string $urlSlugController;
    private array $format;
    private string $classLoad;
    private object $classPage;
    private bool $isAdmsRequest; 

    private array $listControllers;
    private array $listMethods;

    private array $urlToControllerMap = [
        'cadastro' => 'Cadastro', 
    ];

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->config(); 

        $this->isAdmsRequest = (basename($_SERVER['SCRIPT_NAME']) === 'index_admin.php');
        error_log("DEBUG ROUTING: SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . ", ADM_DIR: " . ADM_DIR . ", Is ADMS Request: " . ($this->isAdmsRequest ? 'true' : 'false'));

        $this->processUrl(); 
        $this->listControllers(); 
        $this->listMethods(); 
    }

    private function processUrl(): void
    {
        $this->url = filter_input(INPUT_GET, 'url', FILTER_DEFAULT) ?? ''; 
        $this->clearUrl();
        $this->urlArray = explode("/", $this->url);

        error_log("DEBUG ROUTING: URL Bruta Recebida (GET['url']): " . ($this->url ?? 'Vazia'));
        error_log("DEBUG ROUTING: URL Array (ap\xc3\xb3s explode): " . print_r($this->urlArray, true));

        if ($this->isAdmsRequest) {
            $this->urlController = $this->slugController($this->urlArray[0] ?? CONTROLLERADM);
            $this->urlMethod = isset($this->urlArray[1]) ? $this->urlArray[1] : 'index';
            error_log("DEBUG ROUTING: \xc3\x81rea ADM Detectada (via SCRIPT_NAME). Controller: " . $this->urlController . ", Method: " . $this->urlMethod);
        } else {
            $this->urlController = isset($this->urlArray[0])
                ? $this->slugController($this->urlArray[0])
                : $this->slugController(CONTROLLER);
            $this->urlMethod = isset($this->urlArray[1]) ? $this->urlArray[1] : 'index';
            error_log("DEBUG ROUTING: \xc3\x81rea STS Detectada (via SCRIPT_NAME). Controller: " . $this->urlController . ", Method: " . $this->urlMethod);
        }

        if (isset($this->urlToControllerMap[strtolower($this->urlController)])) { 
            error_log("DEBUG ROUTING: Mapeando URL '" . $this->urlController . "' para Controller '" . $this->urlToControllerMap[strtolower($this->urlController)] . "'");
            $this->urlController = $this->urlToControllerMap[strtolower($this->urlController)];
        }

        $this->urlMethod = $this->slugMethod($this->urlMethod);

        error_log("DEBUG ConfigControllerAdm: URL Controller (processed): " . $this->urlController);
        error_log("DEBUG ConfigControllerAdm: URL Method (processed): " . $this->urlMethod);
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

    private function slugMethod(string $slugMethod): string
    {
        $slugMethod = str_replace("-", " ", $slugMethod);
        $slugMethod = ucwords($slugMethod);
        $slugMethod = str_replace(" ", "", $slugMethod);
        return lcfirst($slugMethod);
    }

    public function loadPage(): void
    {
        error_log("DEBUG LOADPAGE: loadPage() foi alcan\xc3\xa7ado."); 

        $namespaceBase = $this->isAdmsRequest ? '\\Adms\\Controllers\\' : '\\Sts\\Controllers\\';

        $this->classLoad = $namespaceBase . $this->urlController;
        error_log("DEBUG LOADPAGE: Tentando carregar classe: " . $this->classLoad);

        if ($this->isAdmsRequest) {
            if (array_key_exists($this->urlController, $this->listControllers)) {
                $requiredLevel = $this->listControllers[$this->urlController];
                $userLevel = $_SESSION['user_level_numeric'] ?? 0;
                error_log("DEBUG LOADPAGE: Controlador ADM. Requer n\xc3\xadvel: " . $requiredLevel . ", N\xc3\xadvel do usu\xc3\xa1rio: " . $userLevel);

                if (($this->urlController === 'Login' || $this->urlController === 'Cadastro') && $userLevel > 0) { 
                    error_log("DEBUG LOADPAGE: Usu\xc3\xa1rio j\xc3\xa1 logado (" . $userLevel . "). Redirecionando de " . $this->urlController . " para Dashboard.");
                    header("Location: " . URLADM . "dashboard");
                    exit();
                }

                if ($userLevel >= $requiredLevel) {
                    if (class_exists($this->classLoad)) {
                        $this->classPage = new $this->classLoad();
                        // CORREÇÃO CRÍTICA AQUI: Verifica se o método existe E está na lista de permitidos
                        if (method_exists($this->classPage, $this->urlMethod) && in_array($this->urlMethod, $this->listMethods[$this->urlController] ?? [])) {
                            error_log("DEBUG LOADPAGE: Chamando m\xc3\xa9todo: " . $this->urlMethod . " na classe: " . $this->classLoad);
                            $this->callControllerMethod();
                        } elseif (method_exists($this->classPage, 'index') && in_array('index', $this->listMethods[$this->urlController] ?? [])) {
                            $this->urlMethod = 'index'; 
                            error_log("DEBUG LOADPAGE: M\xc3\xa9todo n\xc3\xa3o encontrado na lista, chamando index() na classe: " . $this->classLoad);
                            $this->callControllerMethod();
                        } else {
                            error_log("ERRO LOADPAGE: M\xc3\xa9todo '" . $this->urlMethod . "' n\xc3\xa3o encontrado ou n\xc3\xa3o permitido para " . $this->classLoad);
                            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: M\xc3\xa9todo n\xc3\xa3o encontrado ou acesso negado para este controlador!'];
                            header("Location: " . URLADM . "dashboard");
                            exit();
                        }
                    } else {
                        error_log("ERRO LOADPAGE: Classe do controlador ADM n\xc3\xa3o existe: " . $this->classLoad);
                        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Controlador administrativo n\xc3\xa3o encontrado!'];
                        header("Location: " . URLADM . "dashboard");
                        exit();
                    }
                } else {
                    error_log("ERRO LOADPAGE: N\xc3\xadvel de acesso insuficiente. Usu\xc3\xa1rio: " . $userLevel . ", Requerido: " . $requiredLevel . " para " . $this->urlController);
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Voc\xc3\xaa n\xc3\xa3o tem permiss\xc3\xa3o para acessar esta p\xc3\xa1gina!'];
                    header("Location: " . URLADM . "dashboard");
                    exit();
                }
            } else {
                error_log("ERRO LOADPAGE: Controlador ADMS n\xc3\xa3o est\xc3\xa1 na lista de permitidos: " . $this->urlController);
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Controlador administrativo n\xc3\xa3o permitido!'];
                header("Location: " . URLADM . "dashboard");
                exit();
            }
        } else {
            error_log("DEBUG LOADPAGE: Controlador STS. Classe: " . $this->classLoad);
            if (class_exists($this->classLoad)) {
                $this->classPage = new $this->classLoad();
                if (method_exists($this->classPage, $this->urlMethod)) {
                    error_log("DEBUG LOADPAGE: Chamando m\xc3\xa9todo STS: " . $this->urlMethod . " na classe: " . $this->classLoad);
                    $this->callControllerMethod();
                } elseif (method_exists($this->classPage, 'index')) {
                    $this->urlMethod = 'index';
                    error_log("DEBUG LOADPAGE: M\xc3\xa9todo STS n\xc3\xa3o encontrado, chamando index() na classe: " . $this->classLoad);
                    $this->callControllerMethod();
                } else {
                    error_log("ERRO LOADPAGE: M\xc3\xa9todo STS '" . $this->urlMethod . "' ou 'index' n\xc3\xa3o encontrado na controller " . $this->classLoad);
                    $this->handleMethodNotFound(); 
                }
            } else {
                error_log("ERRO LOADPAGE: Classe do controlador STS n\xc3\xa3o existe: " . $this->classLoad);
                $this->handleControllerNotFound($namespaceBase); 
            }
        }
    }

    private function listControllers(): void
    {
        $this->listControllers = [
            'Dashboard' => 1,
            'Anuncio' => 1, // Anuncio agora gerenciará todas as ações de anúncio, incluindo as de admin
            'Perfil' => 1,
            'Login' => 0, 
            'Logout' => 1, 
            'Cadastro' => 0, 
            'RecoverPassword' => 0, 
            'UpdatePassword' => 0, 
            'AdminUsersController' => 3, 
            // 'AdminAnunciosController' => 3, // REMOVIDO: Métodos movidos para 'Anuncio'
        ];
    }

    private function listMethods(): void 
    {
        $this->listMethods = [
            'Dashboard' => ['index', 'getAnunciosData', 'getUsersData'], // Adicionado 'getUsersData'
            'Anuncio' => [
                'index', 
                'createAnuncio', 
                'editarAnuncio', 
                'updateAnuncio', 
                'visualizarAnuncio', 
                'pausarAnuncio', 
                'deleteMyAnuncio', 
                'updateAnuncioStatus', 
                'toggleAnuncioStatus', 
                'approveAnuncio', 
                'rejectAnuncio', 
                'activateAnuncio', 
                'deactivateAnuncio', 
                'deleteAnuncio' 
            ],
            'Perfil' => ['index', 'update', 'softDeleteAccount', 'deleteMyAccount', 'atualizarNome', 'atualizarFoto', 'atualizarSenha'], // <--- CORREÇÃO AQUI!
            'Login' => ['index', 'autenticar'], 
            'Logout' => ['index'],
            'Cadastro' => ['index', 'salvar'], 
            'RecoverPassword' => ['index', 'recover'],
            'UpdatePassword' => ['index', 'update'],
            'AdminUsersController' => ['index', 'softDeleteUser', 'activateUser', 'getUsersAjax', 'deleteUserAccountByAdmin', 'listDeletedAccounts'], 
            // 'AdminAnunciosController' => ['approveAnuncio', 'rejectAnuncio', 'activateAnuncio', 'deactivateAnuncio', 'deleteAnuncio'], // REMOVIDO: Métodos movidos para 'Anuncio'
        ];
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
            if ($param->getName() === 'formData' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $args[] = $_POST;
            } 
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
        error_log("ERRO: Par\xc3\xa2metro obrigat\xc3\xb3rio '" . $param->getName() . "' para " . $this->urlMethod . " n\xc3\xa3o pode ser resolvido.");
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: Par\xc3\xa2metro ausente para ' . $this->urlMethod]);
            exit();
        } else {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro interno do servidor: Par\xc3\xa2metro ausente para a a\xc3\xa7\xc3\xa3o solicitada.'];
            header("Location: " . URLADM . "erro/index/500");
            exit();
        }
    }

    private function handleMethodNotFound(): void
    {
        header("Location: " . URL . CONTROLLERERRO); 
        exit();
    }

    private function handleControllerNotFound(string $namespaceBase): void
    {
        header("Location: " . URL . CONTROLLERERRO); 
        exit();
    }
}
