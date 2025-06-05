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
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Anúncios Recentes</h6>

            <form class="search-form mt-3 mt-md-0">
                <div class="input-group search-group">
                    <input type="text" class="form-control search-input" placeholder="Pesquisar anúncios..." autofocus>
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
                            <li><a class="dropdown-item" href="#"><i class="fas fa-check-circle text-success"></i> Ativos</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-clock text-warning"></i> Pendentes</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-times-circle text-danger"></i> Excluídos</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-pause-circle text-info"></i> Parados</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-filter"></i> Mais filtros...</a></li>
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
                            <th>Nome</th>
                            <th class="d-none d-md-table-cell">Categoria</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Visitas</th>
                            <th class="d-none d-md-table-cell">Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Nicolas de Cristo Eurich</td>
                            <td class="d-none d-md-table-cell">Imóveis</td>
                            <td><span class="badge text-bg-success">Ativo</span></td>
                            <td class="d-none d-md-table-cell">1,245</td>
                            <td class="d-none d-md-table-cell">15/12/2023</td>
                            <td>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Adriano de Cristo Eurich</td>
                            <td class="d-none d-md-table-cell">Informática</td>
                            <td><span class="badge text-bg-warning">Pendente</span></td>
                            <td class="d-none d-md-table-cell">4,245</td>
                            <td class="d-none d-md-table-cell">15/12/2021</td>
                            <td>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Marcia Cristina</td>
                            <td class="d-none d-md-table-cell">Automóvel</td>
                            <td><span class="badge text-bg-danger">Excluído</span></td>
                            <td class="d-none d-md-table-cell">2,245</td>
                            <td class="d-none d-md-table-cell">15/12/2021</td>
                            <td>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Ana Cristina</td>
                            <td class="d-none d-md-table-cell">Telefonia</td>
                            <td><span class="badge text-bg-info">Parado</span></td>
                            <td class="d-none d-md-table-cell">8,245</td>
                            <td class="d-none d-md-table-cell">15/12/2021</td>
                            <td>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>