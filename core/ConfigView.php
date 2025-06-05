<?php

namespace Core;

// Redirecionar ou parar o processamento quando o usuário não acessa o arquivo index.php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Carregar as páginas da View
 *
 * Esta classe é responsável por carregar diferentes tipos de views,
 * incluindo páginas padrão, de erro, de cadastro e de recuperação de senha.
 *
 * @author Adriano
 */
class ConfigView
{
    /**
     * Construtor
     *
     * @param string $nameView Caminho (relativo à pasta "app/") da view a ser carregada (sem a extensão .php).
     * Por exemplo: "sts/Views/cadastro/cadastro" para o arquivo localizado em "app/sts/Views/cadastro/cadastro.php"
     * @param array|string|null $data Dados que a view deve receber.
     */
    public function __construct(private string $nameView, private array|string|null $data)
    {
    }

    /**
     * Carrega a view padrão.
     * Tenta localizar a view em "app/{nameView}.php" e, se encontrada, inclui o header, a view e o footer.
     * Se não for encontrada, exibe uma mensagem de erro detalhada.
     *
     * @return void
     */
    public function loadView(): void
    {
        // Monta o caminho absoluto para a view
        $viewPath = __DIR__ . '/../app/' . $this->nameView . '.php';

        if (file_exists($viewPath)) {
            include __DIR__ . '/../app/sts/Views/include/header.php';
            include $viewPath;
            include __DIR__ . '/../app/sts/Views/include/footer.php';
        } else {
            die("Erro: Página não encontrada. Por favor, tente novamente. Caso o problema persista, entre em contato com o administrador " . EMAILADM . ".<br>Caminho procurado: " . $viewPath);
        }
    }

    /**
     * Carrega a view de erro.
     *
     * @return void
     */
    public function loadViewErro(): void
    {
        $viewPath = __DIR__ . '/../app/' . $this->nameView . '.php';

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            die("Erro: Página de erro não encontrada. Por favor, tente novamente. Caso o problema persista, entre em contato com o administrador " . EMAILADM . ".<br>Caminho procurado: " . $viewPath);
        }
    }

    /**
     * Carrega a view da página de cadastro.
     * Inclui os headers e footers específicos para a área de cadastro.
     *
     * @return void
     */
    

    /**
     * Carrega a view da página de recuperação de senha.
     * Inclui os headers e footers específicos para essa página.
     *
     * @return void
     */
    public function loadRecuperaSenhaView(): void
    {
        if (isset($_SESSION['msg'])) {
            $this->data['mensagem'] = $_SESSION['msg'];
            unset($_SESSION['msg']);
        }

        $viewPath = __DIR__ . '/../app/' . $this->nameView . '.php';

        if (file_exists($viewPath)) {
            include __DIR__ . '/../app/sts/Views/include/senha_header.php';
            include $viewPath;
            include __DIR__ . '/../app/sts/Views/include/senha_footer.php';
        } else {
            echo "<h1>Erro</h1><p>Por favor tente novamente. Caso o problema persista, entre em contato com o administrador <a href='mailto:" . EMAILADM . "'>" . EMAILADM . "</a></p>";
            die();
        }
    }
}