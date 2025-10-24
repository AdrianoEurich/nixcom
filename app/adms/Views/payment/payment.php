<?php
// app/adms/Views/payment/payment.php
// Página de pagamento PIX

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Extrai as variáveis passadas pelo controlador
extract($this->data);

$subscription = $subscription ?? null;
$plano = $plano ?? [];
$user_data = $user_data ?? [];

// Função para corrigir encoding sem gerar avisos
function fixEncoding($string) {
    if (empty($string)) return $string;
    
    // Mapear caracteres problemáticos conhecidos
    $replacements = [
        'B?sico' => 'Básico',
        'Bßsico' => 'Básico',
        'BÃ¡sico' => 'Básico',
        'intermedi?rio' => 'intermediário',
        'intermedißrio' => 'intermediário',
        'intermediÃ¡rio' => 'intermediário',
        'Cria????o' => 'Criação',
        'Criaßßo' => 'Criação',
        'CriaÃ§Ã£o' => 'Criação',
        'an??ncios' => 'anúncios',
        'anßncios' => 'anúncios',
        'anÃºncios' => 'anúncios'
    ];
    
    // Aplicar substituições
    $fixed = str_replace(array_keys($replacements), array_values($replacements), $string);
    
    return $fixed;
}
?>

<div class="content pt-0 px-0 pb-3" id="paymentContent" data-page-type="payment">
    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="h4 mb-1">
                            <i class="fas fa-credit-card me-2 text-primary"></i>
                            Pagamento PIX
                        </h2>
                        <p class="text-muted mb-0">Escaneie o QR Code ou copie o código para pagar</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary fs-6 px-3 py-2">
                            <i class="fas fa-shield-alt me-1"></i>
                            Pagamento Seguro
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Informações do Plano -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-crown me-2"></i>
                            <?= htmlspecialchars(fixEncoding($plano['nome'] ?? 'Plano')) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Descrição</h6>
                            <p class="mb-0"><?= htmlspecialchars(fixEncoding($plano['descricao'] ?? '')) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Recursos Inclusos</h6>
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-check text-success me-2"></i> Painel administrativo</li>
                                <li><i class="fas fa-check text-success me-2"></i> Criação de anúncios</li>
                                <li><i class="fas fa-check text-success me-2"></i> <?= $plano['max_fotos'] ?? 0 ?> fotos na galeria</li>
                                <?php if (($plano['max_videos'] ?? 0) > 0): ?>
                                    <li><i class="fas fa-check text-success me-2"></i> <?= $plano['max_videos'] ?> vídeos</li>
                                <?php endif; ?>
                                <?php if (($plano['max_audios'] ?? 0) > 0): ?>
                                    <li><i class="fas fa-check text-success me-2"></i> <?= $plano['max_audios'] ?> áudios</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Valor:</span>
                                <span class="h5 mb-0 text-primary" id="planAmount">
                                    <?php 
                                    $valor = $plano['preco_6_meses'] ?? $plano['preco_mensal'] ?? 0;
                                    echo 'R$ ' . number_format($valor, 2, ',', '.');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Code e Pagamento -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Status do Pagamento -->
                        <div id="paymentStatus" class="mb-4" role="status" aria-live="polite">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-3"></i>
                                <div>
                                    <strong>Pronto para pagar!</strong>
                                    <p class="mb-0 small">Clique no botão abaixo para gerar seu PIX</p>
                                </div>
                            </div>
                        </div>

                        <!-- Botão para gerar pagamento -->
                        <div class="text-center mb-4">
                            <button id="generatePaymentBtn" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-qrcode me-2"></i>
                                Gerar PIX
                            </button>
                        </div>

                        <!-- QR Code -->
                        <div id="qrCodeSection" class="text-center mb-4" style="display: none;">
                            <h5 class="mb-3">
                                <i class="fas fa-qrcode me-2"></i>
                                Escaneie o QR Code
                            </h5>
                            <div class="qr-code-container mb-4">
                                <img id="qrCodeImage" src="" alt="QR Code PIX" class="img-fluid border rounded" style="max-width: 300px;">
                            </div>
                            <p class="text-muted small">
                                Abra seu app bancário e escaneie o código acima
                            </p>
                        </div>

                        <!-- Código PIX -->
                        <div id="pixCodeSection" class="mb-4" style="display: none;">
                            <h5 class="mb-3">
                                <i class="fas fa-copy me-2"></i>
                                Código PIX
                            </h5>
                            <div class="input-group">
                                <input type="text" id="pixCode" class="form-control" readonly>
                                <button class="btn btn-outline-primary" type="button" id="copyPixCode">
                                    <i class="fas fa-copy me-1"></i>
                                    Copiar
                                </button>
                            </div>
                            <p class="text-muted small mt-2">
                                Copie o código e cole no seu app bancário
                            </p>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="d-flex gap-2 justify-content-center">
                            <button id="checkPaymentBtn" class="btn btn-primary" style="display: none;">
                                <i class="fas fa-sync-alt me-1"></i>
                                Verificar Pagamento
                            </button>
                            <button id="newPaymentBtn" class="btn btn-outline-secondary" style="display: none;">
                                <i class="fas fa-plus me-1"></i>
                                Novo Pagamento
                            </button>
                        </div>

                        <!-- Informações de Segurança -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <i class="fas fa-lock text-success fa-2x mb-2"></i>
                                    <h6>Pagamento Seguro</h6>
                                    <p class="small text-muted">Dados protegidos com criptografia</p>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-clock text-info fa-2x mb-2"></i>
                                    <h6>Confirmação Rápida</h6>
                                    <p class="small text-muted">Pagamento confirmado em segundos</p>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-shield-alt text-warning fa-2x mb-2"></i>
                                    <h6>Garantia Total</h6>
                                    <p class="small text-muted">100% seguro e confiável</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variáveis globais (verificar se já existem para evitar conflitos)
if (typeof currentSubscriptionId === 'undefined') {
    var currentSubscriptionId = null;
}
if (typeof paymentCheckInterval === 'undefined') {
    var paymentCheckInterval = null;
}

// Funções globais para serem acessíveis pelo SPA
window.initializePayment = initializePayment;
window.copyPixCode = copyPixCode;
window.checkPaymentStatus = checkPaymentStatus;
window.createNewPayment = createNewPayment;

// Função chamada pelo SPA (dashboard_custom.js) quando esta view é carregada via AJAX
// Deve inicializar listeners, estados e ajustar a posição do conteúdo
window.initializePaymentPage = function(fullUrl, initialData) {
    try {
        console.log('initializePaymentPage chamado via SPA para:', fullUrl);

        // Preferir delegar para o setup central (dashboard_custom.js) se disponível.
        if (typeof window.setupPaymentPage === 'function') {
            try {
                console.log('Delegando inicialização para window.setupPaymentPage (dashboard_custom.js)');
                window.setupPaymentPage();
            } catch (err) {
                console.warn('Erro ao chamar setupPaymentPage:', err);
            }

            // Ajustar depois que o setup central tiver configurado os elementos
            setTimeout(() => {
                try { adjustPaymentContentPosition(); } catch (e) { console.warn('adjustPaymentContentPosition falhou', e); }
            }, 150);

            window.currentPaymentInitialData = initialData || null;
            return;
        }

        // Fallback local: re-anexar event listeners e configurar estados iniciais
        document.getElementById('generatePaymentBtn')?.removeEventListener('click', initializePayment);
        document.getElementById('generatePaymentBtn')?.addEventListener('click', initializePayment);
        document.getElementById('copyPixCode')?.removeEventListener('click', copyPixCode);
        document.getElementById('copyPixCode')?.addEventListener('click', copyPixCode);
        document.getElementById('checkPaymentBtn')?.removeEventListener('click', checkPaymentStatus);
        document.getElementById('checkPaymentBtn')?.addEventListener('click', checkPaymentStatus);
        document.getElementById('newPaymentBtn')?.removeEventListener('click', createNewPayment);
        document.getElementById('newPaymentBtn')?.addEventListener('click', createNewPayment);

        const genBtn = document.getElementById('generatePaymentBtn');
        if (genBtn) genBtn.disabled = false;
        const checkBtn = document.getElementById('checkPaymentBtn');
        if (checkBtn) checkBtn.style.display = 'none';
        const newBtn = document.getElementById('newPaymentBtn');
        if (newBtn) newBtn.style.display = 'none';

        setTimeout(() => {
            try { adjustPaymentContentPosition(); } catch (e) { console.warn('initializePaymentPage adjust failed', e); }
        }, 120);

        window.currentPaymentInitialData = initialData || null;
    } catch (e) {
        console.warn('Erro em initializePaymentPage', e);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Página de pagamento carregada');
    
    // Event listeners
    document.getElementById('generatePaymentBtn')?.addEventListener('click', initializePayment);
    document.getElementById('copyPixCode')?.addEventListener('click', copyPixCode);
    document.getElementById('checkPaymentBtn')?.addEventListener('click', checkPaymentStatus);
    document.getElementById('newPaymentBtn')?.addEventListener('click', createNewPayment);
    
    // Ensure initial button states
    const genBtn = document.getElementById('generatePaymentBtn');
    if (genBtn) genBtn.disabled = false;
    document.getElementById('checkPaymentBtn').style.display = 'none';
    document.getElementById('newPaymentBtn').style.display = 'none';

    try {
        const existingSubId = <?php echo json_encode($subscription['id'] ?? null); ?>;
        const existingSubStatus = <?php echo json_encode($subscription['status'] ?? null); ?>;
        if (!currentSubscriptionId && existingSubId && existingSubStatus === 'pending') {
            currentSubscriptionId = existingSubId;
            const checkBtn = document.getElementById('checkPaymentBtn');
            if (checkBtn) checkBtn.style.display = 'inline-block';
            try { showStatus('Aguardando pagamento...', 'info'); } catch (e) {}
            try { startPaymentCheck(); } catch (e) { console.warn('startPaymentCheck indisponível', e); }
        }
    } catch (e) { console.warn('init existing subscription failed', e); }
});

async function initializePayment() {
    try {
        // Desabilitar botão e mostrar loading
        const genBtn = document.getElementById('generatePaymentBtn');
        if (genBtn) {
            genBtn.disabled = true;
            genBtn.setAttribute('aria-busy', 'true');
        }
        showLoading('Gerando pagamento...');
        
        // Criar nova assinatura e pagamento
        const response = await createPayment();
        
        if (response && response.success) {
            currentSubscriptionId = response.subscription_id;
            showPaymentData(response);
            startPaymentCheck();
        } else {
                showPaymentError(response.message || 'Erro ao gerar pagamento');
            // Mostrar botão novamente em caso de erro
            if (genBtn) {
                genBtn.disabled = false;
                genBtn.removeAttribute('aria-busy');
            }
        }
    } catch (error) {
        console.error('Erro ao inicializar pagamento:', error);
            showPaymentError('Erro interno. Tente novamente.');
        // Mostrar botão novamente em caso de erro
        const genBtn2 = document.getElementById('generatePaymentBtn');
        if (genBtn2) {
            genBtn2.disabled = false;
            genBtn2.removeAttribute('aria-busy');
        }
    }
}

async function createPayment() {
    const planType = new URLSearchParams(window.location.search).get('plan') || 'basic';
    
    // Buscar ID do plano baseado no tipo
    const planId = await getPlanIdByType(planType);
    if (!planId) {
        throw new Error('Plano não encontrado');
    }
    
    try {
        const res = await fetch(`${window.URLADM}payment/create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                plano_id: planId,
                period: '6_meses',
                plan_type: planType
            })
        });

        if (!res.ok) {
            const text = await res.text().catch(() => '');
            throw new Error(text || `HTTP ${res.status}`);
        }

        const contentType = res.headers.get('content-type') || '';
        if (contentType.indexOf('application/json') === -1) {
            // Resposta inesperada (provavelmente HTML de login ou erro). Ler como texto e mostrar erro amigável.
            const text = await res.text().catch(() => '');
            console.warn('createPayment: resposta não-JSON recebida:', text.substring(0, 300));
            // Se for HTML, tentar extrair mensagem curta
            const snippet = text.replace(/\s+/g, ' ').replace(/<[^>]*>/g, '').trim().slice(0, 300);
            return { success: false, message: 'Resposta inválida do servidor: ' + (snippet || 'HTML inesperado') };
        }

        return await res.json();
    } catch (err) {
        console.error('createPayment error', err);
        return { success: false, message: err.message || 'Erro na requisição' };
    }
}

async function getPlanIdByType(planType) {
    // Mapear tipos para IDs (ajustar conforme necessário)
    const planMapping = {
        'basic': 2,
        'premium': 3
    };
    
    return planMapping[planType] || 2;
}

function showPaymentData(data) {
    // Atualizar valor
    document.getElementById('planAmount').textContent = `R$ ${data.amount.toFixed(2).replace('.', ',')}`;
    
    // Mostrar QR Code
    if (data.qr_code_base64) {
        document.getElementById('qrCodeImage').src = `data:image/png;base64,${data.qr_code_base64}`;
        document.getElementById('qrCodeSection').style.display = 'block';
    }
    else if (data.pix_copy_paste) {
        // Fallback: gerar QR no cliente usando uma API pública (gera PNG via URL)
        try {
            const qrText = encodeURIComponent(data.pix_copy_paste);
            // Usar Google Chart API (simples) para gerar QR - não ideal para produção, mas funciona como fallback
            const qrUrl = `https://chart.googleapis.com/chart?cht=qr&chl=${qrText}&chs=300x300&chld=L|1`;
            document.getElementById('qrCodeImage').src = qrUrl;
            document.getElementById('qrCodeSection').style.display = 'block';
        } catch (e) {
            console.warn('Erro ao gerar QR fallback:', e);
        }
    }
    
    // Mostrar código PIX
    if (data.pix_copy_paste) {
        document.getElementById('pixCode').value = data.pix_copy_paste;
        document.getElementById('pixCodeSection').style.display = 'block';
    }
    
    // Mostrar botões
    document.getElementById('checkPaymentBtn').style.display = 'inline-block';
    document.getElementById('newPaymentBtn').style.display = 'inline-block';
    
    // Atualizar status
        showStatus('Aguardando pagamento...', 'info');
}

function startPaymentCheck() {
    // Verificar status a cada 10 segundos
    paymentCheckInterval = setInterval(checkPaymentStatus, 10000);
}

function stopPaymentCheck() {
    if (paymentCheckInterval) {
        clearInterval(paymentCheckInterval);
        paymentCheckInterval = null;
    }
}

async function checkPaymentStatus() {
    if (!currentSubscriptionId) return;
    
    try {
        const res = await fetch(`${window.URLADM}payment/status?subscription_id=${currentSubscriptionId}`);
        if (!res.ok) {
            console.warn('Status fetch returned', res.status);
            return;
        }
        const data = await res.json();

        if (data && data.success) {
            if (data.status === 'approved') {
                showStatus('Pagamento aprovado! Redirecionando...', 'success');
                stopPaymentCheck();
                setTimeout(() => {
                    window.location.href = `${window.URLADM}dashboard`;
                }, 2000);
            } else if (data.status === 'paid_awaiting_admin') {
                showStatus('Pagamento confirmado! Aguardando aprovação do administrador.', 'warning');
                stopPaymentCheck();
            } else {
                showStatus(data.message, 'info');
            }
        }
    } catch (error) {
        console.error('Erro ao verificar pagamento:', error);
    }
}

function copyPixCode() {
    const pixCode = document.getElementById('pixCode');
    if (!pixCode) return;

    const text = pixCode.value || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
            showPaymentToast('Código PIX copiado!', 'success');
        }).catch(err => {
            console.warn('Clipboard API failed, fallback to execCommand', err);
            pixCode.select();
            try { document.execCommand('copy'); showPaymentToast('Código PIX copiado!', 'success'); }
            catch (e) { showPaymentToast('Não foi possível copiar. Copie manualmente.', 'error'); }
        });
    } else {
        // Fallback antigo
        pixCode.select();
        try { document.execCommand('copy'); showPaymentToast('Código PIX copiado!', 'success'); }
        catch (e) { showPaymentToast('Não foi possível copiar. Copie manualmente.', 'error'); }
    }
}

function createNewPayment() {
    if (confirm('Deseja gerar um novo pagamento? O pagamento anterior será cancelado.')) {
        currentSubscriptionId = null;
        stopPaymentCheck();
        initializePayment();
    }
}

function showLoading(message) {
    const statusDiv = document.getElementById('paymentStatus');
    statusDiv.innerHTML = `
        <div class="alert alert-info d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-3" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <div>
                <strong>${message}</strong>
                <p class="mb-0 small">Aguarde...</p>
            </div>
        </div>
    `;
}

function showStatus(message, type) {
    const statusDiv = document.getElementById('paymentStatus');
    const alertClass = `alert-${type}`;
    const icon = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
    
    statusDiv.innerHTML = `
        <div class="alert ${alertClass} d-flex align-items-center">
            <i class="fas fa-${icon} me-3"></i>
            <div>
                <strong>${message}</strong>
            </div>
        </div>
    `;
}

// NOTE: global `showError` is provided by general-utils.js (expects inputElement).
// Do NOT declare a global `showError` here to avoid collisions. Use showPaymentError instead.

    function showPaymentError(message) {
        const statusDiv = document.getElementById('paymentStatus');
        statusDiv.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-3"></i>
                <div>
                    <strong>Erro:</strong> ${message}
                </div>
            </div>
        `;
        // Mostrar modal de erro centralizado quando disponível
        try {
            if (window.NixcomModalManager && typeof window.NixcomModalManager.showSimple === 'function') {
                window.NixcomModalManager.showSimple('error', message, 'Erro', 4000);
            }
        } catch (e) {
            console.warn('NixcomModalManager não disponível', e);
        }
    }
function showPaymentToast(message, type) {
    // Usar o manager centralizado se existir (sucesso/info)
    try {
        if (window.NixcomModalManager && typeof window.NixcomModalManager.showSimple === 'function') {
            const t = (type === 'error') ? 'info' : (type || 'success');
            window.NixcomModalManager.showSimple(t, message, null, 2500);
            return;
        }
    } catch (e) {
        console.warn('NixcomModalManager não disponível', e);
    }

    // Fallback mínimo
    console.log(`Toast: ${message} (${type})`);
}
</script>

<style>
.qr-code-container {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    display: inline-block;
}

#pixCode {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
}

.alert {
    border-radius: 10px;
    border: none;
}

.card {
    border-radius: 15px;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

/* Forçar centralização dentro do SPA e largura controlada */
#paymentContent > .container {
    max-width: 1100px;
    margin: 0 auto;
}

/* Garantir que os cartões usem todo o espaço disponível dentro da coluna */
.card.h-100 {
    height: 100%;
}
/* Nota: regras de layout adicionais removidas para preservar comportamento padrão do Bootstrap */

/* Responsividade: em telas menores, usar largura total e margens padrão */
@media (max-width: 991.98px) {
    #paymentContent > .container {
        max-width: 100%;
        margin-left: auto !important;
        margin-right: auto !important;
        padding-left: 12px;
        padding-right: 12px;
    }
}
</style>

<script>
// Ajuste dinâmico para centralizar #paymentContent dentro da área disponível à direita do sidebar
function adjustPaymentContentPosition() {
    try {
        const sidebar = document.getElementById('sidebar');
        const paymentContainer = document.querySelector('#paymentContent > .container');
        console.log('adjustPaymentContentPosition: running');
        if (!paymentContainer) { console.log('adjustPaymentContentPosition: no paymentContainer found'); }
        if (!paymentContainer) return;

        // Resetar margens para o comportamento padrão do Bootstrap em todas as larguras
        paymentContainer.style.marginLeft = 'auto';
        paymentContainer.style.marginRight = 'auto';
        console.log('adjustPaymentContentPosition: margins set to auto (CSS-driven centering)');
    } catch (e) {
        console.warn('adjustPaymentContentPosition erro', e);
    }
}

// Executar após small delay para garantir que o layout do SPA esteja aplicado
window.addEventListener('load', () => setTimeout(adjustPaymentContentPosition, 50));
window.addEventListener('resize', () => setTimeout(adjustPaymentContentPosition, 50));

// Também expor para uso manual se necessário
window.adjustPaymentContentPosition = adjustPaymentContentPosition;

// Se a view for carregada dinamicamente pela SPA, observar o DOM e disparar o ajuste
;(function() {
    try {
        // Tentativa imediata caso a view já esteja presente
        setTimeout(() => {
            adjustPaymentContentPosition();
        }, 60);

        if (typeof MutationObserver === 'undefined') return;

        const observer = new MutationObserver((mutations, obs) => {
            for (const m of mutations) {
                for (const node of m.addedNodes) {
                    if (node && node.nodeType === 1) {
                        // Se o nó adicionado for a própria view ou contiver a view
                        if (node.id === 'paymentContent' || (node.querySelector && node.querySelector('#paymentContent'))) {
                            // pequeno delay para garantir estilos aplicados pela SPA
                            setTimeout(adjustPaymentContentPosition, 60);
                            // desconectar após estabilizar para reduzir overhead
                            setTimeout(() => { try { obs.disconnect(); } catch(e){} }, 1500);
                            return;
                        }
                    }
                }
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    } catch (e) {
        console.warn('Observer de paymentContent falhou', e);
    }
})();
</script>
