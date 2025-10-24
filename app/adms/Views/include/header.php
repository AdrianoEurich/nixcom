<?php

/**
 * Header da Área Administrativa
 * 
 * Contém todas as meta tags, CSS e scripts iniciais
 * 
 * @package GPHUB-adm
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

// Incluir sistema anti-cache
require_once 'app/adms/CoreAdm/AntiCache.php';

// Headers anti-cache para desenvolvimento
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
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
    
    <!-- Meta tags anti-cache para desenvolvimento -->
    <?php if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false): ?>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <?php endif; ?>

    <!-- Título dinâmico da página -->
    <title><?= $this->data['title'] ?? 'GPHUB - Área Administrativa' ?></title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= URLADM ?>assets/images/icon/favicon.ico?<?= CACHE_BUSTER ?>" type="image/x-icon">

    <!-- Bootstrap CSS (versão 5.1.3 recomendada) -->
    <link href="<?= URLADM ?>assets/bootstrap/css/bootstrap.min.css?<?= CACHE_BUSTER ?>" rel="stylesheet">
    <!-- Font Awesome 6 (ícones) - CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/login.css?<?= CACHE_BUSTER ?>">
    <!-- CSS dos Modais Bonitos - DEVE SER O ÚLTIMO CSS -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/modal-feedback-beautiful.css?v=<?= time() . '_' . rand(1000, 9999); ?>">

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
    
    <!-- Sistema Anti-Cache para Desenvolvimento -->
    <?php if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false): ?>
    <script src="<?= URLADM ?>assets/js/anti-cache.js?<?= CACHE_BUSTER ?>"></script>
    <?php endif; ?>
    
    <!-- Sistema de Atualização em Tempo Real -->
    <script src="<?= URLADM ?>assets/js/real-time-updater.js?<?= CACHE_BUSTER ?>"></script>
    
    <!-- Carregador de Estilos para Seções Administrativas -->
    <script src="<?= URLADM ?>assets/js/admin-sections-loader.js?<?= CACHE_BUSTER ?>"></script>
    
    <!-- CSS para seções administrativas -->
    <link rel="stylesheet" href="<?= URLADM ?>assets/css/admin-sections.css?<?= CACHE_BUSTER ?>">
</head>

<body class="login-page">