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
use Adms\Controllers\Contato;
use Adms\Controllers\TesteContato;
use Adms\Controllers\Notificacoes;
use Adms\Controllers\AdminPaymentsController; 
use Adms\Models\AdmsUser;

class ConfigControllerAdm extends ConfigAdm // Assumindo que ConfigAdm é sua classe base
{
    private string $url;
    private array $urlArray;
    private string $urlController;
    private ?string $urlMethod;
    private array $urlParams = [];
    private string $urlSlugController;
    private array $format;
    private string $classLoad;
    private object $classPage;
    private bool $isAdmsRequest; 

    private array $listControllers;
    private array $listMethods;

    private array $urlToControllerMap = [
        'cadastro' => 'Cadastro', 
        'adminusers' => 'AdminUsersController',
        'payment' => 'PaymentController',
        'pagamento' => 'PaymentController',
        'pix' => 'PaymentController',
        'pagar' => 'PaymentController',
        'checkout' => 'PaymentController',
        'assinatura' => 'PaymentController',
        'planos' => 'Planos',
        'admin-payments' => 'AdminPaymentsController',
        'adminpayments' => 'AdminPaymentsController',
        'gerenciar-pagamentos' => 'AdminPaymentsController',
        'pagamentos' => 'AdminPaymentsController',
        'financeiro' => 'FinanceiroController',
    ];

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Definir user_level_numeric se não estiver definido
        if (!isset($_SESSION['user_level_numeric']) && (isset($_SESSION['user_level']) || isset($_SESSION['user_role']))) {
            $userLevelName = $_SESSION['user_level'] ?? $_SESSION['user_role'] ?? 'usuario';
            $numericUserLevel = 0;
            
            if ($userLevelName === 'administrador') {
                $numericUserLevel = 3; 
            } elseif ($userLevelName === 'usuario' || $userLevelName === 'normal') {
                $numericUserLevel = 1; 
            }
            $_SESSION['user_level_numeric'] = $numericUserLevel;
            
            error_log("DEBUG CONFIGADM: user_level_numeric definido: " . $numericUserLevel . " para role: " . $userLevelName);
        }
        
        // Log para verificar a sessão no início do construtor
        error_log("DEBUG CONFIGADM: Sessão user_level_numeric no início do __construct(): " . ($_SESSION['user_level_numeric'] ?? 'NÃO DEFINIDO'));

        $this->config(); 

        // Detecta requisições ADMS também por REQUEST_URI contendo '/adms/' (cobre AJAX onde SCRIPT_NAME pode apontar para outro index)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $this->isAdmsRequest = (
            basename($scriptName) === 'index_admin.php'
            || stripos($requestUri, '/adms/') !== false
        );
        error_log("DEBUG ROUTING: SCRIPT_NAME: " . ($scriptName ?: 'N/A') . ", REQUEST_URI: " . ($requestUri ?: 'N/A') . ", ADM_DIR: " . ADM_DIR . ", Is ADMS Request: " . ($this->isAdmsRequest ? 'true' : 'false'));

        // Sincronizar sessão do usuário logado com o banco para refletir alterações feitas por admin imediatamente
        try {
            if (isset($_SESSION['user_id'])) {
                $userModel = new AdmsUser();
                $fresh = $userModel->getUserById((int)$_SESSION['user_id']);
                if ($fresh) {
                    if (isset($fresh['nome'])) { $_SESSION['user_name'] = $fresh['nome']; }
                    if (isset($fresh['email'])) { $_SESSION['user_email'] = $fresh['email']; }
                    if (isset($fresh['nivel_acesso'])) { $_SESSION['user_role'] = $fresh['nivel_acesso']; }
                    if (isset($fresh['status'])) { $_SESSION['user_status'] = $fresh['status']; }
                    if (isset($fresh['plan_type'])) { $_SESSION['user_plan'] = $fresh['plan_type']; }
                    if (isset($fresh['payment_status'])) { $_SESSION['payment_status'] = $fresh['payment_status']; }
                    if (isset($fresh['foto'])) { $_SESSION['user_photo_path'] = $fresh['foto']; }
                } else {
                    // Usuário foi excluído (ou não existe mais): invalidar sessão e redirecionar para home do STS
                    // Detectar requisição AJAX para responder adequadamente
                    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

                    // Limpar sessão com segurança
                    $_SESSION = [];
                    if (ini_get('session.use_cookies')) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                    }
                    session_destroy();

                    if ($isAjax) {
                        http_response_code(401);
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'forceLogout' => true,
                            'redirect' => URL, // home do STS
                            'message' => 'Sua sessão foi encerrada. Sua conta não existe mais.'
                        ]);
                        exit();
                    } else {
                        header('Location: ' . URL);
                        exit();
                    }
                }
            }
        } catch (\Exception $e) { /* silencioso */ }

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
        error_log("DEBUG ROUTING: REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        error_log("DEBUG ROUTING: SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A'));
        error_log("DEBUG ROUTING: HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'N/A'));

        if ($this->isAdmsRequest) {
            $this->urlController = $this->slugController($this->urlArray[0] ?? CONTROLLERADM);
            $this->urlMethod = isset($this->urlArray[1]) ? $this->urlArray[1] : 'index';
            
            // Capturar parâmetros do path para URLs amigáveis
            $this->urlParams = array_slice($this->urlArray, 2);
            if (!empty($this->urlParams)) {
                // Se há parâmetros no path, definir como método com parâmetro
                if (count($this->urlParams) === 1) {
                    // Exemplo: /pix/basic -> método pix com parâmetro basic
                    $this->urlMethod = $this->urlController; // pix
                    $this->urlParams = [$this->urlArray[1]]; // [basic]
                }
            }
            
            error_log("DEBUG ROUTING: Área ADM Detectada (via SCRIPT_NAME). Controller: " . $this->urlController . ", Method: " . $this->urlMethod);
            error_log("DEBUG ROUTING: Parâmetros do path: " . print_r($this->urlParams, true));
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
        } else {
            error_log("DEBUG ROUTING: Nenhum mapeamento encontrado para '" . $this->urlController . "'");
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

                // Redirecionamento inteligente baseado no controlador
                if ($userLevel > 0) {
                    if ($this->urlController === 'Login') {
                        // Usuário logado tentando acessar login - redirecionar para dashboard
                        error_log("DEBUG LOADPAGE: Usuário já logado (" . $userLevel . "). Redirecionando de Login para Dashboard.");
                        header("Location: " . URLADM . "dashboard");
                        exit();
                    } elseif ($this->urlController === 'Cadastro') {
                        // Usuário logado tentando acessar cadastro - permitir (pode ser para criar nova conta)
                        error_log("DEBUG LOADPAGE: Usuário logado (" . $userLevel . ") acessando Cadastro - permitindo acesso.");
                        // Não redirecionar, continuar com o fluxo normal
                    }
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
                    error_log("ERRO LOADPAGE: Nível de acesso insuficiente. Usuário: " . $userLevel . ", Requerido: " . $requiredLevel . " para " . $this->classLoad);
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
            'Usuario' => 3,
            'PaymentController' => 1, 
            'AdminPaymentsController' => 3, // Apenas administradores
            'Contato' => 1, // Usuários normais e admins podem usar
            'TesteContato' => 1, // Para teste
            'Notificacoes' => 3, // Apenas administradores
            'Planos' => 0, // Acesso público e usuários logados
            'FinanceiroController' => 1,
        ];
    }

    private function listMethods(): void 
    {
        $this->listMethods = [
            'Dashboard' => ['index', 'getAnunciosData', 'getUsersData'],
            'Anuncio' => [
                'index', 'createAnuncio', 'editarAnuncio', 'updateAnuncio', 'visualizarAnuncio', 
                'pausarAnuncio', 'updateAnuncioStatus', 'toggleAnuncioStatus', 
                'approveAnuncio', 'rejectAnuncio', 'activateAnuncio', 'deactivateAnuncio', 'deleteAnuncio',
                'adminAction'
            ],
            // ATUALIZAÇÃO: Adicionado o método 'deleteAccount' para exclusão do perfil do usuário.
            // ATUALIZAÇÃO: Adicionado 'getUserInfo' para atualização do Topbar via SPA
            'Perfil' => ['index', 'update', 'softDeleteAccount', 'deleteMyAccount', 'atualizarNome', 'atualizarFoto', 'removerFoto', 'atualizarSenha', 'deleteAccount', 'getUserInfo'],
            'Login' => ['index', 'autenticar'],
            'Logout' => ['index'],
            'Cadastro' => ['index', 'salvar'],
            'RecoverPassword' => ['index', 'recover'],
            'UpdatePassword' => ['index', 'update'],
            // ATUALIZAÇÃO: Adicionado o método 'deleteUserAccountByAdmin' para que o administrador possa excluir contas.
            'AdminUsersController' => ['index', 'listUsers', 'viewUser', 'updateUser', 'updateUserStatus', 'updateUserPlan', 'deleteUser', 'getStats'],
            'Usuario' => ['deleteAccount'],
            'PaymentController' => ['index', 'create', 'status', 'webhook', 'devApprove'],
            'AdminPaymentsController' => ['index', 'list', 'approve', 'reject'],
            'Contato' => ['enviarMensagemDireta', 'enviarFormularioContato', 'getMensagens', 'getContador', 'marcarLida', 'responderMensagem', 'marcarTodasComoLidas'],
            'TesteContato' => ['enviarMensagemDireta'],
            'Notificacoes' => ['getNotificacoes', 'getContador', 'marcarLida'],
            'Planos' => ['index', 'setSelectedPlan', 'changePlan', 'getPlans'],
            'FinanceiroController' => ['index'],
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
        $paramIndex = 0;
        
        foreach ($parameters as $param) {
            if ($param->getName() === 'formData' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $args[] = $_POST;
            } 
            elseif ($param->getName() === 'plan' && !empty($this->urlParams)) {
                // Passar parâmetros do path (ex: /pix/basic -> plan = basic)
                $args[] = $this->urlParams[$paramIndex] ?? $param->getDefaultValue();
                $paramIndex++;
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

