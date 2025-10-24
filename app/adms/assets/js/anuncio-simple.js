/**
 * ANÚNCIO SIMPLE - Sistema modular simplificado
 * Versão: 3.0 (Carregamento sequencial)
 */

console.log("🚀 ANÚNCIO SIMPLE v3.0 - Carregando sistema modular simplificado");

// Lista de módulos para carregar
    const modules = [
        'anuncio-core.js',
        'anuncio-masks.js', 
        'anuncio-locations.js',
        'anuncio-forms.js',
        'anuncio-validation.js',
        'anuncio-admin.js',
        'anuncio-uploads.js'
    ];

// Módulos específicos para páginas de formulário (não visualização)
    const formModules = [];

// Função para carregar módulos sequencialmente
async function loadModules() {
    console.log("📦 ANÚNCIO SIMPLE: Carregando módulos...");
    
    // Verificar se é página de visualização
    const isVisualizationPage = window.location.href.includes('visualizarAnuncio') || 
                               window.location.href.includes('visualizar_anuncio');
    
    console.log("🔍 ANÚNCIO SIMPLE: URL atual:", window.location.href);
    console.log("🔍 ANÚNCIO SIMPLE: É página de visualização?", isVisualizationPage);
    
    // Carregar módulos básicos
    for (const module of modules) {
        try {
            await loadScript(`${window.URLADM}assets/js/${module}`);
            console.log(`✅ ANÚNCIO SIMPLE: Módulo ${module} carregado`);
        } catch (error) {
            console.error(`❌ ANÚNCIO SIMPLE: Erro ao carregar ${module}:`, error);
        }
    }
    
    // Módulos de formulário agora estão na lista principal
    console.log("📦 ANÚNCIO SIMPLE: Todos os módulos carregados");
    
    console.log("✅ ANÚNCIO SIMPLE: Todos os módulos carregados");
}

// Função para carregar script
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

// Carrega módulos quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadModules);
} else {
    loadModules();
}

// Expor função temporária para evitar erro de "não encontrada"
window.initializeAnuncioFormPage = function(fullUrl, initialData = null) {
    console.log("⏳ ANÚNCIO SIMPLE: Função temporária chamada, aguardando módulos carregarem...");
    console.log("🔍 ANÚNCIO SIMPLE: Verificando módulos disponíveis...");
    console.log("  - AnuncioCore:", !!window.AnuncioCore);
    console.log("  - AnuncioUploads:", !!window.AnuncioUploads);
    console.log("  - AnuncioLocations:", !!window.AnuncioLocations);
    
    // Função para tentar inicializar
    const tryInitialize = () => {
        if (window.AnuncioCore && window.AnuncioCore.initializeAnuncioFormPage) {
            console.log("✅ ANÚNCIO SIMPLE: Módulos carregados, chamando função real...");
            window.AnuncioCore.initializeAnuncioFormPage(fullUrl, initialData);
            return true;
        }
        return false;
    };
    
    // Tentar imediatamente
    if (tryInitialize()) {
        return;
    }
    
    // Aguardar um pouco e tentar novamente
    setTimeout(() => {
        if (!tryInitialize()) {
            console.log("❌ ANÚNCIO SIMPLE: Módulos ainda não carregados, tentando novamente...");
            // Tentar novamente após mais tempo
            setTimeout(() => {
                if (!tryInitialize()) {
                    console.error("❌ ANÚNCIO SIMPLE: Não foi possível carregar os módulos");
                    // Forçar inicialização básica se os módulos não carregarem
                    console.log("🔧 ANÚNCIO SIMPLE: Tentando inicialização básica...");
                    initializeBasicAnuncioForm();
                }
            }, 1000);
        }
    }, 200);
};

// Função de inicialização básica como fallback
function initializeBasicAnuncioForm() {
    console.log("🔧 ANÚNCIO SIMPLE: Executando inicialização básica...");
    
    // Aplicar máscaras básicas se disponíveis
    if (typeof $ !== 'undefined' && $.fn.inputmask) {
        console.log("🔧 ANÚNCIO SIMPLE: Aplicando máscaras básicas...");
        $('input[name="phone_number"]').inputmask('(99) 99999-9999');
        $('input[name="age"]').inputmask('999');
    }
    
    // Carregar estados se disponível
    if (typeof loadStates === 'function') {
        console.log("🔧 ANÚNCIO SIMPLE: Carregando estados...");
        loadStates();
    } else if (window.AnuncioLocations && window.AnuncioLocations.loadStates) {
        console.log("🔧 ANÚNCIO SIMPLE: Carregando estados via AnuncioLocations...");
        window.AnuncioLocations.loadStates();
    } else {
        console.log("🔧 ANÚNCIO SIMPLE: Tentando carregar estados manualmente...");
        loadStatesManually();
    }
    
    console.log("✅ ANÚNCIO SIMPLE: Inicialização básica concluída");
}

// Função para carregar estados manualmente como fallback
function loadStatesManually() {
    console.log("🔧 ANÚNCIO SIMPLE: Carregando estados manualmente...");
    
    const stateSelect = document.getElementById('state_id');
    if (!stateSelect) {
        console.log("🔧 ANÚNCIO SIMPLE: Select de estado não encontrado");
        return;
    }
    
    // Carregar estados via fetch
    fetch(window.URLADM + 'assets/js/data/states.json')
        .then(response => response.json())
        .then(data => {
            if (data && data.data) {
                console.log("🔧 ANÚNCIO SIMPLE: Estados carregados:", data.data.length);
                
                // Limpar opções existentes
                stateSelect.innerHTML = '<option value="">Selecione o Estado</option>';
                
                // Adicionar estados
                data.data.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.Uf;
                    option.textContent = state.Nome;
                    stateSelect.appendChild(option);
                });
                
                console.log("✅ ANÚNCIO SIMPLE: Estados populados com sucesso");
            }
        })
        .catch(error => {
            console.error("❌ ANÚNCIO SIMPLE: Erro ao carregar estados:", error);
        });
}

console.log("✅ ANÚNCIO SIMPLE: Sistema carregado e pronto para uso");
