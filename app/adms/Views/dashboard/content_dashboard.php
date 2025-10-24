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
$user_plan = $user_data['plan_type'] ?? $_SESSION['user_plan'] ?? 'free';
$payment_status = $user_data['payment_status'] ?? $_SESSION['payment_status'] ?? 'pending';

// Dados do anúncio do usuário, com fallback para a sessão se necessário.
$anuncio_status = $anuncio_data['status'] ?? ($_SESSION['anuncio_status'] ?? 'not_found');
$anuncio_id = $anuncio_data['id'] ?? ($_SESSION['anuncio_id'] ?? '');

error_log("DEBUG DASHBOARD VIEW: user_role=" . $user_role . ", user_name=" . $user_name . ", has_anuncio=" . ($has_anuncio ? 'true' : 'false') . ", anuncio_status=" . $anuncio_status . ", anuncio_id=" . $anuncio_id);
?>
<div class="content pt-0 px-0 pb-3" id="dashboardContent" data-page-type="dashboard">
    <?php if ($user_role === 'administrador') : ?>
        <!-- Conteúdo do Dashboard para Administrador -->
        <!--<h1 class="h3 mb-4">Dashboard</h1>-->
        <div class="row g-3">
            <div class="col-xl-3 col-md-6">
                <div class="admin-stats-card primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Anúncios</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAnunciosCount">
                                    <?= htmlspecialchars($dashboard_stats['total_anuncios'] ?? '0'); ?>
                                </div>
                                <div class="growth-indicator positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+12% este mês</span>
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
                <div class="admin-stats-card success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Anúncios Ativos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeAnunciosCount">
                                    <?= htmlspecialchars($dashboard_stats['active_anuncios'] ?? '0'); ?>
                                </div>
                                <div class="growth-indicator positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+8% este mês</span>
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
                <div class="admin-stats-card warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Anúncios Pendentes</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingAnunciosCount">
                                    <?= htmlspecialchars($dashboard_stats['pending_anuncios'] ?? '0'); ?>
                                </div>
                                <div class="growth-indicator negative">
                                    <i class="fas fa-arrow-down"></i>
                                    <span>-3% este mês</span>
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
                <div class="admin-stats-card info">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Taxa de Aprovação</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="approvalRate">
                                    <?= htmlspecialchars($dashboard_stats['approval_rate'] ?? '0%'); ?>
                                </div>
                                <div class="growth-indicator positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+5% este mês</span>
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

        <div class="announcements-card">
            <div class="announcements-header">
                <h2 class="announcements-title">
                    <i class="fas fa-list-alt"></i>
                    Anúncios Recentes
                </h2>

                <!-- Barra de pesquisa e filtros -->
                <div class="search-controls">
                    <form class="search-form" id="searchAnunciosForm">
                        <input type="text" 
                               class="search-input" 
                               id="searchInput" 
                               name="search" 
                               placeholder="🔍 Pesquisar anúncios..." 
                               autofocus 
                               value="<?= htmlspecialchars($search_term); ?>">
                        <button class="search-btn" type="submit">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    <button class="filter-btn"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#filtersModal">
                        <i class="fas fa-filter"></i>
                        Filtros
                    </button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table announcements-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Conta</th>
                                <th>Anunciante</th>
                                <th>Plano</th>
                                <th>Estado</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="anunciosTableBody">
                            <!-- Conteúdo carregado via AJAX -->
                            <div id="loadingSpinner" class="loading-state d-none">
                                <div class="loading-spinner"></div>
                                <p>Carregando anúncios...</p>
                            </div>
                            <div id="noResultsMessage" class="empty-state d-none">
                                <i class="fas fa-search"></i>
                                <h3>Nenhum anúncio encontrado</h3>
                                <p>Tente ajustar os filtros ou termos de busca</p>
                            </div>
                        </tbody>
                    </table>
                </div>
                <!-- Paginação -->
                <nav aria-label="Paginação de Anúncios" class="pagination-modern">
                    <ul class="pagination justify-content-center" id="paginationContainer">
                        <!-- Paginação carregada via AJAX -->
                    </ul>
                </nav>
            </div>
        </div>

    <?php else : ?>        <!-- Dashboard Moderna para Usuário Normal -->
        <div class="modern-dashboard">
            <!-- Header com saudação -->
            <div class="dashboard-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="dashboard-title mb-2">
                            <i class="fas fa-sun text-warning me-3"></i>
                            Olá, <span class="text-primary"><?= htmlspecialchars($user_name) ?></span>!
                        </h1>
                        <p class="dashboard-subtitle text-muted mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?= date('d/m/Y') ?> • 
                            <i class="fas fa-clock me-2"></i>
                            <?= date('H:i') ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="weather-widget">
                            <i class="fas fa-cloud-sun text-info me-2"></i>
                            <span class="text-muted">Bom dia para trabalhar!</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status do Anúncio -->
            <?php if ($has_anuncio) : ?>
                <?php
                $statusConfig = [
                    'pending' => [
                        'message' => 'Seu anúncio está pendente de aprovação',
                        'icon' => 'fas fa-clock',
                        'color' => 'warning',
                        'badge' => 'bg-warning text-dark',
                        'description' => 'Aguarde a revisão do administrador'
                    ],
                    'active' => [
                        'message' => 'Seu anúncio está ativo e visível',
                        'icon' => 'fas fa-check-circle',
                        'color' => 'success',
                        'badge' => 'bg-success',
                        'description' => 'Perfeito! Seus clientes podem encontrá-lo'
                    ],
                    'pausado' => [
                        'message' => 'Seu anúncio está pausado',
                        'icon' => 'fas fa-pause-circle',
                        'color' => 'info',
                        'badge' => 'bg-info',
                        'description' => 'Não está visível publicamente'
                    ],
                    'rejected' => [
                        'message' => 'Seu anúncio foi rejeitado',
                        'icon' => 'fas fa-times-circle',
                        'color' => 'danger',
                        'badge' => 'bg-danger',
                        'description' => 'Revise e edite para reenviar'
                    ]
                ];
                
                $status = $statusConfig[$anuncio_status] ?? $statusConfig['pending'];
                ?>
                
                <div class="announcement-status-card mb-4">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="status-content">
                                        <div class="status-header mb-3">
                                            <h5 class="status-title mb-2">
                                                <i class="<?= $status['icon'] ?> text-<?= $status['color'] ?> me-2"></i>
                                                Status do Anúncio
                                            </h5>
                                            <span class="badge <?= $status['badge'] ?> fs-6 px-3 py-2">
                                                <?= $status['message'] ?>
                                            </span>
                                        </div>
                                        <p class="status-description text-muted mb-0">
                                            <?= $status['description'] ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="status-actions">
                                        <a href="<?= URLADM ?>anuncio/visualizarAnuncio?id=<?= htmlspecialchars($anuncio_id) ?>" 
                                           class="btn btn-outline-primary btn-lg me-2" data-spa="true">
                                            <i class="fas fa-eye me-2"></i>Visualizar
                                        </a>
                                        <a href="<?= URLADM ?>anuncio/editarAnuncio?id=<?= htmlspecialchars($anuncio_id) ?>" 
                                           class="btn btn-primary btn-lg" data-spa="true">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <!-- Card para criar anúncio -->
                <div class="create-announcement-card mb-4">
                    <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-plus-circle fa-4x mb-3 opacity-75"></i>
                            <h3 class="card-title mb-3">Crie seu primeiro anúncio</h3>
                            <p class="card-text mb-4 fs-5 opacity-90">
                                <?php if (in_array($user_plan, ['basic', 'premium']) && $payment_status !== 'paid'): ?>
                                    Faça o pagamento para liberar
                                <?php else: ?>
                                    Comece a divulgar seus serviços e atraia novos clientes
                                <?php endif; ?>
                            </p>
                            
                            <?php 
                            // Debug das variáveis
                            error_log("DEBUG DASHBOARD: user_plan=" . $user_plan . ", payment_status=" . $payment_status);
                            
                            if ($user_plan === 'free'): ?>
                                <!-- Usuário com plano gratuito - botão habilitado -->
                                <a href="<?= URLADM ?>anuncio/index" class="btn btn-light btn-lg px-4" data-spa="true">
                                    <i class="fas fa-rocket me-2"></i>Criar Anúncio
                                </a>
                            <?php elseif ($user_plan === 'basic' || $user_plan === 'premium'): ?>
                                <?php if ($payment_status === 'paid'): ?>
                                    <!-- Usuário com plano pago e pagamento confirmado -->
                                    <a href="<?= URLADM ?>anuncio/index" class="btn btn-primary btn-lg px-4" data-spa="true">
                                        <i class="fas fa-rocket me-2"></i>Criar Anúncio Agora
                                    </a>
                                <?php else: ?>
                                    <!-- Usuário com plano pago mas pagamento pendente -->
                                    <div class="alert alert-warning mb-3">
                                        <i class="fas fa-clock me-2"></i>
                                        Aguardando confirmação do pagamento
                                    </div>
                                    <div class="d-flex flex-column align-items-center gap-3">
                                        <button class="btn btn-light btn-lg px-4" disabled>
                                            <i class="fas fa-lock me-2"></i>Criar Anúncio (Bloqueado)
                                        </button>
                                        <a href="<?= URLADM ?>pagamento?plan=<?= $user_plan ?>" class="btn btn-success btn-lg px-4" data-spa="true">
                                            <i class="fas fa-credit-card me-2"></i>Pagar Anúncio
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Usuário sem plano definido -->
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Escolha um plano para começar
                                </div>
                                <a href="<?= URLADM ?>planos" class="btn btn-light btn-lg px-4" data-spa="true">
                                    <i class="fas fa-crown me-2"></i>Escolher Plano
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Informações do Plano -->
            <div class="plan-info-card mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="card-title mb-2">
                                    <i class="fas fa-crown me-2 text-warning"></i>
                                    Plano Atual: <?= ucfirst($user_plan) ?>
                                </h5>
                                <p class="text-muted mb-0">
                                    <?php
                                    $planDescriptions = [
                                        'free' => 'Plano gratuito com recursos básicos',
                                        'basic' => 'Plano básico com mais recursos',
                                        'premium' => 'Plano premium com todos os recursos'
                                    ];
                                    echo $planDescriptions[$user_plan] ?? 'Plano não definido';
                                    ?>
                                </p>
                                <?php if (in_array($user_plan, ['basic', 'premium']) && $payment_status !== 'paid'): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Pagamento Pendente
                                        </span>
                                    </div>
                                <?php elseif (in_array($user_plan, ['basic', 'premium']) && $payment_status === 'paid'): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Plano Ativo
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <!-- Botão Mudar Plano para TODOS os planos -->
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changePlanModal">
                                        <i class="fas fa-exchange-alt me-2"></i>Mudar Plano
                                    </button>
                                    
                                    <?php if (in_array($user_plan, ['basic', 'premium']) && $payment_status !== 'approved'): ?>
                                        <!-- Botão para efetuar pagamento -->
                                        <a href="<?= URLADM ?>pagamento?plan=<?= $user_plan ?>" class="btn btn-success btn-sm" data-spa="true">
                                            <i class="fas fa-credit-card me-2"></i>Efetuar Pagamento
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards de funcionalidades -->
            <div class="features-grid mb-4">
                <div class="row g-4">
                    <!-- Perfil -->
                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card h-100">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4 text-center">
                                    <div class="feature-icon mb-3">
                                        <i class="fas fa-user-circle fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="feature-title mb-3">Meu Perfil</h5>
                                    <p class="feature-description text-muted mb-4">
                                        Mantenha suas informações pessoais sempre atualizadas
                                    </p>
                                    <a href="<?= URLADM ?>perfil/index" class="btn btn-outline-primary" data-spa="true">
                                        <i class="fas fa-id-card me-2"></i>Gerenciar Perfil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financeiro -->
                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card h-100">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4 text-center">
                                    <div class="feature-icon mb-3">
                                        <i class="fas fa-wallet fa-3x text-success"></i>
                                    </div>
                                    <h5 class="feature-title mb-3">Financeiro</h5>
                                    <p class="feature-description text-muted mb-4">
                                        Gerencie pagamentos e acompanhe suas assinaturas
                                    </p>
                                    <a href="<?= URLADM ?>financeiro" class="btn btn-outline-success" data-spa="true">
                                        <i class="fas fa-money-bill-alt me-2"></i>Acessar Financeiro
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Suporte -->
                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card h-100">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4 text-center">
                                    <div class="feature-icon mb-3">
                                        <i class="fas fa-headset fa-3x text-info"></i>
                                    </div>
                                    <h5 class="feature-title mb-3">Suporte</h5>
                                    <p class="feature-description text-muted mb-4">
                                        Precisa de ajuda?<br>
                                        Entre em contato conosco
                                    </p>
                                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#contatoModal">
                                        <i class="fas fa-comments me-2"></i>Entrar em Contato
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estatísticas rápidas -->
            <div class="quick-stats mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 text-center">
                                    <i class="fas fa-eye fa-2x text-primary mb-2"></i>
                                    <h6 class="stat-number mb-1">0</h6>
                                    <small class="text-muted">Visualizações</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 text-center">
                                    <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                    <h6 class="stat-number mb-1">0</h6>
                                    <small class="text-muted">Favoritos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 text-center">
                                    <i class="fas fa-phone fa-2x text-success mb-2"></i>
                                    <h6 class="stat-number mb-1">0</h6>
                                    <small class="text-muted">Contatos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 text-center">
                                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                    <h6 class="stat-number mb-1">0</h6>
                                    <small class="text-muted">Avaliações</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Atividades recentes -->
            <div class="recent-activities">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Atividades Recentes
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="activity-list">
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon me-3">
                                    <i class="fas fa-user-circle fa-2x text-muted"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-text mb-1">Bem-vindo ao sistema!</p>
                                    <small class="text-muted"><?= date('d/m/Y H:i') ?></small>
                                </div>
                            </div>
                            <?php if ($has_anuncio) : ?>
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon me-3">
                                    <i class="fas fa-bullhorn fa-2x text-primary"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-text mb-1">Anúncio criado com sucesso</p>
                                    <small class="text-muted">Status: <?= ucfirst($anuncio_status) ?></small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Mudança de Plano -->
<div class="modal fade modal-theme-login" id="changePlanModal" tabindex="-1" aria-labelledby="changePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="changePlanModalLabel">
                    <i class="fas fa-exchange-alt me-2 text-primary"></i>
                    Escolha seu novo plano
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row g-3">
                    <!-- Plano Gratuito -->
                    <div class="col-lg-4 col-md-12">
                        <div class="plan-card-modal <?= $user_plan === 'free' ? 'current-plan' : '' ?>">
                            <?php if ($user_plan === 'free'): ?>
                                <div class="current-plan-badge">
                                    <i class="fas fa-check-circle me-1"></i>Plano Atual
                                </div>
                            <?php endif; ?>
                            <div class="plan-header">
                                <h5 class="plan-name mb-2">Plano Gratuito</h5>
                                <div class="plan-price mb-3">
                                    <span class="price-value">Grátis</span>
                                </div>
                            </div>
                            <ul class="plan-features-list">
                                <li><i class="fas fa-check text-success"></i> Painel administrativo</li>
                                <li><i class="fas fa-check text-success"></i> Criação de anúncios</li>
                                <li><i class="fas fa-check text-success"></i> 2 fotos na galeria</li>
                                <li><i class="fas fa-check text-success"></i> 1 foto de capa</li>
                                <li><i class="fas fa-times text-danger"></i> Vídeos</li>
                                <li><i class="fas fa-times text-danger"></i> Áudios</li>
                            </ul>
                            <div class="plan-action mt-4">
                                <?php if ($user_plan === 'free'): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-check me-2"></i>Seu Plano Atual
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-primary w-100" onclick="confirmChangePlan('free')">
                                        <i class="fas fa-arrow-down me-2"></i>Mudar para Gratuito
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Plano Básico -->
                    <div class="col-lg-4 col-md-12">
                        <div class="plan-card-modal popular <?= $user_plan === 'basic' ? 'current-plan' : '' ?>">
                            <div class="popular-badge">
                                <i class="fas fa-star me-1"></i>Mais Popular
                            </div>
                            <?php if ($user_plan === 'basic'): ?>
                                <div class="current-plan-badge">
                                    <i class="fas fa-check-circle me-1"></i>Plano Atual
                                </div>
                            <?php endif; ?>
                            <div class="plan-header">
                                <h5 class="plan-name mb-2">Plano Básico</h5>
                                <div class="plan-price mb-3">
                                    <span class="price-currency">R$</span>
                                    <span class="price-value">29,90</span>
                                    <span class="price-period">/mês</span>
                                </div>
                            </div>
                            <ul class="plan-features-list">
                                <li><i class="fas fa-check text-success"></i> Painel administrativo</li>
                                <li><i class="fas fa-check text-success"></i> Criação de anúncios</li>
                                <li><i class="fas fa-check text-success"></i> 20 fotos na galeria</li>
                                <li><i class="fas fa-check text-success"></i> 1 foto de capa</li>
                                <li><i class="fas fa-times text-danger"></i> Vídeos</li>
                                <li><i class="fas fa-times text-danger"></i> Áudios</li>
                            </ul>
                            <div class="plan-action mt-4">
                                <?php if ($user_plan === 'basic'): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-check me-2"></i>Seu Plano Atual
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary w-100" onclick="confirmChangePlan('basic')">
                                        <i class="fas fa-arrow-up me-2"></i>Mudar para Básico
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Plano Premium -->
                    <div class="col-lg-4 col-md-12">
                        <div class="plan-card-modal <?= $user_plan === 'premium' ? 'current-plan' : '' ?>">
                            <?php if ($user_plan === 'premium'): ?>
                                <div class="current-plan-badge">
                                    <i class="fas fa-check-circle me-1"></i>Plano Atual
                                </div>
                            <?php endif; ?>
                            <div class="plan-header">
                                <h5 class="plan-name mb-2">Plano Premium</h5>
                                <div class="plan-price mb-3">
                                    <span class="price-currency">R$</span>
                                    <span class="price-value">49,90</span>
                                    <span class="price-period">/mês</span>
                                </div>
                            </div>
                            <ul class="plan-features-list">
                                <li><i class="fas fa-check text-success"></i> Painel administrativo</li>
                                <li><i class="fas fa-check text-success"></i> Criação de anúncios</li>
                                <li><i class="fas fa-check text-success"></i> 20 fotos na galeria</li>
                                <li><i class="fas fa-check text-success"></i> 1 foto de capa</li>
                                <li><i class="fas fa-check text-success"></i> 3 vídeos</li>
                                <li><i class="fas fa-check text-success"></i> 3 áudios</li>
                            </ul>
                            <div class="plan-action mt-4">
                                <?php if ($user_plan === 'premium'): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-check me-2"></i>Seu Plano Atual
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success w-100" onclick="confirmChangePlan('premium')">
                                        <i class="fas fa-crown me-2"></i>Mudar para Premium
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inline CSS removido: estilos do changePlanModal agora estão em assets/css/plan-modal.css -->

<script>
let selectedPlanToChange = null;

// Função para lidar com clique no botão confirmar
function handleConfirmClick() {
    if (!selectedPlanToChange) {
        console.error('Nenhum plano selecionado');
        return;
    }
    
    console.log('Confirmando mudança para plano:', selectedPlanToChange);
    
    // Fechar modal de confirmação
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmChangePlanModal'));
    if (confirmModal) {
        confirmModal.hide();
    }
    
    // Aguardar fechamento do modal e processar mudança
    setTimeout(() => {
        processPlanChange(selectedPlanToChange);
    }, 300);
}

function confirmChangePlan(planType) {
    selectedPlanToChange = planType;
    
    const planNames = {
        'free': 'Gratuito',
        'basic': 'Básico',
        'premium': 'Premium'
    };
    
    const planDescriptions = {
        'free': 'Você perderá os benefícios do plano pago.',
        'basic': 'Você terá acesso a 20 fotos na galeria e outros recursos.',
        'premium': 'Você terá acesso completo a todos os recursos, incluindo vídeos e áudios.'
    };
    
    const planName = planNames[planType] || planType;
    const planDescription = planDescriptions[planType] || '';
    
    // Atualizar conteúdo do modal de confirmação
    document.getElementById('confirmPlanName').textContent = planName;
    document.getElementById('confirmPlanDescription').textContent = planDescription;
    
    // Fechar modal de planos e abrir modal de confirmação
    const changePlanModal = bootstrap.Modal.getInstance(document.getElementById('changePlanModal'));
    if (changePlanModal) {
        changePlanModal.hide();
    }
    
    setTimeout(() => {
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmChangePlanModal'));
        confirmModal.show();
    }, 300);
}

// Evento do botão de confirmação - usar addEventListener após DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmChangePlanBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (!selectedPlanToChange) return;
            
            // Fechar modal de confirmação
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmChangePlanModal'));
            if (confirmModal) {
                confirmModal.hide();
            }
            
            // Aguardar fechamento do modal e processar mudança
            setTimeout(() => {
                processPlanChange(selectedPlanToChange);
            }, 300);
        });
    }
});

function processPlanChange(planType) {
    // Reabrir modal de planos com loading
    const changePlanModal = new bootstrap.Modal(document.getElementById('changePlanModal'));
    changePlanModal.show();
    
    const modal = document.getElementById('changePlanModal');
    const modalBody = modal.querySelector('.modal-body');
    const originalContent = modalBody.innerHTML;
    
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <h5 class="text-primary mb-2">Processando mudança de plano...</h5>
            <p class="text-muted">Por favor, aguarde</p>
        </div>
    `;

    fetch('<?= URLADM ?>planos/changePlan', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ plan: planType })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensagem de sucesso
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="success-animation mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h4 class="text-success mb-3">Sucesso!</h4>
                    <p class="lead mb-2">${data.message}</p>
                    <p class="text-muted">Redirecionando em instantes...</p>
                    <div class="spinner-border spinner-border-sm text-primary mt-3" role="status">
                        <span class="visually-hidden">Redirecionando...</span>
                    </div>
                </div>
            `;
            
            // Aguardar 2 segundos e redirecionar
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.action !== 'stay') {
                    window.location.reload();
                } else {
                    changePlanModal.hide();
                    modalBody.innerHTML = originalContent;
                }
            }, 2000);
        } else {
            // Mostrar mensagem de erro
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="error-animation mb-4">
                        <i class="fas fa-exclamation-circle fa-5x text-danger"></i>
                    </div>
                    <h4 class="text-danger mb-3">Ops! Algo deu errado</h4>
                    <p class="lead mb-4">${data.message}</p>
                    <button class="btn btn-primary btn-lg" onclick="location.reload()">
                        <i class="fas fa-redo me-2"></i>Tentar Novamente
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="error-animation mb-4">
                    <i class="fas fa-exclamation-triangle fa-5x text-warning"></i>
                </div>
                <h4 class="text-warning mb-3">Erro de Conexão</h4>
                <p class="lead mb-4">Não foi possível processar sua solicitação. Verifique sua conexão e tente novamente.</p>
                <button class="btn btn-primary btn-lg" onclick="location.reload()">
                    <i class="fas fa-redo me-2"></i>Tentar Novamente
                </button>
            </div>
        `;
    });
}

// Adicionar animações CSS
const style = document.createElement('style');
style.textContent = `
    .success-animation i {
        animation: successPulse 0.6s ease-in-out;
    }
    
    .error-animation i {
        animation: errorShake 0.6s ease-in-out;
    }
    
    @keyframes successPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    @keyframes errorShake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }
    
    #confirmChangePlanModal .confirm-icon {
        animation: iconBounce 1s ease-in-out infinite;
    }
    
    @keyframes iconBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
`;
document.head.appendChild(style);
</script>

<!-- Modal de Confirmação de Mudança de Plano -->
<div class="modal fade modal-theme-login" id="confirmChangePlanModal" tabindex="-1" aria-labelledby="confirmChangePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center" id="confirmChangePlanModalLabel">
                    <i class="fas fa-question-circle text-warning me-2 fa-lg"></i>
                    <span>Confirmar Mudança de Plano</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <div class="confirm-icon mb-3">
                        <i class="fas fa-exchange-alt fa-3x text-primary"></i>
                    </div>
                    <h6 class="mb-3" id="confirmPlanMessage">Tem certeza que deseja mudar para o plano <strong id="confirmPlanName"></strong>?</h6>
                    <p class="text-muted small mb-0" id="confirmPlanDescription"></p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary px-4" id="confirmChangePlanBtn" onclick="handleConfirmClick()">
                    <i class="fas fa-check me-2"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Filtros -->
<div class="modal fade modal-feedback-beautiful" id="filtersModal" tabindex="-1" aria-labelledby="filtersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-beautiful">
            <div class="modal-header modal-header-beautiful">
                <h5 class="modal-title" id="filtersModalLabel">
                    <i class="fas fa-filter me-2"></i>
                    Filtros de Anúncios
                </h5>
            </div>
            <div class="modal-body modal-body-beautiful">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success filter-option modal-btn-beautiful" data-filter-status="active">
                        <i class="fas fa-check-circle me-2"></i>
                        Ativos
                    </button>
                    <button class="btn btn-outline-warning filter-option modal-btn-beautiful" data-filter-status="pending">
                        <i class="fas fa-clock me-2"></i>
                        Pendentes
                    </button>
                    <button class="btn btn-outline-danger filter-option modal-btn-beautiful" data-filter-status="rejected">
                        <i class="fas fa-times-circle me-2"></i>
                        Rejeitados
                    </button>
                    <button class="btn btn-outline-info filter-option modal-btn-beautiful" data-filter-status="pausado">
                        <i class="fas fa-pause-circle me-2"></i>
                        Pausados
                    </button>
                    <button class="btn btn-outline-primary filter-option modal-btn-beautiful" data-filter-status="all">
                        <i class="fas fa-filter me-2"></i>
                        Todos os Status
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS da dashboard moderna carregado externamente -->