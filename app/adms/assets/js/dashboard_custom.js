// app/adms/assets/js/dashboard_custom.js

// Variáveis globais para armazenar as instâncias dos gráficos Chart.js
let visitsChartInstance = null;
let trafficChartInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // SIDEBAR TOGGLE (MENU HAMBÚRGUER)
    // =============================================
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('active');

            if (sidebar.classList.contains('active')) {
                addOverlay();
            } else {
                removeOverlay();
            }
        });
    }

    function addOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
        overlay.style.zIndex = '1019'; // Z-index para estar acima de outros elementos
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            removeOverlay();
        });
        document.body.appendChild(overlay);
    }

    function removeOverlay() {
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay) overlay.remove();
    }

    // =============================================
    // DROPDOWNS (USER AVATAR E NOTIFICAÇÕES) - JS Puro
    // (Ainda usando JS puro, mas Bootstrap 5 Dropdowns são recomendados)
    // =============================================
    // Este bloco é para dropdowns que não são do Bootstrap ou para complementar.
    // Se seus dropdowns são todos geridos pelo Bootstrap, pode simplificar.
    document.addEventListener('click', function(e) {
        // Fecha dropdowns de notificação se o clique for fora deles
        if (!e.target.closest('.notification-dropdown')) {
            document.querySelectorAll('.notification-dropdown-content').forEach(d => {
                d.style.display = 'none';
            });
        }
    });

    document.querySelectorAll('.notification-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Previne que o clique se propague para o document e feche o dropdown imediatamente
            const dropdown = this.nextElementSibling; // Assume que o conteúdo do dropdown é o próximo irmão

            // Fecha outros dropdowns de notificação abertos
            document.querySelectorAll('.notification-dropdown-content').forEach(d => {
                if (d !== dropdown) d.style.display = 'none';
            });

            // Alterna a visibilidade do dropdown clicado
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
    });

    // Para o dropdown do usuário (se for um dropdown do Bootstrap)
    const userDropdownElement = document.getElementById('userDropdown');
    if (userDropdownElement) {
        // Instancia o dropdown do Bootstrap
        const userDropdown = new bootstrap.Dropdown(userDropdownElement); 
        // Quando o dropdown do usuário é exibido, fecha os de notificação (se houver)
        userDropdownElement.addEventListener('show.bs.dropdown', function() {
            document.querySelectorAll('.notification-dropdown-content').forEach(d => {
                d.style.display = 'none';
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
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
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
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    }

    // =============================================
    // LÓGICA SPA (Single Page Application) com jQuery
    // =============================================
    $(document).ready(function() {
        // Inicializa gráficos na carga inicial da página (quando o DOM está pronto)
        initializeCharts();

        // CHAMA A FUNÇÃO DE INICIALIZAÇÃO DA PÁGINA DE PERFIL NA CARGA INICIAL
        // Isso é crucial para que, se a página de perfil for a primeira a ser carregada,
        // os scripts dela sejam ativados.
        // Verifica se estamos na URL /perfil
        if (window.location.pathname.includes('/perfil')) {
            // Verifica se a função initializePerfilPage existe globalmente (exposta por perfil.js)
            if (typeof window.initializePerfilPage === 'function') {
                window.initializePerfilPage();
                console.log("dashboard_custom.js: initializePerfilPage() chamado na carga inicial.");
            } else {
                console.warn("dashboard_custom.js: initializePerfilPage não é uma função (ainda não carregada?).");
            }
        }


        $(document).on('click', 'a[data-spa="true"]', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var ajaxUrl = url + (url.includes('?') ? '&' : '?') + 'ajax=true';

            // Adicione uma lógica para fechar a sidebar quando um link SPA é clicado
            if (sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                removeOverlay();
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

                    initializeCharts(); // Re-inicializa gráficos, se existirem na nova página

                    // =========================================================
                    // CHAMA A FUNÇÃO DE INICIALIZAÇÃO ESPECÍFICA PARA CADA PÁGINA
                    // =========================================================
                    // Lógica para chamar a função de inicialização correta com base na URL
                    if (url.includes('/perfil')) {
                        if (typeof window.initializePerfilPage === 'function') { // Verifica se a função está disponível globalmente
                            window.initializePerfilPage();
                            console.log("dashboard_custom.js: initializePerfilPage() chamado após carga SPA.");
                        } else {
                            console.warn("dashboard_custom.js: initializePerfilPage não é uma função após carga SPA. Verifique se perfil.js está sendo incluído na view carregada via AJAX.");
                        }
                    }
                    // Adicione mais blocos 'else if' aqui para outras páginas SPA
                    // Exemplo:
                    // else if (url.includes('/usuarios')) {
                    //    if (typeof window.initializeUsersPage === 'function') {
                    //        window.initializeUsersPage();
                    //    }
                    // }
                    // =========================================================
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erro ao carregar o conteúdo via AJAX:', textStatus, errorThrown, jqXHR.responseText);
                    alert('Erro ao carregar o conteúdo. Por favor, tente novamente.');
                }
            });
        });

        // =============================================
        // TRATAMENTO DO BOTÃO VOLTAR/AVANÇAR DO NAVEGADOR
        // =============================================
        window.onpopstate = function() {
            var url = window.location.href;
            var ajaxUrl = url + (url.includes('?') ? '&' : '?') + 'ajax=true';

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                beforeSend: function() {
                    $('#dynamic-content').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3">Carregando...</p></div>');
                },
                success: function(response) {
                    $('#dynamic-content').html(response);
                    initializeCharts(); // Re-inicializa gráficos, se existirem na nova página

                    // =========================================================
                    // CHAMA A FUNÇÃO DE INICIALIZAÇÃO ESPECÍFICA PARA CADA PÁGINA
                    // ao usar os botões de navegação
                    // =========================================================
                    if (url.includes('/perfil')) {
                        if (typeof window.initializePerfilPage === 'function') { // Verifica se a função está disponível globalmente
                            window.initializePerfilPage();
                            console.log("dashboard_custom.js: initializePerfilPage() chamado após popstate.");
                        } else {
                            console.warn("dashboard_custom.js: initializePerfilPage não é uma função após popstate. Verifique se perfil.js está sendo incluído na view carregada via AJAX.");
                        }
                    }
                    // Adicione mais blocos 'else if' aqui para outras páginas
                    // Exemplo:
                    // else if (url.includes('/produtos')) {
                    //    if (typeof window.initializeProductsPage === 'function') {
                    //        window.initializeProductsPage();
                    //    }
                    // }
                    // =========================================================
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erro ao navegar com popstate:', textStatus, errorThrown, jqXHR.responseText);
                    alert('Erro ao retornar ao conteúdo anterior. Recarregando a página completa.');
                    location.reload();
                }
            });
        };
    });
});