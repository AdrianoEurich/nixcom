// app/adms/assets/js/dashboard_anuncios.js
// Versão 4 - Controle Otimizado do Modal de Carregamento
// Este script lida com a lógica específica da página de listagem de anúncios no dashboard para administradores.

console.info('INFO JS: dashboard_anuncios.js (Versão 4) carregado.');

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

        // Funções auxiliares para status e badges
        function getStatusBadgeHtml(status, anuncioId) {
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
                default:
                    status_class = 'text-bg-secondary';
                    status_text = 'Desconhecido';
                    break;
            }
            return `<span class="badge ${status_class}" id="status-badge-${anuncioId}">${status_text}</span>`;
        }

        // Função para obter o HTML dos botões de ação
        function getActionButtonsHtml(anuncio) {
            let buttonsHtml = '';
            buttonsHtml += `
                <a href="${URLADM}anuncio/visualizarAnuncio?id=${anuncio.id}" 
                    class="btn btn-sm btn-primary view-anuncio-btn" 
                    data-id="${anuncio.id}" 
                    data-spa="true"
                    title="Visualizar Anúncio">
                    <i class="fas fa-eye"></i>
                </a>
            `;

            if (anuncio.status === 'pending') {
                buttonsHtml += `
                    <button type="button" 
                            class="btn btn-sm btn-success approve-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Aprovar Anúncio">
                        <i class="fas fa-check"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-danger reject-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Rejeitar Anúncio">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (anuncio.status === 'active') {
                buttonsHtml += `
                    <button type="button" 
                            class="btn btn-sm btn-warning deactivate-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Pausar Anúncio">
                        <i class="fas fa-pause"></i>
                    </button>
                `;
            } else if (anuncio.status === 'inactive') {
                buttonsHtml += `
                    <button type="button" 
                            class="btn btn-sm btn-info activate-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Ativar Anúncio">
                        <i class="fas fa-play"></i>
                    </button>
                `;
            }

            if (anuncio.status !== 'rejected') { 
                buttonsHtml += `
                    <a href="${URLADM}anuncio/editarAnuncio?id=${anuncio.id}" 
                        class="btn btn-sm btn-secondary edit-anuncio-btn" 
                        data-id="${anuncio.id}" 
                        data-spa="true"
                        title="Editar Anúncio">
                        <i class="fas fa-edit"></i>
                    </a>
                `;
            }

            buttonsHtml += `
                <button type="button" 
                        class="btn btn-sm btn-danger delete-anuncio-btn" 
                        data-id="${anuncio.id}" 
                        title="Excluir Anúncio">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;

            return buttonsHtml;
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
                updateTable(data.anuncios);
                updatePagination(data.pagination);
                updateDashboardStats(data.dashboard_stats);
            } else {
                if (noResultsMessage) {
                    noResultsMessage.classList.remove('d-none');
                    noResultsMessage.textContent = data.message || 'Nenhum anúncio encontrado com os critérios de busca.';
                }
                updateDashboardStats(data.dashboard_stats);
                console.info('INFO JS: Nenhuma anúncio encontrado ou falha na requisição:', data.message);
            }
        }

        function updateTable(anuncios) {
            let tableHtml = '';
            if (anuncios.length > 0) {
                anuncios.forEach(anuncio => {
                    const statusBadge = getStatusBadgeHtml(anuncio.status, anuncio.id);
                    const actionButtons = getActionButtonsHtml(anuncio);
                    tableHtml += `
                        <tr id="anuncio-row-${anuncio.id}">
                            <td>${anuncio.id}</td>
                            <td>${anuncio.user_name}</td>
                            <td class="d-none d-md-table-cell">${anuncio.user_email}</td>
                            <td class="d-none d-md-table-cell">${anuncio.category}</td>
                            <td>${statusBadge}</td>
                            <td class="d-none d-md-table-cell">${anuncio.city_name} - ${anuncio.state_name}</td>
                            <td class="d-none d-md-table-cell">${anuncio.created_at}</td>
                            <td>
                                <div class="btn-group" role="group" aria-label="Ações do Anúncio">
                                    ${actionButtons}
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tableHtml = `<tr><td colspan="8" class="text-center">Nenhum anúncio encontrado.</td></tr>`;
            }
            anunciosTableBody.innerHTML = tableHtml;
            attachEventListenersToTableButtons();
        }

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

                for (let i = 1; i <= pagination.total_pages; i++) {
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

        function updateDashboardStats(stats) {
            if (totalAnunciosCount) totalAnunciosCount.textContent = stats.total_anuncios ?? '0';
            if (activeAnunciosCount) activeAnunciosCount.textContent = stats.active_anuncios ?? '0';
            if (pendingAnunciosCount) pendingAnunciosCount.textContent = stats.pending_anuncios ?? '0';
            if (approvalRate) approvalRate.textContent = stats.approval_rate ?? '0%';
        }

        async function handleAnuncioAction(anuncioId, actionType) {
            // Este showLoadingModal é para a ação específica dentro da tabela, não a transição de página.
            if (typeof window.showLoadingModal === 'function') window.showLoadingModal('Processando...'); 

            let actionUrl = `${URLADM}anuncio/${actionType}Anuncio`;
            if (actionType === 'delete') {
                actionUrl = `${URLADM}anuncio/deleteAnuncio`;
            }

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ anuncio_id: anuncioId })
                });
                
                const data = await response.json();

                // Este hideLoadingModal é para a ação específica dentro da tabela.
                if (typeof window.hideLoadingModal === 'function') await window.hideLoadingModal(); 

                if (data.success) {
                    if (typeof window.showFeedbackModal === 'function') {
                        window.showFeedbackModal('success', data.message, 'Sucesso');
                    }
                    const currentPage = paginationContainer.querySelector('.page-item.active .page-link-ajax')?.dataset.page || 1;
                    const searchTerm = searchInput.value;
                    const filterStatusElement = statusFilter.querySelector('.filter-item.active');
                    const filterStatus = filterStatusElement ? filterStatusElement.dataset.filterStatus : 'all';
                    loadAnuncios(currentPage, searchTerm, filterStatus);
                } else {
                    if (typeof window.showFeedbackModal === 'function') {
                        window.showFeedbackModal('error', data.message || `Erro ao ${actionType} o anúncio.`, 'Erro');
                    }
                }
            } catch (error) {
                // Este hideLoadingModal é para a ação específica dentro da tabela, em caso de erro.
                if (typeof window.hideLoadingModal === 'function') await window.hideLoadingModal(); 
                console.error(`Erro ao ${actionType} anúncio:`, error);
                if (typeof window.showFeedbackModal === 'function') {
                    window.showFeedbackModal('error', `Erro de conexão ao ${actionType} o anúncio.`, 'Erro de Rede');
                } else {
                    alert(`Erro de conexão ao ${actionType} o anúncio: ${error.message}`);
                }
            }
        }

        function attachEventListenersToTableButtons() {
            if (anunciosTableBody) {
                anunciosTableBody.removeEventListener('click', handleTableButtonClick);
                anunciosTableBody.addEventListener('click', handleTableButtonClick);
            }
        }

        function handleTableButtonClick(event) {
            const target = event.target.closest('button, a');
            if (!target) return;

            const anuncioId = target.dataset.id;
            if (!anuncioId) {
                console.warn('AVISO JS: Botão de ação clicado sem data-id.');
                return;
            }

            if (target.classList.contains('approve-anuncio-btn')) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal('Aprovar Anúncio', 'Tem certeza que deseja aprovar este anúncio? Ele ficará visível publicamente.', () => {
                        handleAnuncioAction(anuncioId, 'approve');
                    });
                } else {
                    if (confirm('Tem certeza que deseja aprovar este anúncio? Ele ficará visível publicamente.')) {
                        handleAnuncioAction(anuncioId, 'approve');
                    }
                }
            } else if (target.classList.contains('reject-anuncio-btn')) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal('Rejeitar Anúncio', 'Tem certeza que deseja rejeitar este anúncio? Ele não ficará visível publicamente.', () => {
                        handleAnuncioAction(anuncioId, 'reject');
                    });
                } else {
                    if (confirm('Tem certeza que deseja rejeitar este anúncio? Ele não ficará visível publicamente.')) {
                        handleAnuncioAction(anuncioId, 'reject');
                    }
                }
            } else if (target.classList.contains('activate-anuncio-btn')) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal('Ativar Anúncio', 'Tem certeza que deseja ativar este anúncio? Ele voltará a ficar visível no site.', () => {
                        handleAnuncioAction(anuncioId, 'activate');
                    });
                } else {
                    if (confirm('Tem certeza que deseja ativar este anúncio? Ele voltará a ficar visível no site.')) {
                        handleAnuncioAction(anuncioId, 'activate');
                    }
                }
            } else if (target.classList.contains('deactivate-anuncio-btn')) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal('Pausar Anúncio', 'Tem certeza que deseja pausar seu anúncio? Ele não ficará visível no site.', () => {
                        handleAnuncioAction(anuncioId, 'deactivate');
                    });
                } else {
                    if (confirm('Tem certeza que deseja pausar seu anúncio? Ele não ficará visível no site.')) {
                        handleAnuncioAction(anuncioId, 'deactivate');
                    }
                }
            } else if (target.classList.contains('delete-anuncio-btn')) {
                if (typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal('Excluir Anúncio', 'ATENÇÃO: Esta ação é irreversível e excluirá o anúncio e todas as mídias associadas permanentemente. Tem certeza?', () => {
                        handleAnuncioAction(anuncioId, 'delete');
                    });
                } else {
                    if (confirm('ATENÇÃO: Esta ação é irreversível e excluirá o anúncio e todas as mídias associadas permanentemente. Tem certeza?')) {
                        handleAnuncioAction(anuncioId, 'delete');
                    }
                }
            } else if (target.classList.contains('view-anuncio-btn')) {
                if (typeof window.loadContent === 'function') {
                    window.loadContent(`${URLADM}anuncio/visualizarAnuncio?id=${anuncioId}`, 'anuncio/visualizarAnuncio');
                } else {
                    console.error('ERRO JS: window.loadContent não está definida. Não foi possível navegar para a visualização do anúncio.');
                    window.location.href = `${URLADM}anuncio/visualizarAnuncio?id=${anuncioId}`;
                }
            } else if (target.classList.contains('edit-anuncio-btn')) {
                if (typeof window.loadContent === 'function') {
                    window.loadContent(`${URLADM}anuncio/editarAnuncio?id=${anuncioId}`, 'anuncio/editarAnuncio');
                } else {
                    console.error('ERRO JS: window.loadContent não está definida. Não foi possível navegar para a edição do anúncio.');
                    window.location.href = `${URLADM}anuncio/editarAnuncio?id=${anuncioId}`;
                }
            }
        }

        // Event Listeners para Paginação (delegação)
        if (paginationContainer) {
            paginationContainer.addEventListener('click', function(event) {
                const target = event.target.closest('.page-link-ajax');
                if (target) {
                    event.preventDefault();
                    const page = parseInt(target.dataset.page);
                    const searchTerm = searchInput.value;
                    const filterStatusElement = statusFilter.querySelector('.filter-item.active');
                    const filterStatus = filterStatusElement ? filterStatusElement.dataset.filterStatus : 'all';
                    loadAnuncios(page, searchTerm, filterStatus);
                }
            });
        }

        // Event Listener para o formulário de busca
        if (searchForm) {
            searchForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const searchTerm = searchInput.value;
                const filterStatusElement = statusFilter.querySelector('.filter-item.active');
                const filterStatus = filterStatusElement ? filterStatusElement.dataset.filterStatus : 'all';
                loadAnuncios(1, searchTerm, filterStatus);
            });
        }

        // Event Listener para os filtros de status
        if (statusFilter) {
            statusFilter.addEventListener('click', function(event) {
                const target = event.target.closest('.filter-item');
                if (target) {
                    event.preventDefault();
                    statusFilter.querySelectorAll('.filter-item').forEach(item => item.classList.remove('active'));
                    target.classList.add('active');
                    const searchTerm = searchInput.value;
                    const filterStatus = target.dataset.filterStatus;
                    loadAnuncios(1, searchTerm, filterStatus);
                }
            });
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

// O DOMContentLoaded listener original foi removido, pois a inicialização
// agora é controlada pelo dashboard_custom.js que chama initializeAnunciosListPage.
