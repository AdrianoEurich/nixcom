// app/adms/assets/js/dashboard_custom.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: dashboard_custom.js carregado.');

    const dynamicContentArea = document.getElementById('dynamic-content');
    const sidebarLinks = document.querySelectorAll('.sidebar-link[data-spa="true"]');

    // Mapeamento de rotas para as funções de inicialização e scripts necessários
    // Adicione aqui cada rota que precisa de um script JS específico
    const pageInitializers = {
        'anuncio': {
            script: `${window.URLADM}assets/js/anuncio.js`,
            initializer: 'initializeAnuncioFormPage'
        },
        'anuncio/editarAnuncio': { // Rota específica para edição, se diferente da criação
            script: `${window.URLADM}assets/js/anuncio.js`,
            initializer: 'initializeAnuncioFormPage'
        },
        'anuncio/visualizarAnuncio': {
            script: `${window.URLADM}assets/js/anuncio.js`,
            initializer: 'initializeVisualizarAnuncioPage'
        },
        'perfil': {
            script: `${window.URLADM}assets/js/perfil.js`,
            initializer: 'initializePerfilPage'
        },
        'dashboard': { // Adicione o dashboard aqui para que ele possa carregar dashboard_anuncios.js
            script: `${window.URLADM}assets/js/dashboard_anuncios.js`,
            initializer: null // Não tem uma função de inicialização específica no dashboard_anuncios.js, mas ele se auto-executa
        }
        // Adicione outras rotas conforme necessário
    };

    // Objeto para controlar quais scripts já foram carregados
    const loadedScripts = {};

    /**
     * Carrega um script JavaScript dinamicamente.
     * @param {string} url O URL do script.
     * @returns {Promise<void>} Uma promessa que resolve quando o script é carregado.
     */
    async function loadScript(url) {
        if (loadedScripts[url]) {
            console.log(`INFO JS: Script já carregado: ${url}`);
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.onload = () => {
                loadedScripts[url] = true;
                console.log(`INFO JS: Script carregado com sucesso: ${url}`);
                resolve();
            };
            script.onerror = () => {
                console.error(`ERRO JS: Falha ao carregar script: ${url}`);
                reject(new Error(`Falha ao carregar script: ${url}`));
            };
            document.head.appendChild(script);
        });
    }

    /**
     * Carrega o conteúdo de uma página via AJAX e o injeta na área de conteúdo dinâmico.
     * Em seguida, tenta carregar e chamar a função de inicialização específica da página.
     * @param {string} url O URL da página a ser carregada.
     * @param {string} currentPath O caminho da URL para identificar a página (ex: "anuncio/index").
     */
    async function loadContent(url, currentPath) {
        window.showLoadingModal('Carregando...'); // Mostra modal de carregamento

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('ERRO AJAX: Resposta de rede não OK:', response.status, response.statusText, errorText);
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }

            const htmlContent = await response.text();
            console.log('DEBUG JS: dashboard_custom.js - Conteúdo HTML recebido via AJAX:', htmlContent);

            dynamicContentArea.innerHTML = htmlContent;

            // Chamar a função de inicialização da página após o carregamento do conteúdo
            await callPageInitializer(currentPath, url);

            // Atualiza o estado da sidebar após carregar o conteúdo
            // Isso é crucial para que os links reflitam o estado mais recente do anúncio
            if (typeof window.updateAnuncioSidebarLinks === 'function') {
                await window.updateAnuncioSidebarLinks();
            } else {
                console.warn('AVISO JS: window.updateAnuncioSidebarLinks não está definida. Verifique se anuncio.js foi carregado.');
            }

            // Atualiza a URL no histórico do navegador
            history.pushState({
                path: url
            }, '', url);

        } catch (error) {
            console.error('ERRO JS: Falha ao carregar conteúdo via AJAX:', error);
            window.showFeedbackModal('error', `Falha ao carregar a página: ${error.message}.`, 'Erro de Carregamento');
            dynamicContentArea.innerHTML = `<div class="alert alert-danger" role="alert">
                Erro ao carregar o conteúdo. Por favor, tente novamente.
            </div>`;
        } finally {
            window.hideLoadingModal(); // Esconde modal de carregamento
        }
    }

    /**
     * Tenta carregar o script e chamar a função de inicialização para a página atual.
     * @param {string} currentPath O caminho da URL para identificar a página (ex: "anuncio/index").
     * @param {string} fullUrl A URL completa da página.
     */
    async function callPageInitializer(currentPath, fullUrl) {
        console.log('DEBUG JS: callPageInitializer - currentPath:', currentPath, 'fullUrl:', fullUrl);

        let routeKey = currentPath;
        // Ajuste para rotas como 'anuncio/index' ou 'anuncio/editarAnuncio'
        if (routeKey.startsWith('anuncio/')) {
            // Se for 'anuncio/editarAnuncio' ou 'anuncio/visualizarAnuncio', use a chave específica
            if (routeKey === 'anuncio/editarAnuncio' || routeKey === 'anuncio/visualizarAnuncio') {
                 // Mantém a chave específica
            } else {
                 routeKey = 'anuncio'; // Para 'anuncio/index' ou 'anuncio'
            }
        }
        // Se for 'perfil/index', use 'perfil'
        if (routeKey.startsWith('perfil/')) {
            routeKey = 'perfil';
        }
        // Se for 'dashboard/index', use 'dashboard'
        if (routeKey.startsWith('dashboard/')) {
            routeKey = 'dashboard';
        }


        const pageConfig = pageInitializers[routeKey];

        if (pageConfig) {
            console.log(`DEBUG JS: callPageInitializer - Tentando chamar: ${pageConfig.initializer || 'script auto-executável'} para o caminho: ${routeKey}`);
            if (pageConfig.script) {
                try {
                    await loadScript(pageConfig.script);
                    // Após o script ser carregado, a função deve estar disponível globalmente
                    if (pageConfig.initializer && typeof window[pageConfig.initializer] === 'function') {
                        // Passa o ID do anúncio se for a página de visualização
                        if (pageConfig.initializer === 'initializeVisualizarAnuncioPage') {
                            // Usando o construtor nativo URL para parsear a URL
                            const urlObj = new URL(fullUrl); 
                            const urlParams = new URLSearchParams(urlObj.search);
                            const anuncioId = urlParams.get('id');
                            window[pageConfig.initializer](anuncioId);
                        } else {
                            window[pageConfig.initializer]();
                        }
                        console.log(`INFO JS: Função ${pageConfig.initializer} chamada com sucesso.`);
                    } else if (!pageConfig.initializer) {
                        console.log(`INFO JS: Script ${pageConfig.script} carregado. Nenhuma função de inicialização explícita necessária.`);
                    } else {
                        console.warn(`AVISO JS: callPageInitializer - A função ${pageConfig.initializer} não é uma função (ainda não carregada ou não definida globalmente). Verifique se o script correspondente está sendo incluído na view carregada via AJAX.`);
                    }
                } catch (error) {
                    console.error(`ERRO JS: Falha ao carregar script para ${routeKey}:`, error);
                }
            } else if (pageConfig.initializer && typeof window[pageConfig.initializer] === 'function') {
                // Se não há script específico, mas a função já existe globalmente
                window[pageConfig.initializer]();
                console.log(`INFO JS: Função ${pageConfig.initializer} chamada com sucesso (sem script dinâmico).`);
            } else {
                console.info('INFO JS: callPageInitializer - Nenhuma função de inicialização específica encontrada para a página atual.');
            }
        } else {
            console.info('INFO JS: callPageInitializer - Nenhuma função de inicialização específica encontrada para a página atual.');
        }
    }


    // Lógica para interceptar cliques nos links da sidebar (SPA)
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.classList.contains('disabled')) {
                e.preventDefault(); // Impede a navegação se o link estiver desabilitado
                console.log('DEBUG JS: Clique em link desabilitado:', this.href);
                return;
            }

            e.preventDefault(); // Previne a navegação padrão
            const url = this.href;
            // Usando window.URLADM aqui
            const pathSegments = url.split(window.URLADM)[1]; // Pega o caminho relativo
            const currentPath = pathSegments ? pathSegments.split('?')[0] : ''; // Remove query params

            console.log('DEBUG JS: Clique SPA interceptado. URL:', url, 'Caminho:', currentPath);
            loadContent(url, currentPath);

            // Adiciona/remove a classe 'active' para o link clicado
            sidebarLinks.forEach(otherLink => {
                otherLink.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Lógica para lidar com o botão de voltar/avançar do navegador
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.path) {
            const url = event.state.path;
            // Usando window.URLADM aqui
            const pathSegments = url.split(window.URLADM)[1];
            const currentPath = pathSegments ? pathSegments.split('?')[0] : '';
            console.log('DEBUG JS: popstate event. Carregando conteúdo para:', url);
            loadContent(url, currentPath);
        }
    });

    // Chama a função de atualização da sidebar na carga inicial da página
    // É importante que anuncio.js seja carregado antes para updateAnuncioSidebarLinks estar disponível
    // No entanto, como dashboard_custom.js carrega dinamicamente, podemos ter um pequeno delay.
    // A melhor abordagem é chamar fetchAndApplyAnuncioStatus e updateAnuncioSidebarLinks
    // no final do DOMContentLoaded do dashboard_custom.js.
    // A função fetchAndApplyAnuncioStatus foi globalizada em anuncio.js para isso.
    async function initialSidebarUpdate() {
        // Verifica se as funções estão disponíveis antes de chamar
        // Tenta carregar anuncio.js primeiro para garantir que as funções de sidebar estejam disponíveis
        try {
            await loadScript(`${window.URLADM}assets/js/anuncio.js`);
            if (typeof window.fetchAndApplyAnuncioStatus === 'function') {
                await window.fetchAndApplyAnuncioStatus();
                if (typeof window.updateAnuncioSidebarLinks === 'function') {
                    window.updateAnuncioSidebarLinks();
                } else {
                    console.warn('AVISO JS: updateAnuncioSidebarLinks não está definida após carregar anuncio.js.');
                }
            } else {
                console.warn('AVISO JS: fetchAndApplyAnuncioStatus não está disponível após carregar anuncio.js.');
            }
        } catch (error) {
            console.error('ERRO JS: Falha ao carregar anuncio.js para atualização inicial da sidebar:', error);
        }
    }
    initialSidebarUpdate();


    // Chama a função de inicialização da página atual na carga inicial
    // Isso é para o caso de a página ser carregada diretamente (não via SPA)
    const initialPathSegments = window.location.href.split(window.URLADM)[1];
    const initialPath = initialPathSegments ? initialPathSegments.split('?')[0] : '';
    callPageInitializer(initialPath, window.location.href);

});
