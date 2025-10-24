<?php
/**
 * Sistema Anti-Cache para Desenvolvimento
 * Este arquivo adiciona parâmetros de versão aos arquivos CSS e JS
 */

// Definir constante de segurança
if (!defined('C7E3L8K9E5')) {
    define('C7E3L8K9E5', true);
}

// Função para gerar timestamp único
function getCacheBuster() {
    return 'v=' . time();
}

// Função para adicionar cache buster aos arquivos
function addCacheBuster($url) {
    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $separator . getCacheBuster();
}

// Headers anti-cache para desenvolvimento
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

// Definir constantes para URLs com cache buster
if (!defined('CACHE_BUSTER')) {
    define('CACHE_BUSTER', getCacheBuster());
}

// Função helper para incluir CSS com cache buster
function includeCSS($path) {
    echo '<link rel="stylesheet" href="' . addCacheBuster($path) . '">' . "\n";
}

// Função helper para incluir JS com cache buster
function includeJS($path) {
    echo '<script src="' . addCacheBuster($path) . '"></script>' . "\n";
}

// Função para limpar cache do navegador via JavaScript
function getCacheClearScript() {
    return '
    <script>
    // Limpar cache do navegador
    if ("caches" in window) {
        caches.keys().then(function(names) {
            for (let name of names) {
                caches.delete(name);
            }
        });
    }
    
    // Forçar reload sem cache
    if (window.performance && window.performance.navigation.type === 1) {
        window.location.reload(true);
    }
    </script>';
}

// Função para adicionar meta tags anti-cache
function addAntiCacheMeta() {
    echo '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">' . "\n";
    echo '<meta http-equiv="Pragma" content="no-cache">' . "\n";
    echo '<meta http-equiv="Expires" content="0">' . "\n";
}
?>
