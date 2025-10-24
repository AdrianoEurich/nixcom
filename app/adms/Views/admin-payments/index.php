<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Gerenciar Pagamentos</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?= URLADM ?>dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pagamentos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1 overflow-hidden">
                            <p class="text-truncate font-size-14 mb-2">Total de Pagamentos</p>
                            <h4 class="mb-0"><?= $stats['total'] ?? 0 ?></h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title rounded-circle bg-primary">
                                    <i class="fas fa-credit-card font-size-18"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1 overflow-hidden">
                            <p class="text-truncate font-size-14 mb-2">Pendentes</p>
                            <h4 class="mb-0 text-warning"><?= $stats['pending'] ?? 0 ?></h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-warning">
                                <span class="avatar-title rounded-circle bg-warning">
                                    <i class="fas fa-clock font-size-18"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1 overflow-hidden">
                            <p class="text-truncate font-size-14 mb-2">Aprovados</p>
                            <h4 class="mb-0 text-success"><?= $stats['approved'] ?? 0 ?></h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success">
                                <span class="avatar-title rounded-circle bg-success">
                                    <i class="fas fa-check font-size-18"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-1 overflow-hidden">
                            <p class="text-truncate font-size-14 mb-2">Valor Total</p>
                            <h4 class="mb-0 text-info">R$ <?= number_format($stats['total_amount'] ?? 0, 2, ',', '.') ?></h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info">
                                <span class="avatar-title rounded-circle bg-info">
                                    <i class="fas fa-dollar-sign font-size-18"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagamentos Pendentes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Pagamentos Pendentes</h4>
                    <p class="card-title-desc">Gerencie os pagamentos que aguardam aprovação</p>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_payments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success font-size-48 mb-3"></i>
                            <h5>Nenhum pagamento pendente</h5>
                            <p class="text-muted">Todos os pagamentos foram processados!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Plano</th>
                                        <th>Valor</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_payments as $payment): ?>
                                        <tr>
                                            <td>#<?= $payment['id'] ?></td>
                                            <td><?= htmlspecialchars($payment['nome']) ?></td>
                                            <td><?= htmlspecialchars($payment['email']) ?></td>
                                            <td>
                                                <span class="badge badge-primary"><?= ucfirst($payment['plan_type']) ?></span>
                                            </td>
                                            <td>R$ <?= number_format($payment['amount'], 2, ',', '.') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></td>
                                            <td>
                                                <span class="badge badge-warning">Pendente</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="approvePayment(<?= $payment['id'] ?>, <?= $payment['user_id'] ?>)">
                                                        <i class="fas fa-check"></i> Aprovar
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="rejectPayment(<?= $payment['id'] ?>, <?= $payment['user_id'] ?>)">
                                                        <i class="fas fa-times"></i> Rejeitar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Rejeição -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejeitar Pagamento</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" id="reject_payment_id" name="payment_id">
                    <input type="hidden" id="reject_user_id" name="user_id">
                    <div class="form-group">
                        <label for="reject_reason">Motivo da Rejeição:</label>
                        <textarea class="form-control" id="reject_reason" name="reason" rows="3" 
                                  placeholder="Digite o motivo da rejeição..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rejeitar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approvePayment(paymentId, userId) {
    if (confirm('Tem certeza que deseja aprovar este pagamento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= URLADM ?>admin-payments/approve';
        
        const paymentIdInput = document.createElement('input');
        paymentIdInput.type = 'hidden';
        paymentIdInput.name = 'payment_id';
        paymentIdInput.value = paymentId;
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        
        form.appendChild(paymentIdInput);
        form.appendChild(userIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectPayment(paymentId, userId) {
    document.getElementById('reject_payment_id').value = paymentId;
    document.getElementById('reject_user_id').value = userId;
    $('#rejectModal').modal('show');
}

document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= URLADM ?>admin-payments/reject', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao rejeitar pagamento: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao rejeitar pagamento');
    });
});
</script>

