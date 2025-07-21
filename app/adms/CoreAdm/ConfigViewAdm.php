<?php

namespace Adms\CoreAdm;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class ConfigViewAdm
{
    private string $nameView;
    private array|string|null $data;
    private string $basePath;

    public function __construct(string $nameView, array|string|null $data = null)
    {
        $this->nameView = $nameView;
        $this->data = $data;
        // Define basePath para a pasta 'app/'
        $this->basePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
    }

    /**
     * Carrega views de CONTEÚDO (dashboard, perfil, etc.) DENTRO do layout principal (main.php).
     * Este é o método usado para a carga inicial da página e para a navegação SPA.
     */
    public function loadView(): void
    {
        if (is_array($this->data)) {
            extract($this->data); // Torna as variáveis do controlador disponíveis na view
        }

        // Caminho completo para a view de conteúdo (ex: app/adms/Views/dashboard/content_dashboard.php)
        $viewContent = $this->basePath . $this->nameView . '.php';

        if (!file_exists($viewContent)) {
            die("Erro: View de conteúdo não encontrada: " . $viewContent);
        }

        // Inclui o layout principal (main.php), que por sua vez incluirá $viewContent
        $layoutFullPath = $this->basePath . 'adms/Views/layout/main.php';

        if (file_exists($layoutFullPath)) {
            include $layoutFullPath;
        } else {
            die("Erro: Layout principal 'main.php' não encontrado em: " . $layoutFullPath);
        }
    }

    /**
     * Carrega views de login/cadastro com layout específico (header.php + view + footer.php).
     * Este método é para páginas que não usam o layout do dashboard.
     */
    public function loadViewLogin(): void
    {
        if (is_array($this->data)) {
            extract($this->data); // Torna as variáveis do controlador disponíveis na view
        }

        // Caminho completo para a view de login/cadastro (ex: app/adms/Views/login/login.php)
        $viewFullPath = $this->basePath . $this->nameView . '.php';

        if (!file_exists($viewFullPath)) {
            die("Erro: View de login/cadastro não encontrada: " . $viewFullPath);
        }

        // Inclui header, o conteúdo da view e o footer específicos para login/cadastro
        include $this->basePath . 'adms/Views/include/header.php'; // Header do login
        include $viewFullPath; // Conteúdo do login (login.php ou cadastro.php)
        include $this->basePath . 'adms/Views/include/footer.php'; // Footer do login
    }

    /**
     * Carrega apenas o conteúdo de uma view (sem layout).
     * Este método é usado para requisições AJAX (SPA) que precisam de apenas um pedaço de HTML.
     * Usa output buffering para capturar o HTML da view e retorná-lo.
     */
    public function loadContentView(): void
    {
        if (is_array($this->data)) {
            extract($this->data); // Torna as variáveis do controlador disponíveis na view
        }

        $viewFullPath = $this->basePath . $this->nameView . '.php';

        if (!file_exists($viewFullPath)) {
            // Não use die() aqui, pois pode interferir com a resposta AJAX.
            // Em vez disso, logue o erro e retorne uma string vazia ou um erro formatado.
            error_log("ERRO ConfigViewAdm: Conteúdo da view para SPA não encontrado: " . $viewFullPath);
            echo "<!-- Erro: Conteúdo da view não encontrado. -->"; // Mensagem para debug no cliente
            return;
        }

        // Inicia o buffer de saída
        ob_start();

        // Inclui o conteúdo da view. Tudo que for "echoado" ou HTML puro será capturado.
        include $viewFullPath;

        // Obtém o conteúdo do buffer e o limpa.
        // O conteúdo capturado é então enviado como a resposta da função.
        echo ob_get_clean();
    }
}
