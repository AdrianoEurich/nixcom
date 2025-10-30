<?php
if (!defined('C7E3L8K9E5')) { die('Erro: Acesso negado!'); }
$u = $this->data['user'] ?? [];
$plan = $u['plan_type'] ?? 'free';
$payment = $u['payment_status'] ?? 'pending';
?>
<div class="content pt-0 px-0 pb-3" id="financeiroContent" data-page-type="financeiro-user">
  <div class="modern-dashboard">
    <div class="content-header mb-4">
      <h2 class="page-title">Financeiro</h2>
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title mb-2">Seu Plano</h5>
            <p class="mb-1"><strong>Plano:</strong> <span class="text-uppercase"><?= htmlspecialchars($plan) ?></span></p>
            <p class="mb-3"><strong>Status Pagamento:</strong> <span class="text-capitalize"><?= htmlspecialchars($payment) ?></span></p>
            <a href="<?= URLADM ?>planos" class="btn btn-primary btn-sm" data-spa="true">Gerenciar Plano</a>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title mb-3">Histórico de Pagamentos</h5>
            <div class="alert alert-info mb-0">Nenhuma transação encontrada ainda.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title mb-3">Faturas</h5>
            <div class="alert alert-secondary mb-0">As suas faturas aparecerão aqui quando houverem cobranças.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
