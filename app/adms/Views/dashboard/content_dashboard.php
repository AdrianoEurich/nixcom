<?php
// app/adms/Views/dashboard/content_dashboard.php
// Conteúdo principal do Dashboard. Esta view será carregada dentro do layout/main.php.

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// As variáveis como $dashboard_stats, $recent_activity (se passadas pelo controlador e extraídas)
// podem ser usadas aqui para preencher os dados dinamicamente.
// Exemplo:
// $total_visits = $dashboard_stats['total_visits'] ?? 'N/A';
// $revenue = $dashboard_stats['revenue'] ?? 'N/A';

// Certifique-se de que $listAnuncios está sendo passado pelo controlador Dashboard.php
// Se não estiver, você verá um erro de variável indefinida.
// Para fins de demonstração, vou criar um array de exemplo se $listAnuncios não estiver definido.
if (!isset($listAnuncios)) {
    $listAnuncios = [
        [
            'id' => 1,
            'user_name' => 'Nicolas de Cristo Eurich',
            'user_email' => 'nicolas@example.com',
            'category' => 'Imóveis',
            'status' => 'active',
            'visits' => '1,245',
            'created_at' => '15/12/2023'
        ],
        [
            'id' => 2,
            'user_name' => 'Adriano de Cristo Eurich',
            'user_email' => 'adriano@example.com',
            'category' => 'Informática',
            'status' => 'pending',
            'visits' => '4,245',
            'created_at' => '15/12/2021'
        ],
        [
            'id' => 3,
            'user_name' => 'Marcia Cristina',
            'user_email' => 'marcia@example.com',
            'category' => 'Automóvel',
            'status' => 'rejected', // Alterado para 'rejected' para testar o botão
            'visits' => '2,245',
            'created_at' => '15/12/2021'
        ],
        [
            'id' => 4,
            'user_name' => 'Ana Cristina',
            'user_email' => 'ana@example.com',
            'category' => 'Telefonia',
            'status' => 'paused', // Alterado para 'paused' para testar o botão
            'visits' => '8,245',
            'created_at' => '15/12/2021'
        ]
    ];
}

// Para a paginação, você precisará passar $pagination_data (ex: current_page, total_pages, search_term) do controlador
$current_page = $pagination_data['current_page'] ?? 1;
$total_pages = $pagination_data['total_pages'] ?? 1;
$search_term = $pagination_data['search_term'] ?? '';

?>
<div class="content p-3">
    <h1 class="h3 mb-4">Dashboard</h1>

    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Visitas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">12,345</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
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
                                Pagamentos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">3,210</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
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
                                Aprovação</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">87%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Receita</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">R$48,245</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4 g-3">
        <div class="col-xl-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Visitas ao Site</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="visitsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Origem do Tráfego</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="trafficChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="me-2">
                            <i class="fas fa-circle text-primary"></i> Direto
                        </span>
                        <span class="me-2">
                            <i class="fas fa-circle text-success"></i> Social
                        </span>
                        <span class="me-2">
                            <i class="fas fa-circle text-info"></i> Referência
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mt-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Anúncios Recentes</h6>
            
            <!-- Formulário de busca e filtros -->
            <form class="search-form d-flex ms-auto" id="searchForm"> <!-- Adicionado ID searchForm -->
                <div class="input-group search-group">
                    <input type="text" class="form-control search-input" id="searchInput" name="search" placeholder="Pesquisar anúncios..." autofocus value="<?= htmlspecialchars($search_term); ?>"> <!-- Adicionado ID searchInput -->
                    <button class="btn btn-primary search-btn" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary filter-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sliders-h"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">Filtrar por:</h6>
                            </li>
                            <li><a class="dropdown-item" href="#" data-filter-status="active"><i class="fas fa-check-circle text-success"></i> Ativos</a></li>
                            <li><a class="dropdown-item" href="#" data-filter-status="pending"><i class="fas fa-clock text-warning"></i> Pendentes</a></li>
                            <li><a class="dropdown-item" href="#" data-filter-status="rejected"><i class="fas fa-times-circle text-danger"></i> Rejeitados</a></li>
                            <li><a class="dropdown-item" href="#" data-filter-status="paused"><i class="fas fa-pause-circle text-info"></i> Pausados</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-filter-status="all"><i class="fas fa-filter"></i> Todos os Status</a></li>
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
                            <th>ID</th> <!-- Adicionado ID do Anúncio -->
                            <th>Anunciante</th> <!-- Novo: Nome do Anunciante -->
                            <th class="d-none d-md-table-cell">Email Anunciante</th> <!-- Novo: Email do Anunciante -->
                            <th class="d-none d-md-table-cell">Categoria</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Visitas</th>
                            <th class="d-none d-md-table-cell">Data Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="latestAnunciosTableBody"> <!-- Adicionado ID latestAnunciosTableBody -->
                        <?php if (!empty($listAnuncios)): ?>
                            <?php foreach ($listAnuncios as $anuncio): ?>
                                <tr>
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
                                            case 'paused':
                                                $status_class = 'text-bg-info';
                                                $status_text = 'Pausado';
                                                break;
                                            default:
                                                $status_class = 'text-bg-secondary';
                                                $status_text = 'Desconhecido';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class; ?>"><?= $status_text; ?></span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($anuncio['visits']); ?></td>
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
                                            <?php elseif ($anuncio['status'] === 'paused'): ?>
                                                <!-- Botão Ativar (visível apenas se status for 'paused') -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-info activate-anuncio-btn" 
                                                        data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                        title="Ativar Anúncio">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Botão Editar (visível para todos os status exceto rejected/deleted, se aplicável) -->
                                            <?php if ($anuncio['status'] !== 'rejected'): ?>
                                                <a href="<?= URLADM; ?>anuncio/editarAnuncio?id=<?= htmlspecialchars($anuncio['id']); ?>" 
                                                   class="btn btn-sm btn-secondary edit-anuncio-btn" 
                                                   data-id="<?= htmlspecialchars($anuncio['id']); ?>" 
                                                   data-spa="true"
                                                   title="Editar Anúncio">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Botão Excluir (visível para todos os status) -->
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
                <ul class="pagination justify-content-center" id="paginationControls"> <!-- Adicionado ID paginationControls -->
                    <?php if ($total_pages > 1): ?>
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="<?= $current_page - 1; ?>" data-search="<?= htmlspecialchars($search_term); ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i === $current_page) ? 'active' : ''; ?>">
                                <a class="page-link" href="#" data-page="<?= $i; ?>" data-search="<?= htmlspecialchars($search_term); ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="<?= $current_page + 1; ?>" data-search="<?= htmlspecialchars($search_term); ?>" aria-label="Próximo">
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
