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
                    <i class="fas fa-webhook me-2 text-primary"></i>
                    Configuração de Webhook
                </h1>
                <p class="text-muted">Configure e teste o webhook do Mercado Pago</p>
            </div>

            <!-- Status do Ambiente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Status do Ambiente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?= $this->data['environment_info']['is_sandbox'] ? 'warning' : 'success' ?> me-2">
                                    <?= $this->data['environment_info']['is_sandbox'] ? 'SANDBOX' : 'PRODUÇÃO' ?>
                                </span>
                                <span class="text-muted">Modo</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-link me-2 text-primary"></i>
                                <span class="text-muted"><?= $this->data['environment_info']['base_url'] ?></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-key me-2 text-success"></i>
                                <span class="text-muted"><?= $this->data['environment_info']['has_token'] ? 'Token Configurado' : 'Token Não Configurado' ?></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt me-2 text-info"></i>
                                <span class="text-muted"><?= str_starts_with($this->data['webhook_url'], 'https://') ? 'HTTPS Ativo' : 'HTTP (Desenvolvimento)' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuração do Webhook -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>Configuração do Webhook
                    </h5>
                </div>
                <div class="card-body">
                    <form id="webhookConfigForm">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="webhookUrl" class="form-label">URL do Webhook</label>
                                <input type="url" class="form-control" id="webhookUrl" 
                                       value="<?= $this->data['webhook_url'] ?>" 
                                       placeholder="https://seudominio.com/webhook/mercadopago.php" required>
                                <div class="form-text">
                                    URL pública onde o Mercado Pago enviará as notificações
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" id="configureWebhook">
                                        <i class="fas fa-save me-2"></i>Configurar Webhook
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Tópicos do Webhook</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="topicPayment" value="payment" checked>
                                    <label class="form-check-label" for="topicPayment">
                                        Pagamentos (payment)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="topicOrder" value="order">
                                    <label class="form-check-label" for="topicOrder">
                                        Pedidos (order)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="topicMerchantOrder" value="merchant_order">
                                    <label class="form-check-label" for="topicMerchantOrder">
                                        Pedidos do Comerciante (merchant_order)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-success" id="testWebhook">
                                        <i class="fas fa-play me-2"></i>Testar Webhook
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs do Webhook -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Logs do Webhook
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Payment ID</th>
                                    <th>Usuário</th>
                                    <th>Resposta</th>
                                </tr>
                            </thead>
                            <tbody id="webhookLogsTable">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
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

<!-- Modal de Resultado -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalTitle">Resultado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultModalBody">
                <!-- Conteúdo será inserido aqui -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔗 Configuração de Webhook carregada');
    
    // Elementos
    const webhookConfigForm = document.getElementById('webhookConfigForm');
    const webhookUrl = document.getElementById('webhookUrl');
    const configureWebhookBtn = document.getElementById('configureWebhook');
    const testWebhookBtn = document.getElementById('testWebhook');
    const resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
    
    // Event listeners
    webhookConfigForm.addEventListener('submit', configureWebhook);
    testWebhookBtn.addEventListener('click', testWebhook);
    
    // Carregar logs iniciais
    loadWebhookLogs();
    
    function configureWebhook(e) {
        e.preventDefault();
        
        const url = webhookUrl.value;
        const topics = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
                           .map(cb => cb.value);
        
        if (!url) {
            showResult('Erro', 'URL do webhook é obrigatória', 'danger');
            return;
        }
        
        configureWebhookBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Configurando...';
        configureWebhookBtn.disabled = true;
        
        fetch(`${window.URLADM}webhook-config/configure`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                webhook_url: url,
                topics: topics
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult('Sucesso', `Webhook configurado com sucesso!<br>ID: ${data.webhook_id}`, 'success');
            } else {
                showResult('Erro', data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showResult('Erro', 'Erro ao configurar webhook', 'danger');
        })
        .finally(() => {
            configureWebhookBtn.innerHTML = '<i class="fas fa-save me-2"></i>Configurar Webhook';
            configureWebhookBtn.disabled = false;
        });
    }
    
    function testWebhook() {
        const url = webhookUrl.value;
        
        if (!url) {
            showResult('Erro', 'URL do webhook é obrigatória', 'danger');
            return;
        }
        
        testWebhookBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testando...';
        testWebhookBtn.disabled = true;
        
        fetch(`${window.URLADM}webhook-config/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                webhook_url: url
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResult('Sucesso', `Notificação de teste enviada!<br>Resposta: ${data.response}`, 'success');
                loadWebhookLogs(); // Recarregar logs
            } else {
                showResult('Erro', data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showResult('Erro', 'Erro ao testar webhook', 'danger');
        })
        .finally(() => {
            testWebhookBtn.innerHTML = '<i class="fas fa-play me-2"></i>Testar Webhook';
            testWebhookBtn.disabled = false;
        });
    }
    
    function loadWebhookLogs() {
        // Simular carregamento de logs (implementar endpoint real)
        setTimeout(() => {
            const tbody = document.getElementById('webhookLogsTable');
            tbody.innerHTML = `
                <tr>
                    <td>${new Date().toLocaleString('pt-BR')}</td>
                    <td>payment</td>
                    <td><span class="badge bg-success">approved</span></td>
                    <td>test_payment_123</td>
                    <td>54</td>
                    <td>200 OK</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        Logs serão carregados aqui quando implementados
                    </td>
                </tr>
            `;
        }, 1000);
    }
    
    function showResult(title, message, type) {
        document.getElementById('resultModalTitle').textContent = title;
        document.getElementById('resultModalBody').innerHTML = message;
        
        // Atualizar cor do modal baseado no tipo
        const modal = document.getElementById('resultModal');
        modal.className = modal.className.replace(/modal-\w+/, '');
        if (type === 'success') {
            modal.classList.add('modal-success');
        } else if (type === 'danger') {
            modal.classList.add('modal-danger');
        }
        
        resultModal.show();
    }
});
</script>

<style>
.modal-success .modal-header {
    background-color: #d4edda;
    border-bottom: 1px solid #c3e6cb;
}

.modal-danger .modal-header {
    background-color: #f8d7da;
    border-bottom: 1px solid #f5c6cb;
}
</style>

