// dashboard_custom.js
// Versão 31 - Ajuste definitivo para submissão de formulários SPA e scripts duplicados.
console.info("dashboard_custom.js (Versão 31) carregado. Configurando navegação SPA.");

// Objeto global para armazenar todas as funcionalidades SPA
window.SpaUtils = window.SpaUtils || {};

// Mapeamento de caminhos de página para scripts específicos a serem carregados
const pageScripts = {
    'dashboard/index': 'dashboard_anuncios.js',
    'perfil/index': 'perfil.js',
    'anuncio/index': 'anuncio.js',
    'anuncio/editarAnuncio': 'anuncio.js'
};

// Mapeamento de pagePath para funções de inicialização
const pageInitializers = {
    'dashboard/index': 'initializeAnunciosListPage',
    'perfil/index': 'initializePerfilPage',
    'anuncio/index': 'initializeAnuncioFormPage',
    'anuncio/editarAnuncio': 'initializeAnuncioFormPage',
    'anuncio/visualizarAnuncio': 'initializeVisualizarAnuncioPage',
};

// Cache para scripts que já foram carregados
const loadedScripts = new Set();

/**
 * Normaliza uma URL completa para um pagePath limpo e roteável.
 * Ex: 'http://localhost/adm/anuncio/editarAnuncio?id=123' -> 'anuncio/editarAnuncio'
 * @param {string} fullUrl O URL completo da página.
 * @returns {string} O caminho da página limpo para roteamento.
 */
function getPagePathFromUrl(fullUrl) {
    const baseUrl = window.URLADM;
    let pagePath = fullUrl.replace(baseUrl, '');

    const queryParamIndex = pagePath.indexOf('?');
    if (queryParamIndex !== -1) {
        pagePath = pagePath.substring(0, queryParamIndex);
    }

    if (pagePath.endsWith('/')) {
        pagePath = pagePath.slice(0, -1);
    }

    if (pagePath === 'dashboard' || pagePath === '') {
        pagePath = 'dashboard/index';
    }

    return pagePath;
}

/**
 * Carrega um script JavaScript dinamicamente, evitando carregamento duplicado.
 * @param {string} scriptUrl O URL completo do script.
 * @returns {Promise<void>} Uma promessa que resolve quando o script é carregado ou rejeita em caso de erro.
 */
window.SpaUtils.loadScript = function(scriptUrl) {
    return new Promise((resolve, reject) => {
        // Verifica se o script já foi carregado
        if (loadedScripts.has(scriptUrl)) {
            console.info(`INFO JS: loadScript - Script já carregado: ${scriptUrl}.`);
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = scriptUrl;
        script.onload = () => {
            console.info('INFO JS: loadScript - Script carregado com sucesso:', scriptUrl);
            loadedScripts.add(scriptUrl); // Adiciona o script ao cache
            resolve();
        };
        script.onerror = () => {
            console.error('ERRO JS: loadScript - Falha ao carregar script:', scriptUrl);
            reject(new Error(`Falha ao carregar script: ${scriptUrl}`));
        };
        document.body.appendChild(script);
        console.log('DEBUG JS: loadScript - Adicionando script tag ao body:', scriptUrl);
    });
};

/**
 * Chama uma função de inicialização de página se ela existir no escopo global.
 * @param {string} initializerFunctionName O nome da função de inicialização.
 * @param {string} fullUrlOrPagePath O URL completo da página (com query params) OU o pagePath limpo.
 * @param {object|null} [initialData=null] Dados JSON iniciais para a página.
 */
window.SpaUtils.callPageInitializer = function(initializerFunctionName, fullUrlOrPagePath, initialData = null) {
    if (typeof window[initializerFunctionName] === 'function') {
        console.info('INFO JS: callPageInitializer - Função', initializerFunctionName, 'chamada com sucesso.');
        window[initializerFunctionName](fullUrlOrPagePath, initialData);
    } else {
        console.warn('AVISO JS: callPageInitializer - Função de inicialização', initializerFunctionName, 'não encontrada para o caminho', fullUrlOrPagePath);
    }
};

/**
 * Anexa listeners de submit a todos os formulários com data-spa="true".
 * @returns {void}
 */
window.SpaUtils.setupSpaForms = function() {
    const spaForms = document.querySelectorAll('form[data-spa="true"]');
    console.log(`DEBUG JS: setupSpaForms - Encontrados ${spaForms.length} formulários SPA.`);
    spaForms.forEach(form => {
        form.removeEventListener('submit', handleSpaFormSubmit); // Evita duplicidade de listeners
        form.addEventListener('submit', handleSpaFormSubmit);
    });
};

/**
 * Handler para a submissão de formulários SPA.
 * @param {Event} event O evento de submissão.
 * @returns {Promise<void>}
 */
async function handleSpaFormSubmit(event) {
    const form = event.target;
    event.preventDefault();

    console.info('INFO JS: handleSpaFormSubmit - Submissão de formulário SPA detectada.');

    const fullUrl = form.action;
    const cleanPagePath = getPagePathFromUrl(fullUrl);

    try {
        const formData = new FormData(form);
        const response = await fetch(fullUrl, {
            method: form.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('ERRO JS: Form submit - Resposta de rede não OK:', response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            console.log('INFO JS: Form submit - Resposta JSON recebida:', data);

            if (data.html) {
                await window.SpaUtils.loadContent(fullUrl, cleanPagePath, data);
            } else {
                const initializerFunction = pageInitializers[cleanPagePath];
                if (initializerFunction) {
                    window.SpaUtils.callPageInitializer(initializerFunction, fullUrl, data);
                } else {
                    console.warn('AVISO JS: Form submit - Nenhuma função de inicialização definida para o caminho:', cleanPagePath);
                    if (data.status === 'success') {
                        window.showFeedbackModal('success', data.msg || 'Ação realizada com sucesso!', 'Sucesso');
                    }
                }
            }
        } else {
            await window.SpaUtils.loadContent(fullUrl, cleanPagePath, null);
        }
    } catch (error) {
        console.error('ERRO JS: Form submit - Erro ao processar submissão:', error);
        window.showFeedbackModal('error', `Não foi possível processar a requisição. Detalhes: ${error.message}`, 'Erro na Requisição');
    }
}


/**
 * Carrega o conteúdo de uma URL via AJAX e o injeta na área de conteúdo principal.
 * Também gerencia o carregamento de scripts específicos da página e a atualização da sidebar.
 * @param {string} url O URL completo do conteúdo a ser carregado (inclui query params).
 * @param {string} pagePath O caminho da página (ex: 'dashboard/index', 'anuncio/editarAnuncio').
 * @param {object|null} [initialData=null] Dados JSON iniciais para a página (se for uma resposta JSON).
 */
window.SpaUtils.loadContent = async function(url, pagePath, initialData = null) {
    console.log('INFO JS: loadContent - Iniciando carregamento de conteúdo para:', url);

    const contentArea = document.getElementById('dynamic-content');
    if (!contentArea) {
        console.error('ERRO JS: loadContent - Elemento #dynamic-content não encontrado.');
        window.showFeedbackModal('error', 'Erro interno: Área de conteúdo não encontrada.', 'Erro de Layout');
        return;
    }

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('ERRO JS: loadContent - Resposta de rede não OK:', response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            console.log('INFO JS: loadContent - Resposta é JSON. Processando dados.');
            if (data.html) {
                contentArea.innerHTML = data.html;
                console.log('INFO JS: loadContent - Conteúdo HTML do JSON injetado com sucesso.');
            } else {
                contentArea.innerHTML = '<div class="alert alert-info">Nenhum conteúdo HTML para exibir.</div>';
                console.warn('AVISO JS: loadContent - Resposta JSON não contém HTML para injetar.');
            }
            initialData = data;
        } else {
            const html = await response.text();
            contentArea.innerHTML = html;
            console.log('INFO JS: loadContent - Resposta é HTML. Injetando conteúdo.');
        }

        console.log('INFO JS: Conteúdo dinâmico injetado com sucesso.');

        if (typeof window.clearPageEvents === 'function') {
            window.clearPageEvents();
        }

        if (typeof window.setupInputMasks === 'function') {
            window.setupInputMasks();
        }
        if (typeof window.setupAutoDismissAlerts === 'function') {
            window.setupAutoDismissAlerts();
        }
        window.SpaUtils.setupSpaForms();

        const scriptToLoad = pageScripts[pagePath];
        if (scriptToLoad) {
            const scriptUrl = `${window.URLADM}assets/js/${scriptToLoad}`;
            console.log('DEBUG JS: loadContent - Chamando loadScript para:', scriptUrl);
            await window.SpaUtils.loadScript(scriptUrl);
        } else {
            console.log('INFO JS: loadContent - Nenhum script específico para carregar para o caminho:', pagePath);
        }

        const initializerFunction = pageInitializers[pagePath];
        if (initializerFunction) {
            console.log('DEBUG JS: loadContent - Chamando callPageInitializer para:', pagePath);
            window.SpaUtils.callPageInitializer(initializerFunction, url, initialData);
        } else {
            console.warn('AVISO JS: loadContent - Nenhuma função de inicialização definida para o caminho:', pagePath);
        }

        history.pushState({ pagePath: pagePath, url: url }, '', url);

        if (typeof window.updateAnuncioSidebarLinks === 'function') {
            window.updateAnuncioSidebarLinks();
            console.log('INFO JS: loadContent - Sidebar atualizada após loadContent.');
        }

    } catch (error) {
        console.error('ERRO JS: loadContent - Erro ao carregar conteúdo:', error);
        contentArea.innerHTML = `<div class="alert alert-danger">Erro ao carregar a página: ${error.message}</div>`;
        window.showFeedbackModal('error', `Não foi possível carregar a página. Detalhes: ${error.message}`, 'Erro de Carregamento');
    }
};

// =================================================================================================
// LÓGICA DE NAVEGAÇÃO SPA (SINGLE PAGE APPLICATION)
// =================================================================================================

document.addEventListener('DOMContentLoaded', async () => {
    console.info("DOMContentLoaded disparado em dashboard_custom.js. Configurando navegação SPA.");

    // Lógica para links SPA
    document.body.addEventListener('click', async (event) => {
        const link = event.target.closest('a[data-spa="true"]');
        if (link) {
            event.preventDefault();
            const fullUrl = link.href;
            const cleanPagePath = getPagePathFromUrl(fullUrl);

            console.info('INFO JS: DOMContentLoaded - Clique em link SPA detectado. Carregando conteúdo para:', fullUrl, '(pagePath limpo para roteamento:', cleanPagePath + ')');
            await window.SpaUtils.loadContent(fullUrl, cleanPagePath);

            // CORREÇÃO: Fecha a sidebar e o overlay após o clique em um link SPA
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (mainContent) {
                    mainContent.classList.remove('sidebar-hidden');
                }
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            }
        }
    });

    // === NOVO: Anexa listeners de formulário após o carregamento inicial do DOM ===
    window.SpaUtils.setupSpaForms();

    // Lógica para o botão de toggle da sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-hidden');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
        });
    }

    // Lógica para fechar sidebar em mobile ao clicar no overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            mainContent.classList.remove('sidebar-hidden');
            sidebarOverlay.classList.remove('active');
        });
    }

    // Lógica para o botão de "Voltar" do navegador
    window.addEventListener('popstate', async (event) => {
        if (event.state && event.state.url && event.state.pagePath) {
            console.log('INFO JS: popstate - Navegando para:', event.state.url);
            await window.SpaUtils.loadContent(event.state.url, event.state.pagePath);
        } else {
            console.warn('AVISO JS: popstate - Estado não encontrado, recarregando dashboard.');
            await window.SpaUtils.loadContent(`${window.URLADM}dashboard`, 'dashboard/index');
        }
    });

    // Carga inicial da página
    const initialUrl = window.location.href;
    const cleanInitialPagePath = getPagePathFromUrl(initialUrl);

    const scriptToLoadForInitial = pageScripts[cleanInitialPagePath];
    if (scriptToLoadForInitial) {
        try {
            await window.SpaUtils.loadScript(`${window.URLADM}assets/js/${scriptToLoadForInitial}`);
            console.log('DEBUG JS: Carga inicial - Script extra carregado para', cleanInitialPagePath);
        } catch (error) {
            console.error('ERRO JS: Carga inicial - Falha ao carregar script inicial:', error);
        }
    }

    console.log('DEBUG JS: Carga inicial - Chamando callPageInitializer para:', cleanInitialPagePath);
    window.SpaUtils.callPageInitializer(pageInitializers[cleanInitialPagePath], initialUrl);
    window.SpaUtils.setupSpaForms();

    if (typeof window.updateAnuncioSidebarLinks === 'function') {
        await window.updateAnuncioSidebarLinks();
        console.log('INFO JS: Carga inicial - Sidebar atualizada.');
    }
});