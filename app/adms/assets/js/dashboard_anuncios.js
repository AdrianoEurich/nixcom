// app/adms/assets/js/dashboard_anuncios.js
// Versão 6 - Pesquisa por Múltiplos Campos e Destaque de Resultados
// Este script lida com a lógica específica da página de listagem de anúncios no dashboard para administradores.

console.info('INFO JS: dashboard_anuncios.js (Versão 6) carregado.');

// Variáveis globais para os elementos do DOM que serão usados na inicialização
let searchForm;
let searchInput;
let statusFilter;
let anunciosTableBody;
let paginationContainer;
let noResultsMessage;
let loadingSpinner; // Spinner específico da tabela
let totalAnunciosCount;
let activeAnunciosCount;
let pendingAnunciosCount;
let approvalRate;

/**
 * Função principal de inicialização para a página de listagem de anúncios (admin dashboard).
 * Esta função é chamada pelo dashboard_custom.js quando o conteúdo do dashboard é carregado.
 * @param {string} fullUrl - A URL completa da página.
 * @param {object|null} [initialData=null] - Dados JSON iniciais para a página (se for uma resposta JSON).
 */
window.initializeAnunciosListPage = async function(fullUrl, initialData = null) { // Tornar async para await loadAnuncios
    console.info('INFO JS: initializeAnunciosListPage chamado. Inicializando funcionalidades da tabela de anúncios.');

    const userRole = document.body.dataset.userRole;
    console.log('DEBUG JS: initializeAnunciosListPage - User Role:', userRole);

    if (userRole === 'admin') {
        // Obter referências aos elementos do DOM
        searchForm = document.getElementById('searchAnunciosForm');
        searchInput = document.getElementById('searchInput');
        statusFilter = document.getElementById('statusFilter');
        anunciosTableBody = document.getElementById('anunciosTableBody');
        paginationContainer = document.getElementById('paginationContainer');
        noResultsMessage = document.getElementById('noResultsMessage');
        loadingSpinner = document.getElementById('loadingSpinner'); // Spinner da tabela
        totalAnunciosCount = document.getElementById('totalAnunciosCount');
        activeAnunciosCount = document.getElementById('activeAnunciosCount');
        pendingAnunciosCount = document.getElementById('pendingAnunciosCount');
        approvalRate = document.getElementById('approvalRate');

        /**
         * Retorna o HTML do badge de status para um anúncio.
         * @param {string} status O status do anúncio (ex: 'active', 'pending').
         * @returns {string} HTML do badge.
         */
        function getStatusBadgeHtml(status) {
            let status_class = '';
            let status_text = '';
            switch (status) {
                case 'active':
                    status_class = 'text-bg-success';
                    status_text = 'Ativo';
                    break;
                case 'pending':
                    status_class = 'text-bg-warning';
                    status_text = 'Pendente';
                    break;
                case 'rejected':
                    status_class = 'text-bg-danger';
                    status_text = 'Rejeitado';
                    break;
                case 'inactive':
                    status_class = 'text-bg-info';
                    status_text = 'Pausado';
                    break;
                case 'deleted': // Adicionado para lidar com status 'deleted'
                    status_class = 'text-bg-dark';
                    status_text = 'Excluído';
                    break;
                default:
                    status_class = 'text-bg-secondary';
                    status_text = 'Desconhecido';
                    break;
            }
            return `<span class="badge ${status_class}">${status_text}</span>`;
        }

        /**
         * Função para obter o HTML do botão de ação simplificado.
         * @param {object} anuncio Objeto de anúncio contendo o ID.
         * @returns {string} HTML do botão "Abrir".
         */
        function getActionButtonsHtml(anuncio) {
            // Apenas um botão "Abrir" que direciona para a página de edição
            return `
                <a href="${URLADM}anuncio/editarAnuncio?id=${anuncio.id}" 
                    class="btn btn-sm btn-primary" 
                    data-id="${anuncio.id}" 
                    data-spa="true"
                    title="Abrir/Editar Anúncio">
                    <i class="fas fa-external-link-alt me-1"></i> Abrir
                </a>
            `;
        }

        /**
         * Função para destacar o termo de busca no texto.
         * @param {string} text O texto original.
         * @param {string} searchTerm O termo a ser destacado.
         * @returns {string} O texto com o termo destacado em HTML.
         */
        function highlightText(text, searchTerm) {
            if (!searchTerm || !text) {
                return text;
            }
            // Escapa caracteres especiais para usar no RegExp
            const escapedSearchTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${escapedSearchTerm})`, 'gi');
            // Adiciona a classe 'highlight' ao termo encontrado
            return text.replace(regex, '<span class="highlight">$1</span>');
        }

        /**
         * Função para carregar anúncios via AJAX ou usar dados iniciais.
         * Esta função NÃO controla o modal de carregamento global.
         * @param {number} [page=1] - A página a ser carregada.
         * @param {string} [searchTerm=''] - Termo de busca.
         * @param {string} [filterStatus='all'] - Status de filtro.
         * @param {object|null} [dataFromLoadContent=null] - Dados JSON passados diretamente de loadContent.
         */
        async function loadAnuncios(page = 1, searchTerm = '', filterStatus = 'all', dataFromLoadContent = null) {
            console.debug(`DEBUG JS: loadAnuncios - Carregando anúncios. Página: ${page}, Termo de busca: "${searchTerm}", Status: "${filterStatus}"`);
            
            // Gerencia apenas o spinner da tabela
            if (anunciosTableBody) anunciosTableBody.innerHTML = ''; 
            if (paginationContainer) paginationContainer.innerHTML = ''; 
            if (noResultsMessage) noResultsMessage.classList.add('d-none'); 
            if (loadingSpinner) loadingSpinner.classList.remove('d-none'); 

            let data;

            if (dataFromLoadContent) {
                console.log('INFO JS: loadAnuncios - Usando dados passados diretamente de loadContent.');
                data = dataFromLoadContent;
            } else {
                console.log('INFO JS: loadAnuncios - Fazendo requisição AJAX para obter dados.');
                const url = `${URLADM}dashboard/getAnunciosData?page=${page}&search=${encodeURIComponent(searchTerm)}&status=${encodeURIComponent(filterStatus)}`;
                
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

                    data = await response.json();
                } catch (error) {
                    if (loadingSpinner) loadingSpinner.classList.add('d-none'); 
                    console.error('ERRO JS: Erro ao carregar anúncios:', error);
                    if (typeof window.showFeedbackModal === 'function') {
                        window.showFeedbackModal('error', 'Falha ao carregar anúncios. Por favor, tente novamente.', 'Erro de Carregamento');
                    } else {
                        alert('Erro ao carregar anúncios: ' + error.message);
                    }
                    throw error; // Propaga o erro para que o dashboard_custom.js possa lidar com o modal global
                }
            }

            if (loadingSpinner) loadingSpinner.classList.add('d-none'); 

            if (data.success && data.anuncios && data.anuncios.length > 0) {
                // Passa o termo de busca para updateTable
                updateTable(data.anuncios, searchTerm); 
                updatePagination(data.pagination);
                updateDashboardStats(data.dashboard_stats);
            } else {
                if (noResultsMessage) {
                    noResultsMessage.classList.remove('d-none');
                    noResultsMessage.textContent = data.message || 'Nenhum anúncio encontrado com os critérios de busca.';
                }
                updateDashboardStats(data.dashboard_stats);
                console.info('INFO JS: Nenhum anúncio encontrado ou falha na requisição:', data.message);
            }
        }

        /**
         * Atualiza a tabela de anúncios com os dados fornecidos.
         * @param {Array<Object>} anuncios Array de objetos de anúncio.
         * @param {string} searchTerm O termo de busca atual para destaque.
         */
        function updateTable(anuncios, searchTerm = '') {
            let tableHtml = '';
            if (anuncios.length > 0) {
                anuncios.forEach(anuncio => {
                    const statusBadge = getStatusBadgeHtml(anuncio.status);
                    const actionButtons = getActionButtonsHtml(anuncio);
                    
                    // Aplica destaque aos campos relevantes
                    const highlightedUserName = highlightText(anuncio.user_name || 'N/A', searchTerm);
                    const highlightedServiceName = highlightText(anuncio.service_name || 'N/A', searchTerm);
                    const highlightedStateUf = highlightText(anuncio.state_uf || 'N/A', searchTerm);

                    tableHtml += `
                        <tr id="anuncio-row-${anuncio.id}">
                            <td class="d-none d-md-table-cell custom-table-col">${anuncio.id}</td>
                            <td>${highlightedUserName}</td>
                            <td class="d-none d-md-table-cell custom-table-col">${highlightedServiceName}</td>
                            <td class="d-none d-md-table-cell custom-table-col">${highlightedStateUf}</td>
                            <td>${statusBadge}</td>
                            <td>
                                <div class="btn-group" role="group" aria-label="Ações do Anúncio">
                                    ${actionButtons}
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                // Colspan ajustado para 6 colunas (ID, Anunciante, Nome de trabalho, Estado, Status, Ações)
                tableHtml = `<tr><td colspan="6" class="text-center">Nenhum anúncio encontrado.</td></tr>`;
            }
            anunciosTableBody.innerHTML = tableHtml;
            // Não há mais botões de ação na tabela para anexar listeners aqui,
            // apenas o link "Abrir" que usa data-spa.
        }

        /**
         * Atualiza os controles de paginação com os dados fornecidos.
         * @param {object} pagination Objeto de paginação contendo current_page, total_pages, search_term, filter_status.
         */
        function updatePagination(pagination) {
            let paginationHtml = '';
            if (pagination.total_pages > 1) {
                if (pagination.current_page > 1) {
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link page-link-ajax" href="#" 
                                data-page="${pagination.current_page - 1}" 
                                data-search="${encodeURIComponent(pagination.search_term)}" 
                                data-status="${encodeURIComponent(pagination.filter_status)}" 
                                aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    `;
                }

                // Lógica para exibir um número limitado de páginas (ex: 5 páginas centradas na atual)
                let startPage = Math.max(1, pagination.current_page - 2);
                let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

                if (startPage > 1) {
                    paginationHtml += `<li class="page-item"><a class="page-link page-link-ajax" href="#" data-page="1" data-search="${encodeURIComponent(pagination.search_term)}" data-status="${encodeURIComponent(pagination.filter_status)}">1</a></li>`;
                    if (startPage > 2) {
                        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = (i === pagination.current_page) ? 'active' : '';
                    paginationHtml += `
                        <li class="page-item ${activeClass}">
                            <a class="page-link page-link-ajax" href="#" 
                                data-page="${i}" 
                                data-search="${encodeURIComponent(pagination.search_term)}" 
                                data-status="${encodeURIComponent(pagination.filter_status)}">
                                ${i}
                            </a>
                        </li>
                    `;
                }

                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) {
                        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHtml += `<li class="page-item"><a class="page-link page-link-ajax" href="#" data-page="${pagination.total_pages}" data-search="${encodeURIComponent(pagination.search_term)}" data-status="${encodeURIComponent(pagination.filter_status)}">${pagination.total_pages}</a></li>`;
                }

                if (pagination.current_page < pagination.total_pages) {
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link page-link-ajax" href="#" 
                                data-page="${pagination.current_page + 1}" 
                                data-search="${encodeURIComponent(pagination.search_term)}" 
                                data-status="${encodeURIComponent(pagination.filter_status)}" 
                                aria-label="Próximo">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    `;
                }
            }
            if (paginationContainer) {
                paginationContainer.innerHTML = `<ul class="pagination justify-content-center">${paginationHtml}</ul>`;
            }
        }

        /**
         * Atualiza os cartões de estatísticas do dashboard.
         * @param {object} stats Objeto contendo as estatísticas do dashboard.
         */
        function updateDashboardStats(stats) {
            if (totalAnunciosCount) totalAnunciosCount.textContent = stats.total_anuncios ?? '0';
            if (activeAnunciosCount) activeAnunciosCount.textContent = stats.active_anuncios ?? '0';
            if (pendingAnunciosCount) pendingAnunciosCount.textContent = stats.pending_anuncios ?? '0';
            if (approvalRate) approvalRate.textContent = stats.approval_rate ?? '0%';
        }

        // Event Listeners para Paginação (delegação)
        if (paginationContainer) {
            paginationContainer.removeEventListener('click', handlePaginationClick); // Remove listener antigo para evitar duplicação
            paginationContainer.addEventListener('click', handlePaginationClick);
        }

        /**
         * Lida com cliques nos links de paginação.
         * @param {Event} event O evento de clique.
         */
        function handlePaginationClick(event) {
            const target = event.target.closest('.page-link-ajax');
            if (target) {
                event.preventDefault();
                const page = parseInt(target.dataset.page);
                const searchTerm = searchInput.value;
                const filterStatusElement = statusFilter.querySelector('.filter-item.active');
                const filterStatus = filterStatusElement ? filterStatusElement.dataset.filterStatus : 'all';
                loadAnuncios(page, searchTerm, filterStatus);
            }
        }

        // Event Listener para o formulário de busca
        if (searchForm) {
            searchForm.removeEventListener('submit', handleSearchSubmit); // Remove listener antigo para evitar duplicação
            searchForm.addEventListener('submit', handleSearchSubmit);
        }

        /**
         * Lida com o envio do formulário de busca.
         * @param {Event} event O evento de envio.
         */
        function handleSearchSubmit(event) {
            event.preventDefault();
            const searchTerm = searchInput.value;
            const filterStatusElement = statusFilter.querySelector('.filter-item.active');
            const filterStatus = filterStatusElement ? filterStatusElement.dataset.filterStatus : 'all';
            loadAnuncios(1, searchTerm, filterStatus);
        }

        // Event Listener para os filtros de status
        if (statusFilter) {
            statusFilter.removeEventListener('click', handleFilterClick); // Remove listener antigo para evitar duplicação
            statusFilter.addEventListener('click', handleFilterClick);
        }

        /**
         * Lida com cliques nos itens de filtro de status.
         * @param {Event} event O evento de clique.
         */
        function handleFilterClick(event) {
            const target = event.target.closest('.filter-item');
            if (target) {
                event.preventDefault();
                statusFilter.querySelectorAll('.filter-item').forEach(item => item.classList.remove('active'));
                target.classList.add('active');
                const searchTerm = searchInput.value;
                const filterStatus = target.dataset.filterStatus;
                loadAnuncios(1, searchTerm, filterStatus);
            }
        }

        // Carrega os anúncios. O modal de carregamento global será ocultado pelo dashboard_custom.js
        try {
            await loadAnuncios(1, searchInput?.value || '', statusFilter?.querySelector('.filter-item.active')?.dataset.filterStatus || 'all', initialData);
        } catch (error) {
            console.error('ERRO JS: Falha na carga inicial da tabela de anúncios (propagado para dashboard_custom.js):', error);
            // Não ocultar o modal aqui, o dashboard_custom.js fará isso no seu catch.
            throw error; // Re-lança o erro para o chamador (dashboard_custom.js)
        }

    } else {
        console.info('INFO JS: Usuário não é administrador. Não carregando a tabela de anúncios.');
        // Não ocultar o modal aqui. O dashboard_custom.js é o responsável por isso.
    }
};
