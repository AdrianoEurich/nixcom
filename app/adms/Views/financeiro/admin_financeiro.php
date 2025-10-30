<?php
if (!defined('C7E3L8K9E5')) { die('Erro: Acesso negado!'); }
$stats = $this->data['stats'] ?? [];
?>
<div class="content pt-0 px-0 pb-3" id="financeiroAdminContent" data-page-type="financeiro-admin">
  <div class="modern-dashboard">
    <div class="content-header mb-4 d-flex align-items-center justify-content-between">
      <h2 class="page-title mb-0">Financeiro (Admin)</h2>
    </div>

    <div class="row g-3">
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Usuários Totais</span>
            <i class="fas fa-users text-primary"></i>
          </div>
          <h3 class="mt-2 mb-0"><?= htmlspecialchars($stats['total_users'] ?? '—') ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Planos Premium</span>
            <i class="fas fa-gem text-warning"></i>
          </div>
          <h3 class="mt-2 mb-0"><?= htmlspecialchars($stats['premium_users'] ?? '—') ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Pagamentos Aprovados (30d)</span>
            <i class="fas fa-check-circle text-success"></i>
          </div>
          <h3 class="mt-2 mb-0"><?= htmlspecialchars($stats['payments_approved_30d'] ?? '—') ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Pendentes</span>
            <i class="fas fa-hourglass-half text-info"></i>
          </div>
          <h3 class="mt-2 mb-0"><?= htmlspecialchars($stats['payments_pending'] ?? '—') ?></h3>
        </div>
      </div>
    </div>
    </div>

    <div class="row g-3 mt-1">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Transações Recentes</h5>
          <div class="alert alert-secondary mb-0">Sem dados ainda. Integre aqui lista de transações do gateway.</div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Assinaturas</h5>
          <div class="alert alert-secondary mb-0">Sem dados ainda. Integre aqui assinaturas/renovações.</div>
        </div>
      </div>
    </div>
    </div>
  </div>
</div>
