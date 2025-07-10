// app/adms/assets/js/dashboard_custom.js

// Variáveis globais para armazenar as instâncias dos gráficos Chart.js
let visitsChartInstance = null; // Armazena a instância do gráfico de visitas
let trafficChartInstance = null; // Armazena a instância do gráfico de tráfego

// Mapeamento de URLs para funções de inicialização específicas de cada página SPA
const spaPageInitializers = {
    'perfil': 'initializePerfilPage', // Sem barra inicial
    'anuncio': 'initializeAnuncioPage', // Sem barra inicial
    // Adicione mais mapeamentos aqui conforme necessário
    // 'usuarios': 'initializeUsersPage',
};

// Garante que o script só execute depois que todo o DOM (estrutura HTML) estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: dashboard_custom.js carregado.');

    // Chama a função global para atualizar o estado da sidebar de anúncios
    // Isso garante que os links da sidebar sejam atualizados imediatamente após o carregamento inicial da página (ex: após login).
    if (typeof window.updateAnuncioSidebarLinks === 'function') {
        window.updateAnuncioSidebarLinks();
        console.log('INFO JS: updateAnuncioSidebarLinks chamado no DOMContentLoaded do dashboard_custom.js.');
    } else {
        console.warn('AVISO JS: window.updateAnuncioSidebarLinks não encontrada. Verifique se anuncio.js está sendo carregado corretamente.');
    }

    // =============================================
    // SIDEBAR TOGGLE (MENU HAMBÚRGUER)
    // Lógica para abrir e fechar a barra lateral (sidebar)
    // =============================================
    const sidebarToggle = document.getElementById('sidebarToggle'); // Botão de alternância da sidebar
    const sidebar = document.getElementById('sidebar'); // A própria sidebar

    // Verifica se os elementos existem na página antes de adicionar os eventos
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault(); // Previne o comportamento padrão do link/botão
            sidebar.classList.toggle('active'); // Adiciona/remove a classe 'active' para mostrar/esconder a sidebar

            // Adiciona ou remove uma camada de sobreposição (overlay)
            if (sidebar.classList.contains('active')) {
                addOverlay(); // Adiciona o overlay quando a sidebar está ativa
            } else {
                removeOverlay(); // Remove o overlay quando a sidebar é desativada
            }
        });
    }

    // Função para adicionar a camada de sobreposição
    function addOverlay() {
        let overlay = document.getElementById('sidebarOverlay');
        if (!overlay) { // Cria o overlay apenas se ele não existir
            overlay = document.createElement('div'); // Cria um novo elemento <div>
            overlay.id = 'sidebarOverlay'; // Define o ID para o overlay
            overlay.style.position = 'fixed'; // Posição fixa na tela
            overlay.style.top = '0'; // Começa no topo
            overlay.style.left = '0'; // Começa na esquerda
            overlay.style.width = '100%'; // Ocupa toda a largura
            overlay.style.height = '100%'; // Ocupa toda a altura
            overlay.style.backgroundColor = 'rgba(0,0,0,0.5)'; // Cor preta semitransparente
            overlay.style.zIndex = '1019'; // Z-index para estar acima de outros elementos, mas abaixo da sidebar
            overlay.addEventListener('click', function() {
                // Ao clicar no overlay, fecha a sidebar e remove o overlay
                if (sidebar) sidebar.classList.remove('active');
                removeOverlay();
            });
            document.body.appendChild(overlay); // Adiciona o overlay ao corpo do documento
        }
    }

    // Função para remover a camada de sobreposição
    function removeOverlay() {
        const overlay = document.getElementById('sidebarOverlay'); // Pega o elemento overlay
        if (overlay) overlay.remove(); // Se existir, remove-o do DOM
    }

    // =============================================
    // DROPDOWNS (AVATAR DO USUÁRIO E NOTIFICAÇÕES) - JS Puro com suporte Bootstrap
    // =============================================
    // Listener geral para fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        // Fecha dropdowns de notificação se o clique for fora deles
        if (!e.target.closest('.notification-dropdown')) {
            document.querySelectorAll('.notification-dropdown-content').forEach(d => {
                d.style.display = 'none'; // Esconde todos os conteúdos de dropdown de notificação
            });
        }
    });

    // Listener para botões de notificação para alternar sua visibilidade
    document.querySelectorAll('.notification-btn').forEach(button => {
        // Remove listener prévio para evitar duplicação em caso de re-execução em SPA (embora menos comum para navbar)
        button.removeEventListener('click', handleNotificationToggle);
        button.addEventListener('click', handleNotificationToggle);
    });

    function handleNotificationToggle(e) {
        e.stopPropagation(); // Previne que o clique se propague e feche o dropdown imediatamente
        const dropdown = this.nextElementSibling; // Conteúdo do dropdown

        document.querySelectorAll('.notification-dropdown-content').forEach(d => {
            if (d !== dropdown) d.style.display = 'none'; // Esconde outros dropdowns de notificação
        });
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }


    // Para o dropdown do usuário (se for um dropdown do Bootstrap)
    const userDropdownElement = document.getElementById('userDropdown');
    if (userDropdownElement) {
        // Instancia o dropdown do Bootstrap. Certifique-se de que o Bootstrap JS está carregado.
        const userDropdown = new bootstrap.Dropdown(userDropdownElement);
        // Quando o dropdown do usuário é exibido, fecha os de notificação (se houver)
        userDropdownElement.addEventListener('show.bs.dropdown', function() {
            document.querySelectorAll('.notification-dropdown-content').forEach(d => {
                d.style.display = 'none'; // Esconde os dropdowns de notificação ao abrir o do usuário
            });
        });
    }

    // =============================================
    // FUNÇÃO PARA INICIALIZAR/RE-INICIALIZAR GRÁFICOS (CHART.JS)
    // =============================================
    function initializeCharts() {
        // Destrói instâncias existentes para evitar gráficos duplicados ou erros
        if (visitsChartInstance) {
            visitsChartInstance.destroy();
            visitsChartInstance = null;
        }
        if (trafficChartInstance) {
            trafficChartInstance.destroy();
            trafficChartInstance = null;
        }

        const visitsCtx = document.getElementById('visitsChart');
        if (visitsCtx) {
            visitsChartInstance = new Chart(visitsCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                    datasets: [{
                        label: 'Visitas',
                        data: [5000, 6200, 7500, 8200, 9500, 10500, 12000, 11000, 12345, 14000, 15000, 16000],
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        const trafficCtx = document.getElementById('trafficChart');
        if (trafficCtx) {
            trafficChartInstance = new Chart(trafficCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Direto', 'Social', 'Referência'],
                    datasets: [{
                        data: [55, 30, 15],
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.8)',
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(54, 185, 204, 0.8)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '70%'
                }
            });
        }
    }

    /**
     * Helper para obter o pathname de uma URL de forma segura.
     * Cria um elemento <a> temporário para que o navegador resolva a URL.
     * @param {string} urlString - A string da URL.
     * @returns {string} O pathname resolvido.
     */
    function getPathnameFromUrl(urlString) {
        const a = document.createElement('a');
        a.href = urlString;
        // Remove a barra inicial se existir, para corresponder aos caminhos em spaPageInitializers
        // E remove o prefixo URLADM para obter apenas a rota relativa
        return a.pathname.replace(URLADM.replace(window.location.origin, ''), '').replace(/^\//, '');
    }

    /**
     * Tenta chamar a função de inicialização específica para a página atual.
     * @param {string} currentPath - O caminho da URL atual (já normalizado, sem a base URLADM e sem barra inicial).
     */
    function callPageInitializer(currentPath) {
        let initialized = false;
        for (const path in spaPageInitializers) {
            // Usa startsWith para ser mais robusto com sub-rotas como anuncio/index ou anuncio/editarAnuncio
            if (currentPath.startsWith(path)) { 
                const initializerFunctionName = spaPageInitializers[path];
                if (typeof window[initializerFunctionName] === 'function') {
                    window[initializerFunctionName]();
                    console.log(`dashboard_custom.js: ${initializerFunctionName}() chamado para ${path}.`);
                    initialized = true;
                    break; // Sai do loop após encontrar e chamar o inicializador
                } else {
                    console.warn(`dashboard_custom.js: A função ${initializerFunctionName} não é uma função (ainda não carregada ou não definida globalmente). Verifique se o script correspondente está sendo incluído na view carregada via AJAX.`);
                }
            }
        }
        if (!initialized) {
            console.log("dashboard_custom.js: Nenhuma função de inicialização específica encontrada para a página atual.");
        }

        // Garante que alertas automáticos sejam configurados após qualquer carregamento de conteúdo
        // Esta chamada é importante aqui, mas a função em si deve estar em general-utils.js
        if (typeof window.setupAutoDismissAlerts === 'function') {
            window.setupAutoDismissAlerts();
        } else {
            console.warn("AVISO JS: window.setupAutoDismissAlerts não é uma função. general-utils.js pode não ter sido carregado corretamente.");
        }
    }


    // =============================================
    // LÓGICA SPA (Single Page Application) com jQuery
    // =============================================
    $(document).ready(function() {
        // Inicializa gráficos e chamadores de página na carga inicial da página
        initializeCharts();
        // Obtém o pathname da URL atual de forma segura e normaliza
        const currentPathname = getPathnameFromUrl(window.location.href);
        callPageInitializer(currentPathname); // Chama o inicializador para a URL atual

        // Event listener para links com o atributo data-spa="true"
        $(document).on('click', 'a[data-spa="true"]', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            // Adiciona 'ajax=true' apenas se a URL não for um logout
            const isLogout = url.includes('login/logout');
            const ajaxUrl = isLogout ? url : url + (url.includes('?') ? '&' : '?') + 'ajax=true';


            // Fecha a sidebar se estiver aberta
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                removeOverlay();
            }

            // Se for logout, apenas redireciona
            if (isLogout) {
                window.location.href = url;
                return;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                beforeSend: function() {
                    $('#dynamic-content').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3">Carregando...</p></div>');
                },
                success: function(response) {
                    $('#dynamic-content').html(response);
                    history.pushState(null, null, url);

                    // Re-inicializa os gráficos e chama o inicializador da página recém-carregada
                    initializeCharts();
                    // Passa a URL do link clicado, normalizando
                    const clickedPathname = getPathnameFromUrl(url);
                    callPageInitializer(clickedPathname); 

                    // Após o carregamento de conteúdo via SPA, re-chama a atualização da sidebar
                    // para garantir que qualquer mudança de estado (ex: após criar um anúncio) seja refletida.
                    if (typeof window.updateAnuncioSidebarLinks === 'function') {
                        window.updateAnuncioSidebarLinks();
                        console.log('INFO JS: updateAnuncioSidebarLinks chamado após carregamento SPA.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erro ao carregar o conteúdo via AJAX:', textStatus, errorThrown, jqXHR.responseText);
                    window.showFeedbackModal('error', 'Erro ao carregar o conteúdo. Por favor, tente novamente.');
                }
            });
        });

        // =============================================
        // TRATAMENTO DO BOTÃO VOLTAR/AVANÇAR DO NAVEGADOR
        // =============================================
        window.onpopstate = function() {
            const url = window.location.href;
            // Obtém o pathname da URL atual de forma segura e normaliza
            const pathName = getPathnameFromUrl(url); 
            const ajaxUrl = url + (url.includes('?') ? '&' : '?') + 'ajax=true';

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                beforeSend: function() {
                    $('#dynamic-content').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3">Carregando...</p></div>');
                },
                success: function(response) {
                    $('#dynamic-content').html(response);
                    initializeCharts(); // Re-inicializa gráficos
                    callPageInitializer(pathName); // Chama o inicializador da página com base no pathname
                    
                    // Após o popstate, re-chama a atualização da sidebar
                    if (typeof window.updateAnuncioSidebarLinks === 'function') {
                        window.updateAnuncioSidebarLinks();
                        console.log('INFO JS: updateAnuncioSidebarLinks chamado após popstate.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erro ao navegar com popstate:', textStatus, errorThrown, jqXHR.responseText);
                    window.showFeedbackModal('error', 'Erro ao retornar ao conteúdo anterior. Recarregando a página completa.');
                    location.reload(); // Recarrega a página inteira como fallback
                }
            });
        };
    });
});
