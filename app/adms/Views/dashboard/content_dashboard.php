<?php
// app/adms/Views/dashboard/content_dashboard.php
// Conteúdo principal do Dashboard. Esta view será carregada dentro do layout/main.php.

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Extrai as variáveis passadas pelo controlador
// Certifique-se de que todas as variáveis esperadas estão sendo passadas pelo controlador.
// Ex: $this->data = ['listAnuncios' => $listAnuncios, 'pagination_data' => $pagination_data, ...];
extract($this->data);

// Variáveis para garantir que existam, mesmo que o controlador não as passe (para evitar erros)
$dashboard_stats = $dashboard_stats ?? [];
$listAnuncios = $listAnuncios ?? [];
$pagination_data = $pagination_data ?? [];

$current_page = $pagination_data['current_page'] ?? 1;
$total_pages = $pagination_data['total_pages'] ?? 1;
$search_term = $pagination_data['search_term'] ?? '';
$filter_status = $pagination_data['filter_status'] ?? 'all';

?>
<div class="content p-3" id="dashboardContent">
    <h1 class="h3 mb-4">Dashboard</h1>

    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Anúncios</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAnunciosCount">
                                <?= htmlspecialchars($dashboard_stats['total_anuncios'] ?? '0'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Anúncios Ativos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeAnunciosCount">
                                <?= htmlspecialchars($dashboard_stats['active_anuncios'] ?? '0'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Anúncios Pendentes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingAnunciosCount">
                                <?= htmlspecialchars($dashboard_stats['pending_anuncios'] ?? '0'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Taxa de Aprovação</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="approvalRate">
                                <?= htmlspecialchars($dashboard_stats['approval_rate'] ?? '0%'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percent fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mt-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Anúncios Recentes</h6>
            
            <!-- Formulário de busca e filtros -->
            <form class="search-form d-flex ms-auto" id="searchForm">
                <div class="input-group search-group">
                    <input type="text" class="form-control search-input" id="searchInput" name="search" placeholder="Pesquisar anúncios..." autofocus value="<?= htmlspecialchars($search_term); ?>">
                    <button class="btn btn-primary search-btn" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary filter-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sliders-h"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="filterDropdown">
                            <li>
                                <h6 class="dropdown-header">Filtrar por:</h6>
                            </li>
                            <li><a class="dropdown-item filter-item" href="#" data-filter-status="active"><i class="fas fa-check-circle text-success"></i> Ativos</a></li>
                            <li><a class="dropdown-item filter-item" href="#" data-filter-status="pending"><i class="fas fa-clock text-warning"></i> Pendentes</a></li>
                            <li><a class="dropdown-item filter-item" href="#" data-filter-status="rejected"><i class="fas fa-times-circle text-danger"></i> Rejeitados</a></li>
                            <li><a class="dropdown-item filter-item" href="#" data-filter-status="inactive"><i class="fas fa-pause-circle text-info"></i> Pausados</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item filter-item" href="#" data-filter-status="all"><i class="fas fa-filter"></i> Todos os Status</a></li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Anunciante</th>
                            <th class="d-none d-md-table-cell">Email Anunciante</th>
                            <th class="d-none d-md-table-cell">Gênero</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Localização</th>
                            <th class="d-none d-md-table-cell">Data Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="latestAnunciosTableBody">
                        <?php if (!empty($listAnuncios)): ?>
                            <?php foreach ($listAnuncios as $anuncio): ?>
                                <tr id="anuncio-row-<?= htmlspecialchars($anuncio['id']); ?>">
                                    <td><?= htmlspecialchars($anuncio['id']); ?></td>
                                    <td><?= htmlspecialchars($anuncio['user_name']); ?></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($anuncio['user_email']); ?></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($anuncio['category']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($anuncio['status']) {
                                            case 'active':
                                                $status_class = 'text-bg-success';
                                                $status_text = 'Ativo';
                                                break;
                                            case 'pending':
                                                $status_class = 'text-bg-warning';
                                                $status_text = 'Pendente';
                                                break;
                                            case 'rejected':
                                                $status_class = 'text-bg-danger';
                                                $status_text = 'Rejeitado';
                                                break;
                                            case 'inactive': // Usando 'inactive' para pausado
                                                $status_class = 'text-bg-info';
                                                $status_text = 'Pausado';
                                                break;
                                            default:
                                                $status_class = 'text-bg-secondary';
                                                $status_text = 'Desconhecido';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class; ?>" id="status-badge-<?= htmlspecialchars($anuncio['id']); ?>"><?= $status_text; ?></span>
                                    </td>
                                    <!-- LINHA CORRIGIDA AQUI -->
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($anuncio['city_name'] . ' - ' . $anuncio['state_name']); ?></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($anuncio['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group" aria-label="Ações do Anúncio">
                                            <!-- Botão Visualizar -->
                                            <a href="<?= URLADM; ?>anuncio/visualizarAnuncio?id=<?= htmlspecialchars($anuncio['id']); ?>" 
                                               class="btn btn-sm btn-primary view-anuncio-btn" 
                                               data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                               data-spa="true"
                                               title="Visualizar Anúncio">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if ($anuncio['status'] === 'pending'): ?>
                                                <!-- Botão Aprovar (visível apenas se status for 'pending') -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-success approve-anuncio-btn" 
                                                        data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                        title="Aprovar Anúncio">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <!-- Botão Rejeitar (visível apenas se status for 'pending') -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger reject-anuncio-btn" 
                                                        data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                        title="Rejeitar Anúncio">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($anuncio['status'] === 'active'): ?>
                                                <!-- Botão Pausar (visível apenas se status for 'active') -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning deactivate-anuncio-btn" 
                                                        data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                        title="Pausar Anúncio">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php elseif ($anuncio['status'] === 'inactive'): ?>
                                                <!-- Botão Ativar (visível apenas se status for 'inactive') -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-info activate-anuncio-btn" 
                                                        data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                        title="Ativar Anúncio">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Botão Editar (visível para todos os status exceto rejected/deleted) -->
                                            <?php if ($anuncio['status'] !== 'rejected'): // Assumindo que 'deleted_at' no DB implica 'rejected' ou que não queremos editar rejeitados ?>
                                                <a href="<?= URLADM; ?>anuncio/editarAnuncio?id=<?= htmlspecialchars($anuncio['id']); ?>" 
                                                   class="btn btn-sm btn-secondary edit-anuncio-btn" 
                                                   data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                   data-spa="true"
                                                   title="Editar Anúncio">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Botão Excluir (sempre visível) -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger delete-anuncio-btn" 
                                                    data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                    title="Excluir Anúncio">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum anúncio encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Controles de Paginação -->
            <nav aria-label="Paginação de Anúncios">
                <ul class="pagination justify-content-center" id="paginationControls">
                    <?php if ($total_pages > 1): ?>
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link page-link-ajax" href="#" data-page="<?= $current_page - 1; ?>" data-search="<?= htmlspecialchars($search_term); ?>" data-status="<?= htmlspecialchars($filter_status); ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i === $current_page) ? 'active' : ''; ?>">
                                <a class="page-link page-link-ajax" href="#" data-page="<?= $i; ?>" data-search="<?= htmlspecialchars($search_term); ?>" data-status="<?= htmlspecialchars($filter_status); ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link page-link-ajax" href="#" data-page="<?= $current_page + 1; ?>" data-search="<?= htmlspecialchars($search_term); ?>" data-status="<?= htmlspecialchars($filter_status); ?>" aria-label="Próximo">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal de Confirmação (Rejeitar) -->
<div class="modal fade" id="rejectConfirmModal" tabindex="-1" aria-labelledby="rejectConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectConfirmModalLabel">Confirmar Rejeição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Você tem certeza que deseja rejeitar este anúncio? Esta ação é irreversível.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">Rejeitar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação (Excluir) -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Você tem certeza que deseja excluir este anúncio permanentemente? Esta ação é irreversível e removerá todas as mídias associadas.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container para Mensagens -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toastHeader"></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastBody">
            <!-- Mensagem aqui -->
        </div>
    </div>
</div>

<script>
    // Define URLADM globalmente no JavaScript
    const URLADM = "<?= URLADM; ?>";

    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const filterDropdown = document.getElementById('filterDropdown');
        const latestAnunciosTableBody = document.getElementById('latestAnunciosTableBody');
        const paginationControls = document.getElementById('paginationControls');
        const rejectConfirmModal = new bootstrap.Modal(document.getElementById('rejectConfirmModal'));
        const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        const confirmRejectBtn = document.getElementById('confirmRejectBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        let currentAnuncioIdForAction = null; // Para armazenar o ID do anúncio para a ação do modal

        // Função para exibir mensagens Toast
        function displayToast(type, message) {
            const toastLiveExample = document.getElementById('liveToast');
            const toastHeader = document.getElementById('toastHeader');
            const toastBody = document.getElementById('toastBody');

            toastHeader.textContent = type === 'success' ? 'Sucesso!' : 'Erro!';
            toastHeader.classList.remove('text-success', 'text-danger');
            toastHeader.classList.add(type === 'success' ? 'text-success' : 'text-danger');
            toastBody.textContent = message;

            const toast = new bootstrap.Toast(toastLiveExample);
            toast.show();
        }

        // Função para carregar anúncios via AJAX
        async function loadAnuncios(page = 1, searchTerm = '', filterStatus = 'all') {
            const url = `${URLADM}dashboard/index?ajax=true&page=${page}&search=${encodeURIComponent(searchTerm)}&status=${encodeURIComponent(filterStatus)}`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    updateTable(data.anuncios);
                    updatePagination(data.pagination);
                    updateDashboardStats(data.dashboard_stats);
                    attachEventListenersToTableButtons(); // Re-anexa listeners
                } else {
                    displayToast('error', data.message || 'Erro ao carregar anúncios.');
                }
            } catch (error) {
                console.error('Erro ao carregar anúncios:', error);
                displayToast('error', 'Erro de conexão ao carregar anúncios.');
            }
        }

        // Função para atualizar a tabela de anúncios
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
                            <!-- LINHA CORRIGIDA AQUI NO JAVASCRIPT -->
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
            latestAnunciosTableBody.innerHTML = tableHtml;
        }

        // Função para obter o HTML do badge de status
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

            // Botão Visualizar
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
                // Botão Aprovar
                buttonsHtml += `
                    <button type="button" 
                            class="btn btn-sm btn-success approve-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Aprovar Anúncio">
                        <i class="fas fa-check"></i>
                    </button>
                    <!-- Botão Rejeitar -->
                    <button type="button" 
                            class="btn btn-sm btn-danger reject-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Rejeitar Anúncio">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (anuncio.status === 'active') {
                // Botão Pausar
                buttonsHtml += `
                    <button type="button" 
                            class="btn btn-sm btn-warning deactivate-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Pausar Anúncio">
                        <i class="fas fa-pause"></i>
                    </button>
                `;
            } else if (anuncio.status === 'inactive') {
                // Botão Ativar
                buttonsHtml += `
                    <button type="button" 
                            class="btn btn-sm btn-info activate-anuncio-btn" 
                            data-id="${anuncio.id}" 
                            title="Ativar Anúncio">
                        <i class="fas fa-play"></i>
                    </button>
                `;
            }

            // Botão Editar (visível para todos os status exceto rejected/deleted)
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

            // Botão Excluir (sempre visível)
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

        // Função para atualizar a paginação
        function updatePagination(pagination) {
            let paginationHtml = '';
            if (pagination.total_pages > 1) {
                // Botão Anterior
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

                // Links de página
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

                // Botão Próximo
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
            paginationControls.innerHTML = paginationHtml; // Adicionado aqui para fechar a função
        }

        // Função para atualizar os cards de estatísticas do dashboard
        function updateDashboardStats(stats) {
            document.getElementById('totalAnunciosCount').textContent = stats.total_anuncios ?? '0';
            document.getElementById('activeAnunciosCount').textContent = stats.active_anuncios ?? '0';
            document.getElementById('pendingAnunciosCount').textContent = stats.pending_anuncios ?? '0';
            document.getElementById('approvalRate').textContent = stats.approval_rate ?? '0%';
        }

        // Event Listeners para Paginação (delegation)
        paginationControls.addEventListener('click', function(event) {
            const target = event.target.closest('.page-link-ajax');
            if (target) {
                event.preventDefault();
                const page = parseInt(target.dataset.page);
                const searchTerm = searchInput.value;
                const filterStatus = filterDropdown.querySelector('.filter-item.active')?.dataset.filterStatus || 'all';
                loadAnuncios(page, searchTerm, filterStatus);
            }
        });

        // Event Listener para o formulário de busca
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const searchTerm = searchInput.value;
            const filterStatus = filterDropdown.querySelector('.filter-item.active')?.dataset.filterStatus || 'all';
            loadAnuncios(1, searchTerm, filterStatus); // Sempre volta para a primeira página na busca
        });

        // Event Listener para os filtros de status
        filterDropdown.addEventListener('click', function(event) {
            const target = event.target.closest('.filter-item');
            if (target) {
                event.preventDefault();
                // Remove a classe 'active' de todos os itens de filtro
                filterDropdown.querySelectorAll('.filter-item').forEach(item => item.classList.remove('active'));
                // Adiciona a classe 'active' ao item clicado
                target.classList.add('active');
                const searchTerm = searchInput.value;
                const filterStatus = target.dataset.filterStatus;
                loadAnuncios(1, searchTerm, filterStatus); // Sempre volta para a primeira página ao filtrar
            }
        });

        // Funções para lidar com as ações (aprovar, rejeitar, ativar, desativar, excluir)
        async function handleAnuncioAction(anuncioId, actionType) {
            const url = `${URLADM}anuncio/${actionType}Anuncio?id=${anuncioId}`;
            try {
                const response = await fetch(url, {
                    method: 'POST', // Usar POST para ações que modificam o estado
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
                    },
                    body: JSON.stringify({ action: actionType }) // Opcional: enviar dados adicionais
                });
                const data = await response.json();

                if (data.success) {
                    displayToast('success', data.message);
                    // Recarregar a tabela para refletir as mudanças
                    const currentPage = paginationControls.querySelector('.page-item.active .page-link-ajax')?.dataset.page || 1;
                    const searchTerm = searchInput.value;
                    const filterStatus = filterDropdown.querySelector('.filter-item.active')?.dataset.filterStatus || 'all';
                    loadAnuncios(currentPage, searchTerm, filterStatus);
                } else {
                    displayToast('error', data.message || `Erro ao ${actionType} o anúncio.`);
                }
            } catch (error) {
                console.error(`Erro ao ${actionType} anúncio:`, error);
                displayToast('error', `Erro de conexão ao ${actionType} o anúncio.`);
            }
        }

        // Anexar Event Listeners aos botões da tabela (delegação para elementos dinâmicos)
        function attachEventListenersToTableButtons() {
            // Aprovar Anúncio
            latestAnunciosTableBody.querySelectorAll('.approve-anuncio-btn').forEach(button => {
                button.onclick = function() {
                    const anuncioId = this.dataset.id;
                    handleAnuncioAction(anuncioId, 'approve');
                };
            });

            // Rejeitar Anúncio (abre modal)
            latestAnunciosTableBody.querySelectorAll('.reject-anuncio-btn').forEach(button => {
                button.onclick = function() {
                    currentAnuncioIdForAction = this.dataset.id;
                    rejectConfirmModal.show();
                };
            });

            // Ativar Anúncio
            latestAnunciosTableBody.querySelectorAll('.activate-anuncio-btn').forEach(button => {
                button.onclick = function() {
                    const anuncioId = this.dataset.id;
                    handleAnuncioAction(anuncioId, 'activate');
                };
            });

            // Pausar Anúncio
            latestAnunciosTableBody.querySelectorAll('.deactivate-anuncio-btn').forEach(button => {
                button.onclick = function() {
                    const anuncioId = this.dataset.id;
                    handleAnuncioAction(anuncioId, 'deactivate'); // Ou 'pause' se o método for esse
                };
            });

            // Excluir Anúncio (abre modal)
            latestAnunciosTableBody.querySelectorAll('.delete-anuncio-btn').forEach(button => {
                button.onclick = function() {
                    currentAnuncioIdForAction = this.dataset.id;
                    deleteConfirmModal.show();
                };
            });
        }

        // Confirmar Rejeição do Anúncio
        confirmRejectBtn.addEventListener('click', function() {
            if (currentAnuncioIdForAction) {
                handleAnuncioAction(currentAnuncioIdForAction, 'reject');
                rejectConfirmModal.hide();
                currentAnuncioIdForAction = null; // Limpa o ID
            }
        });

        // Confirmar Exclusão do Anúncio
        confirmDeleteBtn.addEventListener('click', function() {
            if (currentAnuncioIdForAction) {
                handleAnuncioAction(currentAnuncioIdForAction, 'delete');
                deleteConfirmModal.hide();
                currentAnuncioIdForAction = null; // Limpa o ID
            }
        });

        // Anexa os listeners iniciais quando a página carrega
        attachEventListenersToTableButtons();
    });
</script>
