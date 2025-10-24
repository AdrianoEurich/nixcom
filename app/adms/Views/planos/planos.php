<?php
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="plans-container">
    <div class="container">
        <div class="plans-header">
            <h2 class="plans-title">Escolha Seu Plano</h2>
            <p class="plans-subtitle">Selecione o plano ideal para suas necessidades</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="plan-card" data-plan="free">
                    <h3 class="plan-name">Plano Gratuito</h3>
                    <div class="plan-price">Grátis</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Painel administrativo</li>
                        <li><i class="fas fa-check"></i> Criação de anúncios</li>
                        <li><i class="fas fa-check"></i> 1 foto capa + 2 fotos galeria</li>
                        <li><i class="fas fa-check"></i> 1 vídeo de confirmação</li>
                        <li class="disabled"><i class="fas fa-times"></i> Áudios</li>
                    </ul>
                    <div class="plan-actions">
                        <button class="btn btn-plan" onclick="selectPlan('free')">Escolher Plano</button>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="plan-card popular" data-plan="basic">
                    <div class="popular-badge">Mais Popular</div>
                    <h3 class="plan-name">Plano Básico</h3>
                    <div class="plan-price">R$ 29,90/mês</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Painel administrativo</li>
                        <li><i class="fas fa-check"></i> Criação de anúncios</li>
                        <li><i class="fas fa-check"></i> 1 foto capa + 20 fotos galeria</li>
                        <li><i class="fas fa-check"></i> 1 vídeo de confirmação</li>
                        <li class="disabled"><i class="fas fa-times"></i> Áudios</li>
                    </ul>
                    <div class="plan-actions">
                        <?php if (isset($this->data['is_logged_in']) && $this->data['is_logged_in']): ?>
                            <?php if ($this->data['current_plan'] === 'basic'): ?>
                                <span class="btn btn-plan btn-current-plan">
                                    <i class="fas fa-check me-2"></i>Plano Atual
                                </span>
                            <?php else: ?>
                                <button class="btn btn-plan" onclick="changePlan('basic')">Mudar para Básico</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-plan" onclick="selectPlan('basic')">Escolher Plano</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="plan-card" data-plan="premium">
                    <h3 class="plan-name">Plano Premium</h3>
                    <div class="plan-price">R$ 49,90/mês</div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Painel administrativo</li>
                        <li><i class="fas fa-check"></i> Criação de anúncios</li>
                        <li><i class="fas fa-check"></i> 1 foto capa + 20 fotos galeria</li>
                        <li><i class="fas fa-check"></i> 3 vídeos (1 confirmação + 2 extras)</li>
                        <li><i class="fas fa-check"></i> 3 áudios</li>
                    </ul>
                    <div class="plan-actions">
                        <?php if (isset($this->data['is_logged_in']) && $this->data['is_logged_in']): ?>
                            <?php if ($this->data['current_plan'] === 'premium'): ?>
                                <span class="btn btn-plan btn-current-plan">
                                    <i class="fas fa-check me-2"></i>Plano Atual
                                </span>
                            <?php else: ?>
                                <button class="btn btn-plan" onclick="changePlan('premium')">Mudar para Premium</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-plan" onclick="selectPlan('premium')">Escolher Plano</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="plans-footer text-center mt-5">
            <p class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Todos os planos incluem garantia de 30 dias
            </p>
        </div>
    </div>
</div>

<script>
function selectPlan(planType) {
    // Salvar plano na sessão
    fetch('<?= URLADM ?>planos/selecionar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ plan: planType })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirecionar para cadastro
            window.location.href = '<?= URLADM ?>cadastro';
        } else {
            alert('Erro ao selecionar plano: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao selecionar plano. Tente novamente.');
    });
}

function changePlan(planType) {
    if (!confirm('Tem certeza que deseja mudar para o plano ' + planType.toUpperCase() + '?')) {
        return;
    }

    // Mostrar loading
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
    button.disabled = true;

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
            alert(data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                // Recarregar página para atualizar status
                window.location.reload();
            }
        } else {
            alert('Erro ao mudar plano: ' + data.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao mudar plano. Tente novamente.');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
