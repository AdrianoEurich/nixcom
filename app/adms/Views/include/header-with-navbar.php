<?php

/**
 * Header para Páginas de Login/Cadastro com Navbar
 * 
 * Inclui o navbar da área pública para manter consistência visual
 * 
 * @package nixcom-adm
 * @version 1.2
 */

// ================================================
// SEGURANÇA E CONFIGURAÇÕES INICIAIS
// ================================================

// Bloqueia acesso direto ao arquivo
if (!defined('C7E3L8K9E5')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Acesso direto não permitido!");
}

// Fallbacks para constantes não definidas
$siteTitle = defined('SITE_NAME') ? SITE_NAME : 'Nixcom';
$pageTitle = $this->data['title'] ?? 'Área Administrativa';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <!-- Meta tags essenciais -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Título dinâmico da página -->
    <title><?= $this->data['title'] ?? 'GPHub - Área Administrativa' ?></title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= URLADM ?>assets/images/icon/favicon.ico" type="image/x-icon">

    <!-- Bootstrap CSS (versão 5.1.3 recomendada) -->
    <link href="<?= URLADM ?>assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 (ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- CSS da área pública para o navbar -->
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/css/site.css">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= URL ?>app/sts/assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS específico para navbar nas páginas de login -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/navbar-login.css">
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/login.css">
    <!-- CSS do Modal de Loading - IGUAL AO APROVAR/REPROVAR -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/loading-modal-fix.css?v=<?= time(); ?>">
    <!-- CSS dos Modais Bonitos - DEVE SER O ÚLTIMO CSS -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/modal-feedback-beautiful.css?v=<?= time() . '_' . rand(1000, 9999); ?>">

    <script>
        // Definir baseUrl globalmente
        window.baseUrl = '<?= URL ?>';
    </script>
    
    <!-- CSS específico da página -->
    <?php
    $currentPage = $_GET['url'] ?? '';
    $cssFile = '';
    
    switch($currentPage) {
        case 'cadastro':
            $cssFile = 'cadastro.css';
            break;
        case 'pagamento':
            $cssFile = 'pagamento.css';
            break;
        case 'planos':
            $cssFile = 'planos.css';
            break;
    }
    
    if ($cssFile) {
        echo '<link rel="stylesheet" href="' . URLADM . 'assets/css/' . $cssFile . '">';
    }
    ?>
    
    <!-- Pré-carregamento de fontes -->

    <!-- JS Global (configurações iniciais) -->
    <script>
        // Configurações globais
        const URL_ADM = '<?= URLADM ?>';
        const URL = '<?= URL ?>';
        const SITE_NAME = '<?= SITE_NAME ?? "Nixcom" ?>';

        // Detecta recursos do navegador
        document.documentElement.classList.add(
            'js',
            'css-vars',
            localStorage.getItem('darkMode') === 'true' ? 'dark-mode' : 'light-mode'
        );
    </script>
</head>

<body class="login-page" data-bs-spy="scroll" data-bs-target="#navbar">
    <!-- Navbar da área pública -->
    <nav id="navbar" class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <span class="brand-highlight">GP</span>HUB
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= URL ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= URL ?>#acompanhantes">Acompanhantes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= URL ?>#contato">Contato</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URLADM; ?>login">Login</a>
                    </li>
                    <li class="nav-item nav-item-btn">
                        <?php
                        use Sts\Models\Helper\LoginHelperImproved;
                        $isLoggedIn = LoginHelperImproved::isLoggedIn();
                        $buttonUrl = LoginHelperImproved::getRedirectUrl(URLADM . "cadastro");
                        $buttonText = LoginHelperImproved::getButtonText("Anuncie Grátis");
                        $buttonClass = LoginHelperImproved::getButtonClass("btn btn-animate");
                        ?>
                        <a class="<?= $buttonClass ?>" href="<?= $buttonUrl ?>"><?= $buttonText ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
