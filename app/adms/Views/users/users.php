<?php
// app/adms/Views/users/users.php
// Página de gerenciamento de usuários para administradores

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Extrai as variáveis passadas pelo controlador
extract($this->data);

$page = $page ?? 1;
$search_term = $search_term ?? '';
$filter_status = $filter_status ?? 'all';
$filter_plan = $filter_plan ?? 'all';
$limit = $limit ?? 5;
?>

<div class="content pt-0 px-0 pb-3" id="adminUsersContent" data-page-type="admin-users">

    <!-- Estatísticas Rápidas -->
    <div class="admin-users-stats mb-4">
        <div class="row g-3">
            <div class="col-xl-3 col-md-6">
                <div class="admin-stats-card primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Usuários</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalUsersCount">
                                    -
                                </div>
                                <div class="growth-indicator" id="totalUsersGrowthWrap">
                                    <i class="fas fa-arrow-up"></i>
                                    <span id="totalUsersGrowth">—</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                    Usuários Ativos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeUsersCount">
                                    -
                                </div>
                                <div class="growth-indicator" id="activeUsersGrowthWrap">
                                    <i class="fas fa-arrow-up"></i>
                                    <span id="activeUsersGrowth">—</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                    Planos Pagos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="paidPlansCount">
                                    -
                                </div>
                                <div class="growth-indicator" id="paidPlansGrowthWrap">
                                    <i class="fas fa-arrow-up"></i>
                                    <span id="paidPlansGrowth">—</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-crown fa-2x text-gray-300"></i>
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
                                    Pagamentos Aprovados</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="approvedPaymentsCount">
                                    -
                                </div>
                                <div class="growth-indicator" id="approvedPaymentsGrowthWrap">
                                    <i class="fas fa-arrow-up"></i>
                                    <span id="approvedPaymentsGrowth">—</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros e Pesquisa -->
    <div class="admin-users-filters mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="searchUsers" class="form-label">
                            <i class="fas fa-search me-2 text-primary"></i>Pesquisar Usuários
                        </label>
                        <input type="text" 
                               class="form-control admin-filter-control w-100" 
                               id="searchUsers" 
                               placeholder="Nome ou e-mail..."
                               value="<?= htmlspecialchars($search_term) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="filterPlan" class="form-label">
                            <i class="fas fa-crown me-2 text-warning"></i>Plano
                        </label>
                        <select class="form-select admin-filter-control" id="filterPlan">
                            <option value="all" <?= $filter_plan === 'all' ? 'selected' : '' ?>>Todos os Planos</option>
                            <option value="free" <?= $filter_plan === 'free' ? 'selected' : '' ?>>Gratuito</option>
                            <option value="basic" <?= $filter_plan === 'basic' ? 'selected' : '' ?>>Básico</option>
                            <option value="premium" <?= $filter_plan === 'premium' ? 'selected' : '' ?>>Premium</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button class="btn btn-primary admin-filter-control" id="applyFiltersBtn">
                                <i class="fas fa-filter me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Usuários -->
    <div class="admin-users-table">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-users-table">
                        <thead class="table-header">
                            <tr>
                                <th class="border-0 th-id">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-hashtag me-2 text-muted"></i>
                                        ID
                                    </div>
                                </th>
                                <th class="border-0 th-user">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user me-2 text-muted"></i>
                                        Usuário
                                    </div>
                                </th>
                                <th class="border-0 th-plan">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-crown me-2 text-muted"></i>
                                        Plano
                                    </div>
                                </th>
                                <th class="border-0 th-payment">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-credit-card me-2 text-muted"></i>
                                        Pagamento
                                    </div>
                                </th>
                                <th class="border-0 text-center th-actions">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-cogs me-2 text-muted"></i>
                                        Ações
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Conteúdo carregado via AJAX -->
                            <tr id="loadingRow">
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <h6 class="text-muted mb-1">Carregando usuários...</h6>
                                        <p class="text-muted small mb-0">Aguarde enquanto buscamos os dados</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Paginação -->
    <nav aria-label="Paginação de Usuários" class="mt-4">
        <ul class="pagination justify-content-center" id="paginationContainer">
            <!-- Paginação carregada via AJAX -->
        </ul>
    </nav>
</div>

<!-- Modal de Detalhes do Usuário -->
<div class="modal fade modal-feedback-beautiful" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userDetailsModalLabel">
                    <i class="fas fa-user me-2"></i>Detalhes do Usuário
                </h5>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Conteúdo carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edição Rápida -->
<div class="modal fade modal-feedback-beautiful" id="quickEditModal" tabindex="-1" aria-labelledby="quickEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title d-flex align-items-center" id="quickEditModalLabel">
                    <i class="fas fa-user-edit text-white me-2"></i>
                    <div>
                        <div class="fw-bold">Edição Rápida</div>
                    </div>
                </h5>
            </div>
            <div class="modal-body p-4">
                <form id="quickEditForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    
                    

                    <!-- Campos de Edição (duas colunas) -->
                    <div class="row g-4">
                        <!-- Coluna Esquerda: Informações da Conta -->
                        <div class="col-md-6 text-start">
                            <div class="mb-3">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-primary me-2"></i>
                                    Informações da Conta
                                </h6>
                                <div class="mb-3 text-start">
                                    <label for="editUserStatus" class="form-label fw-semibold">
                                        <i class="fas fa-toggle-on text-success me-2"></i>Status da Conta
                                    </label>
                                    <select class="form-select form-select-lg" id="editUserStatus" name="status">
                                        <option value="ativo">Ativo</option>
                                        <option value="inativo">Inativo</option>
                                        <option value="suspenso">Suspenso</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle text-info me-1"></i>
                                        Controla o acesso do usuário ao sistema
                                    </div>
                                </div>
                                <div class="mb-3 text-start">
                                    <label for="editUserPlan" class="form-label fw-semibold">
                                        <i class="fas fa-crown text-warning me-2"></i>Plano de Assinatura
                                    </label>
                                    <select class="form-select form-select-lg" id="editUserPlan" name="plan_type">
                                        <option value="free">Gratuito</option>
                                        <option value="basic">Básico</option>
                                        <option value="premium">Premium</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle text-info me-1"></i>
                                        Define os recursos disponíveis para o usuário
                                    </div>
                                </div>
                                <div class="mb-0 text-start">
                                    <label for="editPaymentStatus" class="form-label fw-semibold">
                                        <i class="fas fa-credit-card text-primary me-2"></i>Status de Pagamento
                                    </label>
                                    <select class="form-select form-select-lg" id="editPaymentStatus" name="payment_status">
                                        <option value="pending">Pendente</option>
                                        <option value="approved">Aprovado</option>
                                        <option value="rejected">Rejeitado</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle text-info me-1"></i>
                                        Status do último pagamento processado
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna Direita: Informações do Usuário -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                                    <i class="fas fa-id-card text-primary me-2"></i>
                                    Informações do Usuário
                                </h6>
                                <div class="mb-3 text-start">
                                    <label for="editFullName" class="form-label fw-semibold">
                                        <i class="fas fa-user text-primary me-2"></i>Nome
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="editFullName" name="nome" placeholder="Digite o nome completo" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label for="editEmail" class="form-label fw-semibold">
                                        <i class="fas fa-envelope text-info me-2"></i>E-mail
                                    </label>
                                    <input type="email" class="form-control form-control-lg" id="editEmail" name="email" placeholder="usuario@exemplo.com" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label for="editPhone" class="form-label fw-semibold">
                                        <i class="fas fa-phone text-success me-2"></i>Telefone
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="editPhone" name="telefone" placeholder="(00) 00000-0000">
                                </div>
                                <div class="mb-0 text-start">
                                    <label for="editCpf" class="form-label fw-semibold">
                                        <i class="fas fa-id-card text-warning me-2"></i>CPF
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="editCpf" name="cpf" placeholder="000.000.000-00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alertas de Mudança -->
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 mt-4" id="changeAlert" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-info me-2"></i>
                            <div>
                                <strong>Alterações Detectadas:</strong>
                                <div id="changeDetails" class="mt-1"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0 p-4">
                <div class="d-flex justify-content-between w-100">
                    <button type="button" class="btn btn-dark btn-lg px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-lg px-4" id="saveQuickEditBtn">
                        <i class="fas fa-save me-2"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS específico para admin de usuários -->
<style>
    #adminUsersContent .admin-users-filters .admin-filter-control {
        height: 48px;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 12px;
    }
    #adminUsersContent .admin-users-filters .form-control.admin-filter-control,
    #adminUsersContent .admin-users-filters .form-select.admin-filter-control {
        padding: 0.5rem 1rem;
    }
    #adminUsersContent .admin-users-filters .btn.admin-filter-control {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding-top: 0;
        padding-bottom: 0;
    }
#quickEditModal.modal-feedback-beautiful .modal-dialog,
#quickEditModal.modal-feedback-beautiful .modal-content { overflow: visible !important; }
#quickEditModal .modal-content { max-height: 90vh; display: flex; flex-direction: column; }
#quickEditModal .modal-body { flex: 1 1 auto; overflow-y: auto; }
@media (max-width: 576px) {
  #quickEditModal { padding-top: 0 !important; }
  #quickEditModal .modal-dialog { margin: 0 !important; width: 100%; }
  #quickEditModal .modal-content { height: 100vh; max-height: 100vh; }
  #quickEditModal .modal-body { max-height: none; height: auto; overflow-y: auto; }
}
#quickEditModal .modal-content {
    border-radius: 20px;
    overflow: hidden;
}

#quickEditModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem 2rem;
}

#quickEditModal .modal-header .modal-title {
    font-size: 1.25rem;
}

#quickEditModal .modal-body {
    background: #f8f9fa;
}

#quickEditModal .form-select-lg {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    font-size: 1rem;
    padding: 0.75rem 1rem;
}

#quickEditModal .form-select-lg:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    transform: translateY(-1px);
}

#quickEditModal .form-label {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

#quickEditModal .form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

#quickEditModal .bg-light {
    background-color: #f8f9fa !important;
    border: 1px solid #e9ecef;
}

#quickEditModal .alert {
    border-radius: 12px;
    border: none;
    font-size: 0.9rem;
}

#quickEditModal .btn-lg {
    border-radius: 12px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

#quickEditModal .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

#quickEditModal .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

#quickEditModal .btn-outline-secondary:hover {
    transform: translateY(-1px);
}

/* Animações suaves */
#quickEditModal .modal-content {
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Efeitos hover nos campos */
#quickEditModal .form-select:hover {
    border-color: #ced4da;
    transform: translateY(-1px);
}

/* Cards com exatamente as mesmas cores e estilos da dashboard */
#adminUsersContent .admin-stats-card {
    background: var(--card-bg);
    border-radius: 16px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    height: 100%;
}

#adminUsersContent .admin-stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Barra colorida no topo */
#adminUsersContent .admin-stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    transition: all 0.3s ease;
}

#adminUsersContent .admin-stats-card.primary::before {
    background: linear-gradient(90deg, #4e73df, #3b82f6);
}

#adminUsersContent .admin-stats-card.success::before {
    background: linear-gradient(90deg, #1cc88a, #34d399);
}

#adminUsersContent .admin-stats-card.warning::before {
    background: linear-gradient(90deg, #f6c23e, #fbbf24);
}

#adminUsersContent .admin-stats-card.info::before {
    background: linear-gradient(90deg, #36b9cc, #22d3ee);
}

#adminUsersContent .admin-stats-card:hover::before {
    height: 6px;
}

/* Conteúdo dos cards */
#adminUsersContent .admin-stats-card .card-body {
    padding: 1.5rem;
    position: relative;
    z-index: 1;
}

#adminUsersContent .admin-stats-card .text-xs {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

#adminUsersContent .admin-stats-card.primary .text-xs {
    color: #4e73df;
}

#adminUsersContent .admin-stats-card.success .text-xs {
    color: #1cc88a;
}

#adminUsersContent .admin-stats-card.warning .text-xs {
    color: #f6c23e;
}

#adminUsersContent .admin-stats-card.info .text-xs {
    color: #36b9cc;
}

#adminUsersContent .admin-stats-card .h5 {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    line-height: 1.2;
}

#adminUsersContent .admin-stats-card .col-auto {
    display: flex;
    align-items: center;
    justify-content: center;
}

#adminUsersContent .admin-stats-card .fa-2x {
    font-size: 2.5rem;
    opacity: 0.3;
    transition: all 0.3s ease;
}

#adminUsersContent .admin-stats-card.primary .fa-2x {
    color: #4e73df;
}

#adminUsersContent .admin-stats-card.success .fa-2x {
    color: #1cc88a;
}

#adminUsersContent .admin-stats-card.warning .fa-2x {
    color: #f6c23e;
}

#adminUsersContent .admin-stats-card.info .fa-2x {
    color: #36b9cc;
}

#adminUsersContent .admin-stats-card:hover .fa-2x {
    opacity: 0.6;
    transform: scale(1.1);
}

/* =============================================
   ESTILOS DA TABELA PROFISSIONAL
   ============================================= */


/* Cabeçalho da tabela */
#adminUsersContent .table-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border-bottom: 2px solid #dee2e6;
}

#adminUsersContent .table-header th {
    border: none;
    font-weight: 700;
    color: white;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1.25rem 1rem;
    position: relative;
}

#adminUsersContent .table-header th::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #4e73df, #224abe);
    opacity: 0;
    transition: opacity 0.3s ease;
}

#adminUsersContent .table-header th:hover::after {
    opacity: 1;
}

#adminUsersContent .table-header th i {
    font-size: 0.8rem;
    opacity: 0.9;
    color: white;
}

/* Corpo da tabela */
#adminUsersContent .admin-users-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f3f4;
}

#adminUsersContent .admin-users-table tbody tr:hover {
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(78, 115, 223, 0.1);
}

#adminUsersContent .admin-users-table tbody td {
    padding: 1.25rem 1rem;
    vertical-align: middle;
    border: none;
    font-size: 0.95rem;
    color: #495057;
}

/* Estilos para badges de status */
#adminUsersContent .badge-plan-free {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-plan-basic {
    background: linear-gradient(135deg, #4e73df, #224abe);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-plan-premium {
    background: linear-gradient(135deg, #f6c23e, #dda20a);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-status-ativo {
    background: linear-gradient(135deg, #1cc88a, #17a673);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-status-inativo {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-status-suspenso {
    background: linear-gradient(135deg, #e74a3b, #c0392b);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-payment-approved {
    background: linear-gradient(135deg, #1cc88a, #17a673);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-payment-pending {
    background: linear-gradient(135deg, #f6c23e, #dda20a);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

#adminUsersContent .badge-payment-rejected {
    background: linear-gradient(135deg, #e74a3b, #c0392b);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
}

/* Botões de ação */
#adminUsersContent .btn-action {
    padding: 0.5rem;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: none;
    margin: 0 2px;
}

#adminUsersContent .btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

#adminUsersContent .btn-view {
    background: linear-gradient(135deg, #4e73df, #224abe);
    color: white;
}

#adminUsersContent .btn-edit {
    background: linear-gradient(135deg, #f6c23e, #dda20a);
    color: white;
}

#adminUsersContent .btn-delete {
    background: linear-gradient(135deg, #e74a3b, #c0392b);
    color: white;
}


/* Informações do usuário */
#adminUsersContent .user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

#adminUsersContent .user-name {
    font-weight: 700;
    color: #2c3e50;
    font-size: 1rem;
    margin: 0;
}

#adminUsersContent .user-email {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
    margin: 0;
}

/* Telefone (<=576px): mostrar apenas Usuário e Ações */
@media (max-width: 576px) {
    /* Esconder cabeçalhos: ID, Plano e Pagamento */
    #adminUsersContent .admin-users-table thead .th-id,
    #adminUsersContent .admin-users-table thead .th-plan,
    #adminUsersContent .admin-users-table thead .th-payment {
        display: none !important;
    }
    /* Esconder células: ID, Plano e Pagamento */
    #adminUsersContent .admin-users-table tbody .col-id,
    #adminUsersContent .admin-users-table tbody .col-plan,
    #adminUsersContent .admin-users-table tbody .col-payment {
        display: none !important;
    }
    /* Fallback por posição: ocultar 1ª (ID), 3ª (Plano) e 4ª (Pagamento) */
    #adminUsersContent .admin-users-table thead th:nth-child(1),
    #adminUsersContent .admin-users-table thead th:nth-child(3),
    #adminUsersContent .admin-users-table thead th:nth-child(4),
    #adminUsersContent .admin-users-table tbody td:nth-child(1),
    #adminUsersContent .admin-users-table tbody td:nth-child(3),
    #adminUsersContent .admin-users-table tbody td:nth-child(4) {
        display: none !important;
    }
    /* Compactar botões e evitar quebra na coluna de ações */
    #adminUsersContent .admin-users-table tbody .col-actions {
        white-space: nowrap;
        width: 1%;
    }
    #adminUsersContent .admin-users-table tbody .col-actions .btn-action {
        width: 34px;
        height: 34px;
        margin: 0 1px;
    }
}

/* Também simplificar em tablets: até 768px */
@media (max-width: 768px) {
    #adminUsersContent .admin-users-table thead .th-id,
    #adminUsersContent .admin-users-table thead .th-plan {
        display: none !important;
    }
    #adminUsersContent .admin-users-table tbody .col-id,
    #adminUsersContent .admin-users-table tbody .col-plan {
        display: none !important;
    }
    /* Fallback por posição: oculta 1ª (ID) e 3ª (Plano) mesmo sem classes */
    #adminUsersContent .admin-users-table thead th:nth-child(1),
    #adminUsersContent .admin-users-table thead th:nth-child(3),
    #adminUsersContent .admin-users-table tbody td:nth-child(1),
    #adminUsersContent .admin-users-table tbody td:nth-child(3) {
        display: none !important;
    }
}

.badge-plan-premium {
    background-color: #fd7e14;
    color: #000 !important;
}

.badge-plan-basic {
    background-color: #cfe2ff !important; /* light blue */
    color: #000 !important;
}

.badge-plan-free {
    background-color: #e9ecef !important; /* light gray */
    color: #000 !important;
}

.badge-status-ativo {
    background-color: #198754;
}

/* Garantir contraste dos badges de plano */
/* (mantido por segurança de especificidade) */
.badge-plan-free { color: #000 !important; }
.badge-plan-basic { color: #000 !important; }

.badge-status-inativo {
    background-color: #6c757d;
}

.badge-status-suspenso {
    background-color: #dc3545;
}

.badge-payment-approved {
    background-color: #198754;
}

.badge-payment-pending {
    background-color: #ffc107;
    color: #000;
}

.badge-payment-rejected {
    background-color: #dc3545;
}

/* ===== User Details Modal refinements ===== */
#userDetailsModal { padding-top: 5px !important; }
#userDetailsModal .modal-dialog { max-width: 900px; }
#userDetailsModal.modal-feedback-beautiful .modal-dialog { margin: 5px auto !important; }
#userDetailsModal .modal-content { max-height: 90vh; display: flex; flex-direction: column; }
#userDetailsModal .modal-body { flex: 1 1 auto; overflow-y: auto; }
#userDetailsModal .badge { margin-right: .25rem; }
#userDetailsModal .card { border-radius: 12px; }
#userDetailsModal .card-body { padding: 1rem 1.25rem; }
#userDetailsModal .text-truncate-wrap { overflow-wrap: anywhere; word-break: break-word; }
#userDetailsModal .header-chip { gap: .35rem; flex-wrap: wrap; }

/* Garantir que o modal fique acima do backdrop exagerado de outros CSS */
#userDetailsModal { z-index: 1060 !important; }
.modal-backdrop { z-index: 1050 !important; }
.modal-backdrop.show { opacity: .5; }

/* Aplicar estilo bonito, mantendo scroll interno funcional */
#userDetailsModal.modal-feedback-beautiful,
#userDetailsModal.modal-feedback-beautiful .modal-dialog,
#userDetailsModal.modal-feedback-beautiful .modal-content {
  overflow: visible !important;
  max-height: none !important;
}
#userDetailsModal.modal-feedback-beautiful .modal-body {
  overflow-y: auto !important;
  max-height: none !important;
}

@media (max-width: 576px) {
  #userDetailsModal { padding-top: 0 !important; }
  #userDetailsModal .modal-dialog { margin: 0 !important; width: 100%; }
  #userDetailsModal .modal-content { height: 100vh; max-height: 100vh; }
  #userDetailsModal .modal-body { max-height: none; height: auto; overflow-y: auto; }
}
</style>