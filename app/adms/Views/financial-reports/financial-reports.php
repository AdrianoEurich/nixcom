<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-chart-line me-2 text-primary"></i>
                    Relatórios Financeiros
                </h1>
                <p class="text-muted">Acompanhe o desempenho financeiro da plataforma</p>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="dateFrom" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="dateFrom" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="dateTo" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="dateTo" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-primary" id="applyFilters">
                                    <i class="fas fa-search me-2"></i>Filtrar
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-success" id="exportCsv">
                                    <i class="fas fa-download me-2"></i>Exportar CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title" id="totalPayments">0</h4>
                                    <p class="card-text">Total de Pagamentos</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-credit-card fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title" id="approvedPayments">0</h4>
                                    <p class="card-text">Pagamentos Aprovados</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title" id="pendingPayments">0</h4>
                                    <p class="card-text">Pagamentos Pendentes</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title" id="totalRevenue">R$ 0,00</h4>
                                    <p class="card-text">Receita Total</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Receita por Plano</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="planChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Receita por Período</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="periodChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Logs -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Logs de Pagamento</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário</th>
                                    <th>Payment ID</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th>Plano</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📊 Relatórios Financeiros carregados');
    
    // Elementos
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const applyFiltersBtn = document.getElementById('applyFilters');
    const exportCsvBtn = document.getElementById('exportCsv');
    
    // Carregar dados iniciais
    loadReports();
    
    // Event listeners
    applyFiltersBtn.addEventListener('click', loadReports);
    exportCsvBtn.addEventListener('click', exportToCsv);
    
    function loadReports() {
        console.log('📊 Carregando relatórios...');
        
        const params = new URLSearchParams({
            date_from: dateFrom.value,
            date_to: dateTo.value
        });
        
        fetch(`${window.URLADM}financial-reports/getReports?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStats(data.data.stats);
                    updateCharts(data.data.plan_stats, data.data.period_stats);
                    updateLogsTable(data.data.logs);
                } else {
                    console.error('Erro ao carregar relatórios:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
            });
    }
    
    function updateStats(stats) {
        document.getElementById('totalPayments').textContent = stats.total_payments || 0;
        document.getElementById('approvedPayments').textContent = stats.approved_payments || 0;
        document.getElementById('pendingPayments').textContent = stats.pending_payments || 0;
        document.getElementById('totalRevenue').textContent = formatCurrency(stats.total_revenue || 0);
    }
    
    function updateCharts(planStats, periodStats) {
        // Gráfico de receita por plano
        const planCtx = document.getElementById('planChart').getContext('2d');
        new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: planStats.map(stat => stat.plan_type),
                datasets: [{
                    data: planStats.map(stat => stat.total_revenue),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Gráfico de receita por período
        const periodCtx = document.getElementById('periodChart').getContext('2d');
        new Chart(periodCtx, {
            type: 'line',
            data: {
                labels: periodStats.map(stat => stat.date),
                datasets: [{
                    label: 'Receita',
                    data: periodStats.map(stat => stat.total_revenue),
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    function updateLogsTable(logs) {
        const tbody = document.getElementById('logsTableBody');
        
        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">Nenhum log encontrado</td></tr>';
            return;
        }
        
        tbody.innerHTML = logs.map(log => `
            <tr>
                <td>${log.id}</td>
                <td>${log.user_id}</td>
                <td>${log.payment_id || '-'}</td>
                <td><span class="badge bg-${getStatusColor(log.status)}">${log.status}</span></td>
                <td>${formatCurrency(log.amount || 0)}</td>
                <td>${log.plan_type || '-'}</td>
                <td>${formatDate(log.created_at)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(${log.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    function exportToCsv() {
        const params = new URLSearchParams({
            date_from: dateFrom.value,
            date_to: dateTo.value
        });
        
        window.open(`${window.URLADM}financial-reports/exportCsv?${params}`, '_blank');
    }
    
    function getStatusColor(status) {
        const colors = {
            'approved': 'success',
            'pending': 'warning',
            'rejected': 'danger',
            'cancelled': 'secondary'
        };
        return colors[status] || 'secondary';
    }
    
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }
    
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('pt-BR');
    }
    
    window.viewLogDetails = function(logId) {
        // Implementar modal de detalhes do log
        console.log('Visualizar detalhes do log:', logId);
    };
});
</script>

