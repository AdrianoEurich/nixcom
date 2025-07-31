// dashboard_custom.js (Versão 26 - Passando URL Completa para Inicializadores)

console.info("dashboard_custom.js (Versão 26) carregado. Configurando navegação SPA.");

// Mapeamento de caminhos de página para scripts específicos a serem carregados
// A chave é o pagePath (ex: 'dashboard/index'), o valor é o nome do script (ex: 'dashboard_anuncios.js')
const pageScripts = {
    'dashboard/index': 'dashboard_anuncios.js',
    'perfil/index': 'perfil.js',
    // 'anuncio/index': 'anuncio.js' // anuncio.js é carregado globalmente, não precisa ser aqui
    // 'anuncio/editarAnuncio' e 'anuncio/visualizarAnuncio' são inicializados por anuncio.js
    // e não precisam de scripts adicionais específicos aqui.
};

// Mapeamento de pagePath para funções de inicialização (definidas globalmente por outros scripts)
// A chave é o pagePath (ex: 'dashboard/index'), o valor é o nome da função global (ex: 'initializeAnunciosListPage')
const pageInitializers = {
    'dashboard/index': 'initializeAnunciosListPage',
    'perfil/index': 'initializePerfilPage',
    'anuncio/index': 'initializeAnuncioFormPage', // Para a página de criação de anúncio
    'anuncio/editarAnuncio': 'initializeAnuncioFormPage', // Para a página de edição de anúncio
    'anuncio/visualizarAnuncio': 'initializeVisualizarAnuncioPage', // Para a página de visualização de anúncio
};

/**
 * Carrega um script JavaScript dinamicamente.
 * @param {string} scriptUrl O URL completo do script.
 * @returns {Promise<void>} Uma promessa que resolve quando o script é carregado ou rejeita em caso de erro.
 */
window.loadScript = function(scriptUrl) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = scriptUrl;
        script.onload = () => {
            console.info('INFO JS: loadScript - Script carregado com sucesso:', scriptUrl);
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
 * Depende de quem chama esta função.
 * @param {object|null} [initialData=null] Dados JSON iniciais para a página (se for uma resposta JSON).
 */
window.callPageInitializer = function(initializerFunctionName, fullUrlOrPagePath, initialData = null) {
    if (typeof window[initializerFunctionName] === 'function') {
        console.info('INFO JS: callPageInitializer - Função', initializerFunctionName, 'chamada com sucesso.');
        // Passa a URL completa (ou pagePath, dependendo do que foi recebido) e os dados iniciais
        window[initializerFunctionName](fullUrlOrPagePath, initialData);
    } else {
        console.warn('AVISO JS: callPageInitializer - Função de inicialização', initializerFunctionName, 'não encontrada para o caminho', fullUrlOrPagePath);
    }
};

/**
 * Carrega o conteúdo de uma URL via AJAX e o injeta na área de conteúdo principal.
 * Também gerencia o carregamento de scripts específicos da página e a atualização da sidebar.
 * @param {string} url O URL completo do conteúdo a ser carregado (inclui query params).
 * @param {string} pagePath O caminho da página (ex: 'dashboard/index', 'anuncio/editarAnuncio').
 * @param {object|null} [initialData=null] Dados JSON iniciais para a página (se for uma resposta JSON).
 */
window.loadContent = async function(url, pagePath, initialData = null) {
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
        let data;

        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
            console.log('INFO JS: loadContent - Resposta é JSON. Processando dados.');
            // Se a resposta for JSON, ela pode conter o HTML para injetar ou apenas dados
            if (data.html) {
                contentArea.innerHTML = data.html;
                console.log('INFO JS: loadContent - Conteúdo HTML do JSON injetado com sucesso.');
            } else {
                // Se não houver HTML no JSON, limpa a área de conteúdo ou exibe uma mensagem
                contentArea.innerHTML = '<div class="alert alert-info">Nenhum conteúdo HTML para exibir.</div>';
                console.warn('AVISO JS: loadContent - Resposta JSON não contém HTML para injetar.');
            }
            initialData = data; // Passa os dados JSON para o inicializador da página
        } else {
            const html = await response.text();
            contentArea.innerHTML = html;
            console.log('INFO JS: loadContent - Resposta é HTML. Injetando conteúdo.');
        }

        console.log('INFO JS: Conteúdo dinâmico injetado com sucesso.');

        // Re-aplicar máscaras de input se a função existir
        if (typeof window.setupInputMasks === 'function') {
            window.setupInputMasks();
            console.log('INFO JS: loadContent - Máscaras de input re-aplicadas ao novo conteúdo.');
        }

        // Re-configurar alertas de auto-dispensa
        if (typeof window.setupAutoDismissAlerts === 'function') {
            window.setupAutoDismissAlerts();
            console.log('INFO JS: loadContent - Alertas de auto-dispensa re-configurados.');
        }

        // Carregar script específico da página se houver
        const scriptToLoad = pageScripts[pagePath];
        if (scriptToLoad) {
            const scriptUrl = `${window.URLADM}assets/js/${scriptToLoad}`;
            console.log('DEBUG JS: loadContent - Chamando loadScript para:', scriptUrl);
            await window.loadScript(scriptUrl);
        } else {
            console.log('INFO JS: loadContent - Nenhum script específico (além de anuncio.js) para carregar para o caminho:', pagePath);
        }

        // Chamar função de inicialização da página
        const initializerFunction = pageInitializers[pagePath];
        if (initializerFunction) {
            console.log('DEBUG JS: loadContent - Chamando callPageInitializer para:', pagePath);
            // PASSA A URL COMPLETA (url) para o inicializador da página
            window.callPageInitializer(initializerFunction, url, initialData);
        } else {
            console.warn('AVISO JS: loadContent - Nenhuma função de inicialização definida para o caminho:', pagePath);
        }

        // Atualizar a URL no histórico do navegador
        history.pushState({ pagePath: pagePath, url: url }, '', url);

        // Atualizar links da sidebar (chamada global)
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

    // Carregar anuncio.js globalmente no início
    // Ele contém funções como updateAnuncioSidebarLinks, initializeAnuncioFormPage, etc.
    try {
        await window.loadScript(`${window.URLADM}assets/js/anuncio.js`);
        console.info("DOMContentLoaded - anuncio.js carregado globalmente.");
    } catch (error) {
        console.error("ERRO JS: DOMContentLoaded - Falha ao carregar anuncio.js globalmente:", error);
        // Pode ser necessário exibir um erro crítico aqui se anuncio.js for essencial
    }

    // Lógica para links SPA
    document.body.addEventListener('click', async (event) => {
        const link = event.target.closest('a[data-spa="true"]');
        if (link) {
            event.preventDefault(); // Impede o comportamento padrão do link
            console.log('DEBUG JS: DOMContentLoaded - event.preventDefault() chamado para link SPA.');

            const fullUrl = link.href; // Esta é a URL completa com o ID (ex: .../editarAnuncio?id=128)
            const baseUrl = window.URLADM;
            let pagePath = fullUrl.replace(baseUrl, ''); // Remove a base para obter o caminho da página (ex: anuncio/editarAnuncio?id=128)

            // Se o pagePath tiver query params, remove-os APENAS PARA O MAPEAMENTO DE SCRIPTS/INICIALIZADORES
            // A fullUrl (com ID) será passada para loadContent.
            const queryParamIndex = pagePath.indexOf('?');
            let cleanPagePath = pagePath;
            if (queryParamIndex !== -1) {
                cleanPagePath = pagePath.substring(0, queryParamIndex);
            }

            // Remove a barra final se houver
            if (cleanPagePath.endsWith('/')) {
                cleanPagePath = cleanPagePath.slice(0, -1);
            }

            // Trata o caso de 'dashboard' ou 'dashboard/index'
            if (cleanPagePath === 'dashboard' || cleanPagePath === '') {
                cleanPagePath = 'dashboard/index';
            }

            console.info('INFO JS: DOMContentLoaded - Clique em link SPA detectado. Carregando conteúdo para:', fullUrl, '(pagePath limpo para roteamento:', cleanPagePath + ')');
            // Passa a fullUrl (com ID) e o pagePath limpo para roteamento
            await window.loadContent(fullUrl, cleanPagePath);
        }
    });

    // Lógica para o botão de toggle da sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.getElementById('sidebarOverlay'); // Para mobile

    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-hidden'); // Nova classe para ajustar o main-content
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
            // Re-carrega o conteúdo da página sem adicionar ao histórico novamente
            await window.loadContent(event.state.url, event.state.pagePath);
        } else {
            // Se não houver estado, recarrega a página inicial ou um fallback
            console.warn('AVISO JS: popstate - Estado não encontrado, recarregando dashboard.');
            await window.loadContent(`${window.URLADM}dashboard`, 'dashboard/index');
        }
    });

    // Carga inicial da página (quando o usuário acessa diretamente uma URL ou dá refresh)
    const initialUrl = window.location.href; // Esta é a URL completa com o ID
    const baseUrl = window.URLADM;
    let initialPagePath = initialUrl.replace(baseUrl, '');

    const queryParamIndex = initialPagePath.indexOf('?');
    let cleanInitialPagePath = initialPagePath;
    if (queryParamIndex !== -1) {
        cleanInitialPagePath = initialPagePath.substring(0, queryParamIndex);
    }
    if (cleanInitialPagePath.endsWith('/')) {
        cleanInitialPagePath = cleanInitialPagePath.slice(0, -1);
    }
    if (cleanInitialPagePath === 'dashboard' || cleanInitialPagePath === '') {
        cleanInitialPagePath = 'dashboard/index';
    }

    // Define o script a ser carregado para a carga inicial
    let scriptToLoadForInitial = pageScripts[cleanInitialPagePath];
    if (scriptToLoadForInitial) {
        console.log('DEBUG JS: Carga inicial - Definido scriptToLoadForInitial para', scriptToLoadForInitial, 'para', cleanInitialPagePath);
        try {
            await window.loadScript(`${window.URLADM}assets/js/${scriptToLoadForInitial}`);
            console.log('DEBUG JS: Carga inicial - loadScript para', `${window.URLADM}assets/js/${scriptToLoadForInitial}`, 'concluído.');
        } catch (error) {
            console.error('ERRO JS: Carga inicial - Falha ao carregar script inicial:', error);
        }
    }

    console.log('DEBUG JS: Carga inicial - Chamando callPageInitializer para:', cleanInitialPagePath);
    // PASSA A URL COMPLETA (initialUrl) para o inicializador da página
    window.callPageInitializer(pageInitializers[cleanInitialPagePath], initialUrl);
    console.log('DEBUG JS: Carga inicial - callPageInitializer para', cleanInitialPagePath, 'concluído.');

    // Atualiza a sidebar após a carga inicial
    if (typeof window.updateAnuncioSidebarLinks === 'function') {
        await window.updateAnuncioSidebarLinks();
        console.log('INFO JS: Carga inicial - Sidebar atualizada.');
    }
});
