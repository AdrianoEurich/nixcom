// app/adms/assets/js/dashboard_custom.js - Versão 17
// Este script gerencia o carregamento dinâmico de conteúdo (SPA) e a inicialização de scripts específicos da página.

console.info('INFO JS: dashboard_custom.js (Versão 17) carregado.');

// Mapeamento de caminhos para funções de inicialização
const pageInitializers = {
    'dashboard/index': 'initializeAnunciosListPage',
    'anuncio/index': 'initializeAnuncioFormPage',
    'anuncio/editarAnuncio': 'initializeAnuncioFormPage',
    'anuncio/listarAnuncios': 'initializeAnunciosListPage', // Pode ser o mesmo que dashboard/index se for listagem geral
    'anuncio/visualizarAnuncio': 'initializeVisualizarAnuncioPage',
    'perfil/index': 'initializePerfilPage', // Adicionado para garantir que o perfil também seja inicializado
};

// Objeto para armazenar referências a scripts já carregados
const loadedScripts = {};

/**
 * Carrega um script JavaScript dinamicamente.
 * @param {string} src - O caminho do script a ser carregado.
 * @returns {Promise<void>} - Uma promessa que resolve quando o script é carregado.
 */
async function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (loadedScripts[src]) {
            console.info('INFO JS: Script já carregado: ' + src);
            return resolve();
        }

        const script = document.createElement('script');
        script.src = src;
        script.onload = () => {
            console.info('INFO JS: Script carregado com sucesso: ' + src);
            loadedScripts[src] = true;
            resolve();
        };
        script.onerror = (e) => {
            console.error('ERRO JS: Falha ao carregar script: ' + src, e);
            reject(new Error(`Falha ao carregar script: ${src}`));
        };
        document.body.appendChild(script);
    });
}

/**
 * Chama a função de inicialização da página com base no caminho.
 * @param {string} pagePath - O caminho lógico da página (ex: 'dashboard/index', 'anuncio/index').
 * @param {string} fullUrl - A URL completa que foi carregada.
 * @param {object|null} [initialData=null] - Dados JSON iniciais para a página (se for uma resposta JSON).
 * @returns {Promise<void>} Uma Promise que resolve quando a função de inicialização é concluída.
 */
async function callPageInitializer(pagePath, fullUrl, initialData = null) {
    console.debug(`DEBUG JS: callPageInitializer - currentPath: ${pagePath} fullUrl: ${fullUrl}`);
    const initializerFunctionName = pageInitializers[pagePath];

    if (initializerFunctionName) {
        if (typeof window[initializerFunctionName] === 'function') {
            console.debug(`DEBUG JS: callPageInitializer - Tentando chamar: ${initializerFunctionName} para o caminho: ${pagePath}`);
            try {
                // Await a função de inicialização, caso ela seja assíncrona
                await window[initializerFunctionName](fullUrl, initialData); 
                console.info(`INFO JS: Função ${initializerFunctionName} chamada com sucesso.`);
            } catch (e) {
                console.error(`ERRO JS: Erro ao executar a função de inicialização "${initializerFunctionName}" para o caminho: ${pagePath}.`, e);
                window.showFeedbackModal('error', `Erro ao inicializar a página: ${pagePath}.`, 'Erro de Inicialização');
            }
        } else {
            console.warn(`AVISO JS: Função de inicialização "${initializerFunctionName}" não encontrada para o caminho: ${pagePath}.`);
            // Não mostra modal aqui, pois o script pode ainda não ter sido carregado.
            // O erro será tratado se a função for chamada e não existir.
        }
    } else {
        console.info(`INFO JS: Nenhuma função de inicialização definida para o caminho: ${pagePath}.`);
    }
}

/**
 * Carrega conteúdo dinamicamente via AJAX e inicializa o script da página.
 * Esta função NÃO MAIS CONTROLA o modal de carregamento global.
 * @param {string} url - A URL do conteúdo a ser carregado.
 * @param {string} [pagePath=null] - O caminho lógico da página para inicialização de scripts.
 */
async function loadContent(url, pagePath = null) {
    console.log(`INFO JS: loadContent - Iniciando carregamento de conteúdo para: ${url}`);
    
    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('ERRO AJAX: Resposta de rede não OK:', response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('Content-Type');
        let initialData = null;

        if (contentType && contentType.includes('application/json')) {
            console.log('INFO JS: Resposta é JSON. Processando dados.');
            initialData = await response.json();
        } else {
            console.log('INFO JS: Resposta é HTML. Injetando conteúdo.');
            const html = await response.text();
            console.debug('DEBUG JS: dashboard_custom.js - Conteúdo HTML recebido via AJAX: ', html.substring(0, 200) + '...');

            const dynamicContentDiv = document.getElementById('dynamic-content');
            if (dynamicContentDiv) {
                dynamicContentDiv.innerHTML = html;
                console.log('INFO JS: Conteúdo dinâmico injetado com sucesso.');
            } else {
                console.error('ERRO JS: Elemento #dynamic-content não encontrado.');
                throw new Error('Elemento de conteúdo dinâmico não encontrado.');
            }

            // Re-aplicar máscaras e alertas após injetar o novo HTML
            if (typeof window.setupInputMasks === 'function') {
                window.setupInputMasks();
                console.info('INFO JS: Máscaras de input re-aplicadas ao novo conteúdo.');
            }
            
            if (typeof window.setupAutoDismissAlerts === 'function') {
                window.setupAutoDismissAlerts();
                console.info('INFO JS: Alertas de auto-dispensa re-configurados.');
            }
        }

        let actualPagePath = pagePath;
        if (contentType && !contentType.includes('application/json')) {
            // Se a resposta foi HTML, tentamos determinar o pagePath real pelo data-page-type
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = document.getElementById('dynamic-content').innerHTML;
            const pageTypeElement = tempDiv.querySelector('[data-page-type]');
            if (pageTypeElement && pageTypeElement.dataset.pageType) {
                const pageType = pageTypeElement.dataset.pageType;
                if (pageType === 'form' && (actualPagePath === 'anuncio/index' || actualPagePath === 'anuncio/editarAnuncio')) {
                    // Mantemos o pagePath original que veio da navegação.
                } else if (pageType === 'list') {
                    actualPagePath = 'anuncio/listarAnuncios'; // Ou 'dashboard/index' dependendo da sua estrutura
                } else if (pageType === 'view') {
                    actualPagePath = 'anuncio/visualizarAnuncio';
                } else if (pageType === 'perfil') { // Adicionado para o perfil
                    actualPagePath = 'perfil/index';
                }
            }
        }

        let scriptToLoad = null;
        // O anuncio.js já será carregado globalmente no DOMContentLoaded
        // Aqui, carregamos scripts específicos que não são o anuncio.js
        if (actualPagePath === 'anuncio/listarAnuncios' || actualPagePath === 'dashboard/index') {
            scriptToLoad = `${URLADM}assets/js/dashboard_anuncios.js`;
        } else if (actualPagePath === 'perfil/index') {
            scriptToLoad = `${URLADM}assets/js/perfil.js`; 
        }

        if (scriptToLoad) {
            await loadScript(scriptToLoad);
        } else {
            console.info(`INFO JS: Nenhum script específico (além de anuncio.js) para carregar para o caminho: ${actualPagePath}.`);
        }

        // Chama o inicializador da página e AGUARDA sua conclusão
        await callPageInitializer(actualPagePath, url, initialData); 
        
        // ATUALIZA A SIDEBAR APÓS CADA CARREGAMENTO DE CONTEÚDO
        if (typeof window.updateAnuncioSidebarLinks === 'function') {
            window.updateAnuncioSidebarLinks();
            console.log('INFO JS: Sidebar atualizada após loadContent.');
        } else {
            console.warn('AVISO JS: window.updateAnuncioSidebarLinks não está definida após loadContent.');
        }

    } catch (error) {
        console.error('ERRO JS: Falha no carregamento de conteúdo dinâmico:', error);
        window.showFeedbackModal('error', 'Não foi possível carregar o conteúdo. Por favor, tente novamente.', 'Erro de Carregamento');
    }
}


// =============================================
// INICIALIZAÇÃO SPA (DOMContentLoaded)
// =============================================
document.addEventListener('DOMContentLoaded', async function() {
    console.info('INFO JS: DOMContentLoaded disparado em dashboard_custom.js. Configurando navegação SPA.');

    // CARREGA anuncio.js GLOBALMENTE NO INÍCIO
    try {
        await loadScript(`${URLADM}assets/js/anuncio.js`);
        console.info('INFO JS: anuncio.js carregado globalmente no DOMContentLoaded.');
    } catch (error) {
        console.error('ERRO JS: Falha ao carregar anuncio.js no DOMContentLoaded:', error);
    }

    // Função para lidar com cliques em links SPA
    document.body.addEventListener('click', async function(event) {
        const target = event.target.closest('a[data-spa="true"]');
        if (target) {
            event.preventDefault();

            const url = target.href;
            const basePathname = new URL(URLADM).pathname;
            let pagePath = new URL(url).pathname.replace(basePathname, '').replace(/^\//, '');
            
            const projectFolderName = 'nixcom/';
            if (pagePath.startsWith(projectFolderName)) {
                pagePath = pagePath.substring(projectFolderName.length);
            }

            if (pagePath.endsWith('/')) {
                pagePath = pagePath.slice(0, -1);
            }

            if (pagePath === 'dashboard' || pagePath === '') {
                pagePath = 'dashboard/index';
            }

            console.log(`INFO JS: Clique em link SPA detectado. Carregando conteúdo para: ${url} (pagePath: ${pagePath})`);
            
            window.history.pushState({ path: url, pagePath: pagePath }, '', url);

            // loadContent agora NÃO chama showLoadingModal nem hideLoadingModal
            await loadContent(url, pagePath);
        }
    });

    // Lida com a navegação do histórico (botões Voltar/Avançar do navegador)
    window.addEventListener('popstate', async function(event) {
        console.log('INFO JS: Evento popstate disparado.');
        if (event.state && event.state.path) {
            console.log(`INFO JS: Navegando para o estado: ${event.state.path}`);
            // loadContent agora NÃO chama showLoadingModal nem hideLoadingModal
            await loadContent(event.state.path, event.state.pagePath);
        } else {
            console.log('INFO JS: Popstate sem estado. Recarregando a página.');
            // Para recarga completa, o modal de carregamento não é gerenciado por SPA
            window.location.reload(); 
        }
    });

    // Carrega o conteúdo inicial da página (se for uma URL SPA)
    const initialUrl = window.location.href;
    const basePathname = new URL(URLADM).pathname;
    let initialPagePath = window.location.pathname.replace(basePathname, '').replace(/^\//, '');

    const projectFolderName = 'nixcom/';
    if (initialPagePath.startsWith(projectFolderName)) {
        initialPagePath = initialPagePath.substring(projectFolderName.length);
    }

    if (initialPagePath.endsWith('/')) {
        initialPagePath = initialPagePath.slice(0, -1);
    }

    if (initialPagePath === 'dashboard' || initialPagePath === '') {
        initialPagePath = 'dashboard/index';
    }

    console.log(`INFO JS: Carregamento inicial da página. URL: ${initialUrl}, PagePath: ${initialPagePath}`);
    
    let scriptToLoadForInitial = null;
    // O anuncio.js já foi carregado globalmente.
    // Aqui, carregamos scripts específicos que não são o anuncio.js
    if (initialPagePath === 'anuncio/listarAnuncios' || initialPagePath === 'dashboard/index') {
        scriptToLoadForInitial = `${URLADM}assets/js/dashboard_anuncios.js`;
    } else if (initialPagePath === 'perfil/index') {
        scriptToLoadForInitial = `${URLADM}assets/js/perfil.js`; 
    }

    if (scriptToLoadForInitial) {
        await loadScript(scriptToLoadForInitial);
    } else {
        console.info(`INFO JS: Nenhum script específico (além de anuncio.js) para carregar na carga inicial para: ${initialPagePath}.`);
    }

    // Chama o inicializador da página e AGUARDA sua conclusão
    await callPageInitializer(initialPagePath, initialUrl);
    
    // ATUALIZA A SIDEBAR APÓS A CARGA INICIAL
    if (typeof window.updateAnuncioSidebarLinks === 'function') {
        window.updateAnuncioSidebarLinks();
        console.log('INFO JS: Sidebar atualizada após carga inicial.');
    } else {
        console.warn('AVISO JS: window.updateAnuncioSidebarLinks não está definida após carga inicial.');
    }
    
    window.loadContent = loadContent; // Torna loadContent globalmente acessível
});
