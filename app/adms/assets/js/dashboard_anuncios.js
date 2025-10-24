// Versão 15 - Ajustado: botão "Excluir Conta" removido da tabela, ação implementada apenas na página editar anúncio.

console.info('INFO JS: dashboard_anuncios.js (Versão 15) carregado.');

// Variável global para o filtro selecionado
let selectedFilter = 'all';

let searchForm;
let searchInput;
// statusFilter removido - agora usamos modal
let anunciosTableBody;
let paginationContainer;
let noResultsMessage;
let loadingSpinner;
let totalAnunciosCount;
let activeAnunciosCount;
let pendingAnunciosCount;
let approvalRate;

window.initializeAnunciosListPage = async function (fullUrl, initialData = null) {
    console.info('INFO JS: initializeAnunciosListPage chamado. Inicializando funcionalidades da tabela de anúncios.');

    const userRoleRaw = document.body.dataset.userRole || '';
    const userRole = (userRoleRaw || '').toLowerCase();
    if (userRole !== 'admin' && userRole !== 'administrador') {
        console.info('INFO JS: Usuário não é administrador. Não carregando a tabela de anúncios. Role detectada:', userRoleRaw);
        return;
    }

    searchForm = document.getElementById('searchAnunciosForm');
    searchInput = document.getElementById('searchInput');
    // statusFilter removido - agora usamos modal
    anunciosTableBody = document.getElementById('anunciosTableBody');
    paginationContainer = document.getElementById('paginationContainer');
    noResultsMessage = document.getElementById('noResultsMessage');
    loadingSpinner = document.getElementById('loadingSpinner');
    totalAnunciosCount = document.getElementById('totalAnunciosCount');
    activeAnunciosCount = document.getElementById('activeAnunciosCount');
    pendingAnunciosCount = document.getElementById('pendingAnunciosCount');
    approvalRate = document.getElementById('approvalRate');

    if (!searchForm || !searchInput || !anunciosTableBody) {
        console.error('ERRO JS: Elementos do DOM necessários para a página de anúncios não foram encontrados.');
        console.error('ERRO JS: Elementos encontrados - searchForm:', !!searchForm, 'searchInput:', !!searchInput, 'anunciosTableBody:', !!anunciosTableBody);
        return;
    }

    function getStatusBadgeHtml(status) {
        const normalizedStatus = (status || '').toLowerCase();
        let status_class = '';
        let status_text = '';

        console.log('🔍 DASHBOARD ANUNCIOS: Processando status:', status, 'normalizado:', normalizedStatus);

        switch (normalizedStatus) {
            case 'active':
                status_class = 'text-bg-success';
                status_text = 'Ativo';
                break;
            case 'pausado':
                status_class = 'text-bg-info';
                status_text = 'Pausado';
                break;
            case 'pending':
                status_class = 'text-bg-warning';
                status_text = 'Pendente';
                break;
            case 'rejected':
                status_class = 'text-bg-danger';
                status_text = 'Rejeitado';
                break;
            case 'pausado':
                status_class = 'text-bg-info';
                status_text = 'Pausado';
                break;
            case 'deleted':
            case 'excluido':
            case '':
                status_class = 'text-bg-secondary';
                status_text = 'Excluído';
                break;
            default:
                console.error(`ERRO JS: Status desconhecido recebido para badge: "${status}"`);
                status_class = 'text-bg-dark';
                status_text = 'Desconhecido';
                break;
        }

        return `<span class="badge ${status_class}">${status_text}</span>`;
    }

    function getActionButtonsHtml(anuncio) {
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

    function highlightText(text, searchTerm) {
        if (!searchTerm || !text) return text;
        const escapedSearchTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escapedSearchTerm})`, 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }

    function getCurrentFilters() {
        const searchTerm = searchInput?.value || '';
        // Como não temos mais statusFilter, sempre retorna 'all' por padrão
        // O filtro será aplicado via modal
        const filterStatus = 'all';
        return { searchTerm, filterStatus };
    }

    async function loadAnuncios(page = 1, searchTerm = '', filterStatus = 'all', dataFromLoadContent = null) {
        console.debug(`DEBUG JS: loadAnuncios - Carregando anúncios. Página: ${page}, Termo de busca: "${searchTerm}", Status: "${filterStatus}"`);

        if (anunciosTableBody) anunciosTableBody.innerHTML = '';
        if (paginationContainer) paginationContainer.innerHTML = '';
        if (noResultsMessage) noResultsMessage.classList.add('d-none');
        if (loadingSpinner) loadingSpinner.classList.remove('d-none');

        let data;

        if (dataFromLoadContent) {
            console.log('INFO JS: loadAnuncios - Usando dados passados diretamente de loadContent.');
            data = dataFromLoadContent;
        } else {
            console.log('INFO JS: loadAnuncios - Fazendo requisição AJAX para carregar anúncios.');
            const url = `${URLADM}dashboard/getAnunciosData?page=${page}&search=${encodeURIComponent(searchTerm)}&status=${encodeURIComponent(filterStatus)}`;
            console.info('INFO JS: loadAnuncios - Fazendo requisição AJAX. URL da requisição:', url);

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
                throw error;
            }
        }

        if (loadingSpinner) loadingSpinner.classList.add('d-none');

        if (data.success && data.anuncios && data.anuncios.length > 0) {
            updateTable(data.anuncios, searchTerm);
            updatePagination(data.pagination);
            updateDashboardStats(data.dashboard_stats);
        } else {
            anunciosTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum anúncio encontrado.</td></tr>';
            if (paginationContainer) paginationContainer.innerHTML = '';
            updateDashboardStats(data.dashboard_stats);
            console.info('INFO JS: Nenhum anúncio encontrado ou falha na requisição:', data.message);
        }
    }

    // Expor função loadAnuncios globalmente para o modal
    window.loadAnuncios = loadAnuncios;

    function updateTable(anuncios, searchTerm = '') {
        let tableHtml = '';
        console.log('🔍 DASHBOARD ANUNCIOS: Processando anúncios para tabela:', anuncios);
        anuncios.forEach(anuncio => {
            console.log('🔍 DASHBOARD ANUNCIOS: Processando anúncio ID:', anuncio.id, 'Status:', anuncio.status);
            const statusBadge = getStatusBadgeHtml(anuncio.status);
            const actionButtons = getActionButtonsHtml(anuncio);
            const highlightedUserName = highlightText(anuncio.user_name || 'N/A', searchTerm);
            const highlightedServiceName = highlightText(anuncio.service_name || 'N/A', searchTerm);
            const highlightedStateUf = highlightText(anuncio.state_id || 'N/A', searchTerm);

            tableHtml += `
                <tr id="anuncio-row-${anuncio.id}">
                    <td class="d-none d-md-table-cell custom-table-col">${anuncio.id}</td>
                    <td>${highlightedUserName}</td>
                    <td class="d-none d-md-table-cell custom-table-col">${highlightedServiceName}</td>
                    <td>${anuncio.plan_badge || '<span class="badge bg-secondary">Gratuito</span>'}</td>
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

        if (!anuncios.length) {
            tableHtml = `<tr><td colspan="7" class="text-center">Nenhum anúncio encontrado.</td></tr>`;
        }

        anunciosTableBody.innerHTML = tableHtml;
        // Nenhum botão de excluir conta aqui!
    }

    function updatePagination(pagination) {
        let paginationHtml = '';
        if (pagination?.total_pages > 1) {
            if (pagination.current_page > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link page-link-ajax" href="#" data-page="${pagination.current_page - 1}"
                           data-search="${encodeURIComponent(pagination.search_term)}"
                           data-status="${encodeURIComponent(pagination.filter_status)}" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>`;
            }

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
                        <a class="page-link page-link-ajax" href="#" data-page="${i}"
                           data-search="${encodeURIComponent(pagination.search_term)}"
                           data-status="${encodeURIComponent(pagination.filter_status)}">
                            ${i}
                        </a>
                    </li>`;
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
                        <a class="page-link page-link-ajax" href="#" data-page="${pagination.current_page + 1}"
                           data-search="${encodeURIComponent(pagination.search_term)}"
                           data-status="${encodeURIComponent(pagination.filter_status)}" aria-label="Próximo">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>`;
            }
        }

        if (paginationContainer) {
            paginationContainer.innerHTML = `<ul class="pagination justify-content-center">${paginationHtml}</ul>`;
        }
    }

    function updateDashboardStats(stats) {
        if (totalAnunciosCount) totalAnunciosCount.textContent = stats?.total_anuncios ?? '0';
        if (activeAnunciosCount) activeAnunciosCount.textContent = stats?.active_anuncios ?? '0';
        if (pendingAnunciosCount) pendingAnunciosCount.textContent = stats?.pending_anuncios ?? '0';
        if (approvalRate) approvalRate.textContent = stats?.approval_rate ?? '0%';
    }

    function setupEventListeners() {
        if (paginationContainer) {
            paginationContainer.removeEventListener('click', handlePaginationClick);
            paginationContainer.addEventListener('click', handlePaginationClick);
        }

        if (searchForm) {
            searchForm.removeEventListener('submit', handleSearchSubmit);
            searchForm.addEventListener('submit', handleSearchSubmit);
        }

        // statusFilter removido - filtros agora via modal
    }

    function handlePaginationClick(event) {
        const target = event.target.closest('.page-link-ajax');
        if (target) {
            event.preventDefault();
            const page = parseInt(target.dataset.page);
            const { searchTerm, filterStatus } = getCurrentFilters();
            loadAnuncios(page, searchTerm, filterStatus);
        }
    }

    function handleSearchSubmit(event) {
        event.preventDefault();
        const { searchTerm, filterStatus } = getCurrentFilters();
        loadAnuncios(1, searchTerm, filterStatus);
    }

    // handleFilterClick removido - filtros agora via modal

    setupEventListeners();
    
    // Configurar filtros do modal
    setupModalFilters();
    
    // Reconfigurar quando o modal for aberto
    const filtersModal = document.getElementById('filtersModal');
    if (filtersModal) {
        filtersModal.addEventListener('shown.bs.modal', function() {
            console.log('DEBUG JS: Modal aberto - Reconfigurando filtros');
            setupModalFilters();
        });
    }

    try {
        const { searchTerm, filterStatus } = getCurrentFilters();
        console.log('DEBUG JS: Inicializando carregamento de anúncios. SearchTerm:', searchTerm, 'FilterStatus:', filterStatus, 'InitialData:', initialData);
        await loadAnuncios(1, searchTerm, filterStatus, initialData);
    } catch (error) {
        console.error('ERRO JS: Falha na carga inicial da tabela de anúncios (propagado para dashboard_custom.js):', error);
        // Tentar carregar sem initialData se houver erro
        try {
            console.log('DEBUG JS: Tentando carregar anúncios sem initialData...');
            await loadAnuncios(1, '', 'all');
        } catch (retryError) {
            console.error('ERRO JS: Falha também no retry:', retryError);
            throw error;
        }
    }
};

// Expor função globalmente para recarregar dados
window.loadAnunciosData = function() {
    if (typeof loadAnuncios === 'function') {
        const { searchTerm, filterStatus } = getCurrentFilters();
        loadAnuncios(1, searchTerm, filterStatus);
    }
};

// Exposição da função será feita após sua definição

// =============================================
// FUNCIONALIDADE DO MODAL DE FILTROS
// =============================================

// Variável global já declarada no topo do arquivo

// Função para configurar os event listeners do modal
function setupModalFilters() {
    const filterOptions = document.querySelectorAll('.filter-option');
    console.log('DEBUG JS: setupModalFilters - Encontrados:', filterOptions.length, 'opções de filtro');
    
    filterOptions.forEach(option => {
        // Remove event listener anterior se existir
        option.removeEventListener('click', handleFilterOptionClick);
        // Adiciona novo event listener
        option.addEventListener('click', handleFilterOptionClick);
        console.log('DEBUG JS: Event listener adicionado para:', option.getAttribute('data-filter-status'));
    });
}

// Função para lidar com clique nas opções de filtro
function handleFilterOptionClick(event) {
    event.preventDefault();
    console.log('DEBUG JS: handleFilterOptionClick - Clique detectado!');
    
    // Armazena o filtro selecionado
    selectedFilter = this.getAttribute('data-filter-status');
    console.log('DEBUG JS: Filtro selecionado e aplicando automaticamente:', selectedFilter);
    
    // Aplicar filtro imediatamente
    if (typeof window.loadAnuncios === 'function') {
        console.log('DEBUG JS: loadAnuncios está disponível, aplicando filtro...');
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput ? searchInput.value : '';
        window.loadAnuncios(1, searchTerm, selectedFilter);
    } else {
        console.error('ERRO JS: Função loadAnuncios não está disponível no escopo do modal');
    }
    
    // Fechar o modal automaticamente
    const modal = bootstrap.Modal.getInstance(document.getElementById('filtersModal'));
    if (modal) {
        console.log('DEBUG JS: Fechando modal...');
        modal.hide();
    } else {
        console.error('ERRO JS: Modal não encontrado');
    }
}