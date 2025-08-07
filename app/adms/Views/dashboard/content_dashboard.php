<?php
// app/adms/Views/dashboard/content_dashboard.php
// Conteúdo principal do Dashboard. Esta view será carregada dentro do layout/main.php.

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Extrai as variáveis passadas pelo controlador.
// Isso garante que os dados mais recentes do controller sobrescrevam quaisquer valores padrão.
extract($this->data);

// Variáveis para garantir que existam, mesmo que o controlador não as passe.
// O operador ?? garante um valor padrão seguro caso a variável não esteja definida.
$dashboard_stats = $dashboard_stats ?? [];
$listAnuncios = $listAnuncios ?? [];
$pagination_data = $pagination_data ?? [];
$user_data = $user_data ?? [];
$anuncio_data = $anuncio_data ?? [];
$has_anuncio = $has_anuncio ?? false;

$current_page = $pagination_data['current_page'] ?? 1;
$total_pages = $pagination_data['total_pages'] ?? 1;
$search_term = $pagination_data['search_term'] ?? '';
$filter_status = $pagination_data['filter_status'] ?? 'all';

// Variáveis para controle de acesso e exibição de usuário
$user_name = $user_data['nome'] ?? $_SESSION['user_name'] ?? 'Usuário';
$user_role = $user_data['nivel_acesso'] ?? $_SESSION['user_level_name'] ?? 'normal';

// Dados do anúncio do usuário, com fallback para a sessão se necessário.
$anuncio_status = $anuncio_data['status'] ?? ($_SESSION['anuncio_status'] ?? 'not_found');
$anuncio_id = $anuncio_data['id'] ?? ($_SESSION['anuncio_id'] ?? '');

error_log("DEBUG DASHBOARD VIEW: user_role=" . $user_role . ", user_name=" . $user_name . ", has_anuncio=" . ($has_anuncio ? 'true' : 'false') . ", anuncio_status=" . $anuncio_status . ", anuncio_id=" . $anuncio_id);
?>
<div class="content pt-0 px-0 pb-3" id="dashboardContent" data-page-type="dashboard">
    <?php if ($user_role === 'administrador') : ?>
        <!-- Conteúdo do Dashboard para Administrador -->
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
                <form class="search-form d-flex ms-auto" id="searchAnunciosForm">
                    <div class="input-group search-group">
                        <input type="text" class="form-control search-input" id="searchInput" name="search" placeholder="Pesquisar anúncios..." autofocus value="<?= htmlspecialchars($search_term); ?>">
                        <button class="btn btn-primary search-btn" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary filter-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-sliders-h"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="statusFilter">
                                <li>
                                    <h6 class="dropdown-header">Filtrar por:</h6>
                                </li>
                                <li><a class="dropdown-item filter-item" href="#" data-filter-status="active"><i class="fas fa-check-circle text-success"></i> Ativos</a></li>
                                <li><a class="dropdown-item filter-item" href="#" data-filter-status="pending"><i class="fas fa-clock text-warning"></i> Pendentes</a></li>
                                <li><a class="dropdown-item filter-item" href="#" data-filter-status="rejected"><i class="fas fa-times-circle text-danger"></i> Rejeitados</a></li>
                                <li><a class="dropdown-item filter-item" href="#" data-filter-status="inactive"><i class="fas fa-pause-circle text-info"></i> Pausados</a></li>
                                <!-- NOVO ITEM: Filtro para anúncios excluídos -->
                                <li><a class="dropdown-item filter-item" href="#" data-filter-status="deleted"><i class="fas fa-trash-alt text-muted"></i> Excluídos</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
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
                                <th class="d-none d-md-table-cell custom-table-col">ID</th>
                                <th>Anunciante</th>
                                <th class="d-none d-md-table-cell custom-table-col">Nome de trabalho</th>
                                <th class="d-none d-md-table-cell custom-table-col">Estado</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="anunciosTableBody">
                            <!-- Conteúdo carregado via AJAX por dashboard_anuncios.js -->
                            <div id="loadingSpinner" class="text-center d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                            <p id="noResultsMessage" class="text-center text-muted d-none">Nenhum anúncio encontrado.</p>
                        </tbody>
                    </table>
                </div>
                <!-- Controles de Paginação -->
                <nav aria-label="Paginação de Anúncios">
                    <ul class="pagination justify-content-center" id="paginationContainer">
                        <!-- Paginação carregada via AJAX por dashboard_anuncios.js -->
                    </ul>
                </nav>
            </div>
        </div>

    <?php else : ?>
        <!-- Conteúdo do Dashboard para Usuário Normal -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg border-0 rounded-lg mt-0 mb-4 p-4">
                    <div class="card-body text-start">
                        <h2 class="card-title text-primary mb-2 fs-4">Bem-vindo(a), <?= htmlspecialchars($user_name) ?>!</h2>
                        <p class="text-muted mb-4 fs-6">Aqui você pode gerenciar seu anúncio e acompanhar o status.</p>

                        <?php if ($has_anuncio) : ?>
                            <?php
                            $statusIcon = '';
                            $statusColorClass = '';
                            $statusBadgeClass = '';

                            switch ($anuncio_status) {
                                case 'pending':
                                    $statusMessage = 'Seu anúncio está <span class="badge bg-warning text-dark">Pendente de Aprovação</span>. Aguarde a revisão do administrador.';
                                    $statusIcon = 'fas fa-clock';
                                    $statusColorClass = 'text-warning';
                                    $statusBadgeClass = 'bg-warning text-dark';
                                    break;
                                case 'active':
                                    $statusMessage = 'Seu anúncio está <span class="badge bg-success">Ativo</span> e visível publicamente.';
                                    $statusIcon = 'fas fa-check-circle';
                                    $statusColorClass = 'text-success';
                                    $statusBadgeClass = 'bg-success';
                                    break;
                                case 'inactive':
                                    $statusMessage = 'Seu anúncio está <span class="badge bg-secondary">Inativo</span>. Ele não está visível publicamente.';
                                    $statusIcon = 'fas fa-pause-circle';
                                    $statusColorClass = 'text-secondary';
                                    $statusBadgeClass = 'bg-secondary';
                                    break;
                                case 'rejected':
                                    $statusMessage = 'Seu anúncio foi <span class="badge bg-danger">Rejeitado</span>. Por favor, revise e edite-o.';
                                    $statusIcon = 'fas fa-times-circle';
                                    $statusColorClass = 'text-danger';
                                    $statusBadgeClass = 'bg-danger';
                                    break;
                                default:
                                    $statusMessage = 'Status do seu anúncio é <span class="badge bg-info">Desconhecido</span>. Contate o suporte se precisar de ajuda.';
                                    $statusIcon = 'fas fa-question-circle';
                                    $statusColorClass = 'text-info';
                                    $statusBadgeClass = 'bg-info';
                                    break;
                            }
                            ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body text-start">
                                    <h5 class="card-title text-muted mb-3 fs-5">Status do Seu Anúncio</h5>
                                    <p class="card-text fs-5 <?= $statusColorClass ?>">
                                        <i class="<?= $statusIcon ?> me-2"></i>
                                        <?= $statusMessage ?>
                                    </p>
                                    <div class="d-flex flex-column flex-sm-row justify-content-start gap-3 mt-4">
                                        <a href="<?= URLADM ?>anuncio/visualizarAnuncio?id=<?= htmlspecialchars($anuncio_id) ?>" class="btn btn-info btn-lg flex-grow-1 flex-sm-grow-0" data-spa="true">
                                            <i class="fas fa-eye me-2"></i>Visualizar Anúncio
                                        </a>
                                        <a href="<?= URLADM ?>anuncio/editarAnuncio?id=<?= htmlspecialchars($anuncio_id) ?>" class="btn btn-warning btn-lg flex-grow-1 flex-sm-grow-0" data-spa="true">
                                            <i class="fas fa-edit me-2"></i>Editar Anúncio
                                        </a>
                                    </div>
                                </div>
                            </div>

                        <?php else : ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body text-start">
                                    <h5 class="card-title text-muted mb-3 fs-5">Você ainda não possui um anúncio.</h5>
                                    <p class="card-text fs-5">Crie um agora para começar a divulgar seus serviços!</p>
                                    <a href="<?= URLADM ?>anuncio/index" class="btn btn-primary btn-lg mt-3" data-spa="true">
                                        <i class="fas fa-plus-circle me-2"></i>Criar Novo Anúncio
                                    </a>
                                </div>
                            </div>

                        <?php endif; ?>

                        <hr class="my-3">

                        <div class="row gx-4 gy-4">
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-start">
                                        <i class="fas fa-user-circle fa-3x text-secondary mb-3"></i>
                                        <h5 class="card-title fs-5">Seu Perfil</h5>
                                        <p class="card-text text-muted fs-6">Mantenha suas informações pessoais atualizadas.</p>
                                        <a href="<?= URLADM ?>perfil/index" class="btn btn-outline-secondary mt-3" data-spa="true">
                                            <i class="fas fa-id-card me-2"></i>Ver Perfil
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-start">
                                        <i class="fas fa-wallet fa-3x text-success mb-3"></i>
                                        <h5 class="card-title fs-5">Financeiro</h5>
                                        <p class="card-text text-muted fs-6">Gerencie seus pagamentos e assinaturas.</p>
                                        <a href="<?= URLADM ?>financeiro" class="btn btn-outline-success mt-3" data-spa="true">
                                            <i class="fas fa-money-bill-alt me-2"></i>Acessar Financeiro
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
