<?php

namespace Adms\CoreAdm;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
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
        
        // Log para verificar a sessão no início do construtor
        error_log("DEBUG CONFIGADM: Sessão user_level_numeric no início do __construct(): " . ($_SESSION['user_level_numeric'] ?? 'NÃO DEFINIDO'));

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
        error_log("DEBUG ROUTING: URL Array (após explode): " . print_r($this->urlArray, true));

        if ($this->isAdmsRequest) {
            $this->urlController = $this->slugController($this->urlArray[0] ?? CONTROLLERADM);
            $this->urlMethod = isset($this->urlArray[1]) ? $this->urlArray[1] : 'index';
            error_log("DEBUG ROUTING: Área ADM Detectada (via SCRIPT_NAME). Controller: " . $this->urlController . ", Method: " . $this->urlMethod);
        } else {
            $this->urlController = isset($this->urlArray[0])
                ? $this->slugController($this->urlArray[0])
                : $this->slugController(CONTROLLER);
            $this->urlMethod = isset($this->urlArray[1]) ? $this->urlArray[1] : 'index';
            error_log("DEBUG ROUTING: Área STS Detectada (via SCRIPT_NAME). Controller: " . $this->urlController . ", Method: " . $this->urlMethod);
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
        error_log("DEBUG LOADPAGE: loadPage() foi alcançado."); 

        $namespaceBase = $this->isAdmsRequest ? '\\Adms\\Controllers\\' : '\\Sts\\Controllers\\';

        $this->classLoad = $namespaceBase . $this->urlController;
        error_log("DEBUG LOADPAGE: Tentando carregar classe: " . $this->classLoad);

        if ($this->isAdmsRequest) {
            if (array_key_exists($this->urlController, $this->listControllers)) {
                $requiredLevel = $this->listControllers[$this->urlController];
                $userLevel = $_SESSION['user_level_numeric'] ?? 0;
                error_log("DEBUG LOADPAGE: Controlador ADM. Requer nível: " . $requiredLevel . ", Nível do usuário: " . $userLevel);

                if (($this->urlController === 'Login' || $this->urlController === 'Cadastro') && $userLevel > 0) { 
                    error_log("DEBUG LOADPAGE: Usuário já logado (" . $userLevel . "). Redirecionando de " . $this->urlController . " para Dashboard.");
                    header("Location: " . URLADM . "dashboard");
                    exit();
                }

                if ($userLevel >= $requiredLevel) {
                    if (class_exists($this->classLoad)) {
                        $this->classPage = new $this->classLoad();
                        // CORREÇÃO CRÍTICA AQUI: Verifica se o método existe E está na lista de permitidos
                        if (method_exists($this->classPage, $this->urlMethod) && in_array($this->urlMethod, $this->listMethods[$this->urlController] ?? [])) {
                            error_log("DEBUG LOADPAGE: Chamando método: " . $this->urlMethod . " na classe: " . $this->classLoad);
                            $this->callControllerMethod();
                        } elseif (method_exists($this->classPage, 'index') && in_array('index', $this->listMethods[$this->urlController] ?? [])) {
                            $this->urlMethod = 'index'; 
                            error_log("DEBUG LOADPAGE: Método não encontrado na lista, chamando index() na classe: " . $this->classLoad);
                            $this->callControllerMethod();
                        } else {
                            error_log("ERRO LOADPAGE: Método '" . $this->urlMethod . "' não encontrado ou não permitido para " . $this->classLoad);
                            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Método não encontrado ou acesso negado para este controlador!'];
                            header("Location: " . URLADM . "dashboard");
                            exit();
                        }
                    } else {
                        error_log("ERRO LOADPAGE: Classe do controlador ADM não existe: " . $this->classLoad);
                        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Controlador administrativo não encontrado!'];
                        header("Location: " . URLADM . "dashboard");
                        exit();
                    }
                } else {
                    error_log("ERRO LOADPAGE: Nível de acesso insuficiente. Usuário: " . $userLevel . ", Requerido: " . $requiredLevel . " para " . $this->urlController);
                    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Você não tem permissão para acessar esta página!'];
                    header("Location: " . URLADM . "dashboard");
                    exit();
                }
            } else {
                error_log("ERRO LOADPAGE: Controlador ADMS não está na lista de permitidos: " . $this->urlController);
                $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro: Controlador administrativo não permitido!'];
                header("Location: " . URLADM . "dashboard");
                exit();
            }
        } else {
            error_log("DEBUG LOADPAGE: Controlador STS. Classe: " . $this->classLoad);
            if (class_exists($this->classLoad)) {
                $this->classPage = new $this->classLoad();
                if (method_exists($this->classPage, $this->urlMethod)) {
                    error_log("DEBUG LOADPAGE: Chamando método STS: " . $this->urlMethod . " na classe: " . $this->classLoad);
                    $this->callControllerMethod();
                } elseif (method_exists($this->classPage, 'index')) {
                    $this->urlMethod = 'index';
                    error_log("DEBUG LOADPAGE: Método STS não encontrado, chamando index() na classe: " . $this->classLoad);
                    $this->callControllerMethod();
                } else {
                    error_log("ERRO LOADPAGE: Método STS '" . $this->urlMethod . "' ou 'index' não encontrado na controller " . $this->classLoad);
                    $this->handleMethodNotFound(); 
                }
            } else {
                error_log("ERRO LOADPAGE: Classe do controlador STS não existe: " . $this->classLoad);
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
            'Perfil' => ['index', 'update', 'softDeleteAccount', 'deleteMyAccount', 'atualizarNome', 'atualizarFoto', 'removerFoto', 'atualizarSenha'], // <--- CORREÇÃO AQUI!
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
        error_log("ERRO: Parâmetro obrigatório '" . $param->getName() . "' para " . $this->urlMethod . " não pode ser resolvido.");
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: Parâmetro ausente para ' . $this->urlMethod]);
            exit();
        } else {
            $_SESSION['msg'] = ['type' => 'error', 'text' => 'Erro interno do servidor: Parâmetro ausente para a ação solicitada.'];
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