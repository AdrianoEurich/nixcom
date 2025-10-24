/**
 * Sistema Anti-Cache para Desenvolvimento
 * Versão 1.0 - Limpeza automática de cache
 */

console.log('🔄 Sistema Anti-Cache carregado');

// Função para limpar cache do navegador
function clearBrowserCache() {
    console.log('🧹 Limpando cache do navegador...');
    
    // Limpar cache do Service Worker
    if ('caches' in window) {
        caches.keys().then(function(names) {
            for (let name of names) {
                caches.delete(name);
                console.log('✅ Cache removido:', name);
            }
        });
    }
    
    // Limpar localStorage e sessionStorage
    try {
        localStorage.clear();
        sessionStorage.clear();
        console.log('✅ Storage limpo');
    } catch (e) {
        console.log('⚠️ Erro ao limpar storage:', e);
    }
    
    // Limpar IndexedDB
    if ('indexedDB' in window) {
        indexedDB.databases().then(databases => {
            databases.forEach(db => {
                indexedDB.deleteDatabase(db.name);
                console.log('✅ IndexedDB removido:', db.name);
            });
        });
    }
}

// Função para forçar reload sem cache
function forceReload() {
    console.log('🔄 Forçando reload sem cache...');
    
    // Método 1: Reload com cache bypass
    window.location.reload(true);
    
    // Método 2: Se o primeiro não funcionar
    setTimeout(() => {
        window.location.href = window.location.href + '?nocache=' + Date.now();
    }, 100);
}

// Função para adicionar timestamp aos recursos
function addCacheBusterToResources() {
    console.log('⏰ Adicionando cache buster aos recursos...');
    
    // Adicionar timestamp aos links CSS
    const cssLinks = document.querySelectorAll('link[rel="stylesheet"]');
    cssLinks.forEach(link => {
        if (!link.href.includes('?')) {
            link.href += '?v=' + Date.now();
        }
    });
    
    // Adicionar timestamp aos scripts
    const scripts = document.querySelectorAll('script[src]');
    scripts.forEach(script => {
        if (!script.src.includes('?')) {
            script.src += '?v=' + Date.now();
        }
    });
}

// Função para detectar mudanças em arquivos
function detectFileChanges() {
    console.log('👀 Monitorando mudanças em arquivos...');
    
    // Verificar se há mudanças nos arquivos principais
    const checkFiles = [
        'assets/css/login.css',
        'assets/js/login.js',
        'assets/css/cadastro.css',
        'assets/js/cadastro.js',
        'assets/css/dashboard_custom.css',
        'assets/js/dashboard_custom.js'
    ];
    
    checkFiles.forEach(file => {
        fetch(file + '?check=' + Date.now())
            .then(response => {
                if (response.status === 200) {
                    console.log('✅ Arquivo verificado:', file);
                }
            })
            .catch(error => {
                console.log('⚠️ Erro ao verificar arquivo:', file, error);
            });
    });
}

// Função principal de inicialização
function initAntiCache() {
    console.log('🚀 Inicializando sistema anti-cache...');
    
    // Limpar cache existente
    clearBrowserCache();
    
    // Adicionar cache buster aos recursos
    addCacheBusterToResources();
    
    // Detectar mudanças em arquivos
    detectFileChanges();
    
    // Adicionar listener para teclas de atalho
    document.addEventListener('keydown', function(e) {
        // Ctrl + Shift + R = Limpar cache e recarregar
        if (e.ctrlKey && e.shiftKey && e.key === 'R') {
            e.preventDefault();
            console.log('🔥 Atalho detectado: Limpando cache e recarregando...');
            clearBrowserCache();
            forceReload();
        }
        
        // Ctrl + Shift + C = Apenas limpar cache
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            console.log('🧹 Atalho detectado: Limpando cache...');
            clearBrowserCache();
        }
    });
    
    console.log('✅ Sistema anti-cache inicializado');
    console.log('💡 Atalhos disponíveis:');
    console.log('   Ctrl + Shift + R = Limpar cache e recarregar');
    console.log('   Ctrl + Shift + C = Apenas limpar cache');
}

// Executar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAntiCache);
} else {
    initAntiCache();
}

// Exportar funções para uso global
window.clearBrowserCache = clearBrowserCache;
window.forceReload = forceReload;
window.addCacheBusterToResources = addCacheBusterToResources;
