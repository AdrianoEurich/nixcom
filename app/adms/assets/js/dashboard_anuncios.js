// dashboard_anuncios.js
// Este script lida com a lógica específica da página de listagem de anúncios no dashboard.

// As variáveis URLADM e projectBaseURL são definidas globalmente pelo PHP (main.php).
// Não as declare novamente aqui para evitar o erro "Identifier 'URLADM' has already been declared".
// Exemplo de uso: console.log(URLADM);

console.info('INFO JS: dashboard_anuncios.js carregado.');

document.addEventListener('DOMContentLoaded', function() {
    console.info('INFO JS: DOMContentLoaded disparado em dashboard_anuncios.js.');

    // Função para carregar a lista de anúncios
    function loadAnuncios(page = 1, searchTerm = '', filterStatus = 'all') {
        console.debug(`DEBUG JS: loadAnuncios - Carregando anúncios. Página: ${page}, Termo de busca: "${searchTerm}", Status: "${filterStatus}"`);
        const tableBody = document.getElementById('anunciosTableBody');
        const paginationContainer = document.getElementById('paginationContainer');
        const noResultsMessage = document.getElementById('noResultsMessage');
        const loadingSpinner = document.getElementById('loadingSpinner');

        if (tableBody) tableBody.innerHTML = '';
        if (paginationContainer) paginationContainer.innerHTML = '';
        if (noResultsMessage) noResultsMessage.classList.add('d-none');
        if (loadingSpinner) loadingSpinner.classList.remove('d-none');

        const url = `${URLADM}dashboard/getAnunciosData?page=${page}&search=${encodeURIComponent(searchTerm)}&status=${filterStatus}`;

        fetch(url)
            .then(handleResponse)
            .then(data => {
                if (loadingSpinner) loadingSpinner.classList.add('d-none');
                console.debug('DEBUG JS: Dados de anúncios recebidos:', data);

                if (data.success && data.anuncios && data.anuncios.length > 0) {
                    data.anuncios.forEach(anuncio => {
                        const row = tableBody.insertRow();
                        row.innerHTML = `
                            <td>${anuncio.id}</td>
                            <td>${anuncio.user_name}</td>
                            <td>${anuncio.user_email}</td>
                            <td>${anuncio.service_name}</td>
                            <td>${anuncio.state_name} - ${anuncio.city_name}</td>
                            <td>${anuncio.category}</td>
                            <td>${anuncio.created_at}</td>
                            <td>${anuncio.visits}</td>
                            <td>
                                <span class="badge ${getBadgeClass(anuncio.status)}">${getStatusText(anuncio.status)}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-sm btn-info me-1 view-anuncio-btn" data-id="${anuncio.id}" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${anuncio.status === 'pending' ? `
                                    <button class="btn btn-sm btn-success me-1 approve-anuncio-btn" data-id="${anuncio.id}" title="Aprovar">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger me-1 reject-anuncio-btn" data-id="${anuncio.id}" title="Rejeitar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    ` : ''}
                                    <button class="btn btn-sm btn-danger delete-anuncio-btn" data-id="${anuncio.id}" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                    });
                    renderPagination(data.pagination_data);
                } else {
                    if (noResultsMessage) {
                        noResultsMessage.classList.remove('d-none');
                        noResultsMessage.textContent = data.message || 'Nenhum anúncio encontrado com os critérios de busca.';
                    }
                    console.info('INFO JS: Nenhuma anúncio encontrado ou falha na requisição:', data.message);
                }
            })
            .catch(error => {
                if (loadingSpinner) loadingSpinner.classList.add('d-none');
                console.error('ERRO JS: Erro ao carregar anúncios:', error);
                showFeedbackModal('error', 'Falha ao carregar anúncios. Por favor, tente novamente.', 'Erro de Carregamento');
            });
    }

    // Funções auxiliares para status e badges
    function getBadgeClass(status) {
        switch (status) {
            case 'active': return 'bg-success';
            case 'pending': return 'bg-warning text-dark';
            case 'rejected': return 'bg-danger';
            case 'inactive': return 'bg-secondary';
            default: return 'bg-info';
        }
    }

    function getStatusText(status) {
        switch (status) {
            case 'active': return 'Ativo';
            case 'pending': return 'Pendente';
            case 'rejected': return 'Rejeitado';
            case 'inactive': return 'Inativo';
            default: return 'Desconhecido';
        }
    }

    // Renderização da Paginação
    function renderPagination(pagination) {
        const paginationContainer = document.getElementById('paginationContainer');
        if (!paginationContainer) return;

        paginationContainer.innerHTML = ''; // Limpa paginação anterior

        const ul = document.createElement('ul');
        ul.classList.add('pagination', 'justify-content-center');

        // Botão "Anterior"
        const prevLi = document.createElement('li');
        prevLi.classList.add('page-item');
        if (pagination.current_page <= 1) prevLi.classList.add('disabled');
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${pagination.current_page - 1}">Anterior</a>`;
        ul.appendChild(prevLi);

        // Números das páginas
        for (let i = 1; i <= pagination.total_pages; i++) {
            const pageLi = document.createElement('li');
            pageLi.classList.add('page-item');
            if (i === pagination.current_page) pageLi.classList.add('active');
            pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            ul.appendChild(pageLi);
        }

        // Botão "Próximo"
        const nextLi = document.createElement('li');
        nextLi.classList.add('page-item');
        if (pagination.current_page >= pagination.total_pages) nextLi.classList.add('disabled');
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${pagination.current_page + 1}">Próximo</a>`;
        ul.appendChild(nextLi);

        paginationContainer.appendChild(ul);

        // Adiciona event listeners aos links de paginação
        ul.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && page > 0 && page <= pagination.total_pages) {
                    loadAnuncios(page, pagination.search_term, pagination.filter_status);
                }
            });
        });
    }

    // Event listener para o formulário de busca
    const searchForm = document.getElementById('searchAnunciosForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.getElementById('searchInput').value;
            const filterStatus = document.getElementById('statusFilter').value;
            console.debug(`DEBUG JS: searchForm submitted - Termo: "${searchTerm}", Status: "${filterStatus}"`);
            loadAnuncios(1, searchTerm, filterStatus); // Sempre volta para a primeira página na busca
        });
    }

    // Event listeners para os botões de ação (aprovar, rejeitar, deletar)
    const anunciosTableBody = document.getElementById('anunciosTableBody');
    if (anunciosTableBody) {
        anunciosTableBody.addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;

            const anuncioId = target.dataset.id;
            if (!anuncioId) {
                console.warn('AVISO JS: Botão de ação clicado sem data-id.');
                return;
            }

            if (target.classList.contains('approve-anuncio-btn')) {
                showConfirmModal('Aprovar Anúncio', 'Tem certeza que deseja aprovar este anúncio? Ele ficará visível publicamente.', () => {
                    sendAnuncioAction(anuncioId, 'approveAnuncio');
                });
            } else if (target.classList.contains('reject-anuncio-btn')) {
                showConfirmModal('Rejeitar Anúncio', 'Tem certeza que deseja rejeitar este anúncio? Ele não ficará visível publicamente.', () => {
                    sendAnuncioAction(anuncioId, 'rejectAnuncio');
                });
            } else if (target.classList.contains('delete-anuncio-btn')) {
                showConfirmModal('Excluir Anúncio', 'ATENÇÃO: Esta ação é irreversível e excluirá o anúncio e todas as mídias associadas permanentemente. Tem certeza?', () => {
                    sendAnuncioAction(anuncioId, 'deleteAnuncio');
                });
            } else if (target.classList.contains('view-anuncio-btn')) {
                // Redireciona para a página de visualização do anúncio (SPA)
                loadContent(`${URLADM}anuncio/visualizarAnuncio?anuncio_id=${anuncioId}`);
            }
        });
    }

    // Função para enviar ações de aprovar/rejeitar/deletar
    function sendAnuncioAction(anuncioId, action) {
        console.debug(`DEBUG JS: sendAnuncioAction - Enviando ação "${action}" para Anúncio ID: ${anuncioId}`);
        showLoadingModal('Processando...');

        fetch(`${URLADM}anuncio/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
            },
            body: JSON.stringify({ anuncio_id: anuncioId })
        })
        .then(handleResponse)
        .then(data => {
            hideLoadingModal();
            if (data.success) {
                showFeedbackModal('success', data.message, 'Sucesso');
                // Recarrega a lista de anúncios para refletir as mudanças
                const currentPage = parseInt(document.querySelector('.pagination .page-item.active .page-link')?.dataset.page || 1);
                const searchTerm = document.getElementById('searchInput')?.value || '';
                const filterStatus = document.getElementById('statusFilter')?.value || 'all';
                loadAnuncios(currentPage, searchTerm, filterStatus);
            } else {
                showFeedbackModal('error', data.message, 'Erro');
            }
        })
        .catch(error => {
            hideLoadingModal();
            console.error(`ERRO JS: Erro na ação "${action}" para Anúncio ID ${anuncioId}:`, error);
            showFeedbackModal('error', `Falha na comunicação com o servidor: ${error.message}`, 'Erro de Rede');
        });
    }

    // Carrega os anúncios ao iniciar a página
    loadAnuncios();
});
