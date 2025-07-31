/**
 * admin_users.js
 *
 * Este arquivo JavaScript lida com a lógica de frontend para a página de gerenciamento de usuários
 * no painel administrativo. Ele gerencia a exibição da lista de usuários, paginação,
 * pesquisa, filtragem e ações de soft delete/ativação via AJAX.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verifica se a variável 'baseUrl' está definida no escopo global (definida no PHP)
    if (typeof baseUrl === 'undefined') {
        console.error('Erro: A variável baseUrl não está definida. Certifique-se de que está sendo passada do PHP.');
        return;
    }

    const userSearchInput = document.getElementById('userSearchInput');
    const userStatusFilter = document.getElementById('userStatusFilter');
    const buttonSearchUsers = document.getElementById('button-search-users');
    const usersTableBody = document.getElementById('usersTableBody');
    const usersPagination = document.getElementById('usersPagination');
    const messageContainer = document.querySelector('.container-fluid .alert'); // Container para mensagens

    /**
     * Exibe uma mensagem de feedback para o usuário.
     * @param {string} type Tipo de mensagem (success, danger, info, warning).
     * @param {string} text Conteúdo da mensagem.
     */
    function showMessage(type, text) {
        if (messageContainer) {
            // Remove classes antigas e adiciona a nova
            messageContainer.className = `alert alert-${type} alert-dismissible fade show`;
            messageContainer.innerHTML = `${text}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>`;
            messageContainer.style.display = 'block'; // Garante que esteja visível
        } else {
            // Fallback para console.log se o container não for encontrado
            console.log(`Mensagem (${type}): ${text}`);
        }
    }

    /**
     * Limpa as mensagens de feedback.
     */
    function clearMessages() {
        if (messageContainer) {
            messageContainer.style.display = 'none';
            messageContainer.innerHTML = '';
        }
    }

    /**
     * Constrói a URL para a requisição AJAX com base nos parâmetros de busca e filtro.
     * @param {number} page Número da página atual.
     * @returns {string} URL completa para a requisição.
     */
    function buildAjaxUrl(page) {
        const searchTerm = userSearchInput.value.trim();
        const filterStatus = userStatusFilter.value;
        let url = `${baseUrl}/getUsersData?page=${page}`;

        if (searchTerm) {
            url += `&search=${encodeURIComponent(searchTerm)}`;
        }
        if (filterStatus && filterStatus !== 'all') {
            url += `&status=${encodeURIComponent(filterStatus)}`;
        }
        return url;
    }

    /**
     * Carrega os usuários via AJAX e atualiza a tabela e a paginação.
     * @param {number} page A página a ser carregada.
     */
    async function loadUsers(page) {
        clearMessages(); // Limpa mensagens anteriores ao carregar novos dados
        usersTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Carregando usuários...</td></tr>';
        usersPagination.innerHTML = ''; // Limpa paginação anterior

        try {
            const response = await fetch(buildAjaxUrl(page));
            const data = await response.json();

            if (data.success) {
                renderUsersTable(data.users);
                renderPagination(data.pagination);
            } else {
                usersTableBody.innerHTML = `<tr><td colspan="7" class="text-center">${data.message || 'Erro ao carregar usuários.'}</td></tr>`;
                showMessage('danger', data.message || 'Erro ao carregar usuários.');
            }
        } catch (error) {
            console.error('Erro ao buscar dados de usuários:', error);
            usersTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Erro de rede ou servidor. Tente novamente.</td></tr>';
            showMessage('danger', 'Erro de rede ou servidor ao carregar usuários.');
        }
    }

    /**
     * Renderiza as linhas da tabela de usuários.
     * @param {Array} users Array de objetos de usuário.
     */
    function renderUsersTable(users) {
        usersTableBody.innerHTML = ''; // Limpa o corpo da tabela
        if (users.length === 0) {
            usersTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum usuário encontrado.</td></tr>';
            return;
        }

        users.forEach(user => {
            const statusClass = getStatusBadgeClass(user.status);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.id}</td>
                <td>${user.nome}</td>
                <td>${user.email}</td>
                <td>${user.nivel_acesso}</td>
                <td><span class="${statusClass}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span></td>
                <td>${user.ultimo_acesso ? new Date(user.ultimo_acesso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A'}</td>
                <td>
                    ${user.status !== 'deleted' ? `
                        <button class="btn btn-danger btn-sm soft-delete-user-btn" data-user-id="${user.id}" title="Desativar Conta">
                            <i class="fas fa-trash"></i> Soft Delete
                        </button>
                    ` : `
                        <button class="btn btn-success btn-sm activate-user-btn" data-user-id="${user.id}" title="Ativar Conta">
                            <i class="fas fa-check"></i> Ativar
                        </button>
                    `}
                    <!-- Botão de editar, se houver uma rota de edição de usuário -->
                    <!-- <a href="${baseUrl}/edit/${user.id}" class="btn btn-info btn-sm" title="Editar Usuário">
                        <i class="fas fa-edit"></i>
                    </a> -->
                </td>
            `;
            usersTableBody.appendChild(row);
        });

        // Adiciona event listeners para os novos botões
        addActionButtonListeners();
    }

    /**
     * Retorna a classe CSS para o badge de status.
     * @param {string} status O status do usuário.
     * @returns {string} Classe CSS do badge.
     */
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'ativo': return 'badge badge-success';
            case 'inativo': return 'badge badge-warning';
            case 'pendente': return 'badge badge-info';
            case 'bloqueado':
            case 'suspenso':
            case 'deleted':
                return 'badge badge-danger';
            default: return 'badge badge-secondary';
        }
    }

    /**
     * Renderiza os links de paginação.
     * @param {object} paginationData Dados da paginação (current_page, total_pages, etc.).
     */
    function renderPagination(paginationData) {
        usersPagination.innerHTML = '';
        const { current_page, total_pages } = paginationData;

        // Botão "Anterior"
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${current_page <= 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current_page - 1}">Anterior</a>`;
        usersPagination.appendChild(prevLi);

        // Links de páginas
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === current_page ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            usersPagination.appendChild(pageLi);
        }

        // Botão "Próximo"
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${current_page >= total_pages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${current_page + 1}">Próximo</a>`;
        usersPagination.appendChild(nextLi);

        // Adiciona event listener para os links de paginação
        usersPagination.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && page > 0 && page <= total_pages) {
                    loadUsers(page);
                }
            });
        });
    }

    /**
     * Adiciona event listeners aos botões de ação (soft delete, ativar).
     */
    function addActionButtonListeners() {
        // Botões de Soft Delete
        document.querySelectorAll('.soft-delete-user-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                if (confirm('Tem certeza que deseja desativar esta conta de usuário? Isso também desativará o anúncio associado, se houver.')) {
                    sendActionRequest('softDeleteUser', userId);
                }
            });
        });

        // Botões de Ativar
        document.querySelectorAll('.activate-user-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                if (confirm('Tem certeza que deseja ativar esta conta de usuário?')) {
                    sendActionRequest('activateUser', userId);
                }
            });
        });
    }

    /**
     * Envia uma requisição AJAX para uma ação específica (soft delete, ativar).
     * @param {string} action O nome do método no controlador (ex: 'softDeleteUser', 'activateUser').
     * @param {string} userId O ID do usuário para a ação.
     */
    async function sendActionRequest(action, userId) {
        clearMessages();
        try {
            const response = await fetch(`${baseUrl}/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
                },
                body: JSON.stringify({ user_id: userId })
            });
            const data = await response.json();

            if (data.success) {
                showMessage('success', data.message);
                loadUsers(1); // Recarrega a primeira página para refletir as mudanças
            } else {
                showMessage('danger', data.message || 'Erro ao executar a ação.');
            }
        } catch (error) {
            console.error(`Erro ao executar a ação ${action}:`, error);
            showMessage('danger', 'Erro de rede ou servidor ao executar a ação.');
        }
    }

    // Event Listeners para pesquisa e filtro
    buttonSearchUsers.addEventListener('click', () => loadUsers(1));
    userSearchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadUsers(1);
        }
    });
    userStatusFilter.addEventListener('change', () => loadUsers(1));

    // Carrega os usuários na inicialização da página
    loadUsers(1);
});
