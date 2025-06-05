<?php

/**
 * Header da Área Administrativa
 * 
 * Contém todas as meta tags, CSS e scripts iniciais
 * 
 * @package nixcom-adm
 * @version 1.1
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
    <title><?= $this->data['title'] ?? 'Nixcom - Área Administrativa' ?></title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= URLADM ?>assets/images/icon/favicon.ico" type="image/x-icon">

    <!-- Bootstrap CSS (versão 5.1.3 recomendada) -->
    <link href="<?= URLADM ?>assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 (ícones) -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/fontawesome/css/all.min.css">

    <!-- CSS Customizado -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/login.css">
    
    <!-- Pré-carregamento de fontes -->
    <link rel="preload" href="<?= URLADM ?>assets/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>

    <!-- JS Global (configurações iniciais) -->
    <script>
        // Configurações globais
        const URL_ADM = '<?= URLADM ?>';
        const SITE_NAME = '<?= SITE_NAME ?? "Nixcom" ?>';

        // Detecta recursos do navegador
        document.documentElement.classList.add(
            'js',
            'css-vars',
            localStorage.getItem('darkMode') === 'true' ? 'dark-mode' : 'light-mode'
        );
    </script>
</head>

<body class="login-page">