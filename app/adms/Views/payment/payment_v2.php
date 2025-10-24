<?php
// app/adms/Views/payment/payment_v2.php
if (!defined('C7E3L8K9E5')) { header('Location: /'); die('Erro: Página não encontrada!'); }

// Variáveis da view
extract($this->data);
$subscription = $subscription ?? null;
$plano = $plano ?? [];
$user_data = $user_data ?? [];

$planNome = $plano['nome'] ?? 'Plano';
$planDescricao = $plano['descricao'] ?? '';
$valor = $plano['preco_6_meses'] ?? ($plano['preco_mensal'] ?? 0);
$subscriptionId = $subscription['id'] ?? null;
$subscriptionStatus = $subscription['status'] ?? null;
$planType = $plano['tipo'] ?? (isset($_GET['plan']) ? $_GET['plan'] : 'basic');
// Ambiente
$isSandbox = \Adms\Config\MercadoPagoConfig::isSandbox();
?>

<script>
  // Sinalizar para scripts legados (pagamento.js) que a v2 está ativa
  window.PAYMENT_V2_ACTIVE = true;
  try { document.documentElement.setAttribute('data-payment-v2', '1'); } catch (e) {}
  // Neutralizar inicializadores legados se presentes
  try {
    window.initializePaymentPage = function(){ console.log('payment_v2: bloqueando initializer legado'); };
    window.initializePayment = window.initializePayment || function(){ console.log('payment_v2: initializePayment legado ignorado'); };
  } catch(e) { }
  // Bloquear injeção do script legado pagamento.js caso algum loader tente adicioná-lo
  try {
    const blockLegacyScript = (node) => {
      try {
        if (node && node.tagName === 'SCRIPT') {
          const src = node.getAttribute('src') || '';
          if (src.indexOf('/assets/js/pagamento.js') !== -1) {
            console.log('payment_v2: bloqueando carregamento de pagamento.js');
            node.parentNode && node.parentNode.removeChild(node);
            return true;
          }
        }
      } catch(err) {}
      return false;
    };
    // Remover imediatamente se já existir na página por algum motivo
    document.querySelectorAll('script[src*="/assets/js/pagamento.js"]').forEach(s => { try { s.parentNode && s.parentNode.removeChild(s); } catch(e){} });
    // Observar novas inclusões de script
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (!m.addedNodes) continue;
        for (const n of m.addedNodes) {
          if (blockLegacyScript(n)) continue;
          if (n && n.querySelectorAll) {
            n.querySelectorAll('script[src*="/assets/js/pagamento.js"]').forEach(s => blockLegacyScript(s));
          }
        }
      }
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
  } catch(err) { }
</script>
<div class="content pt-0 px-0 pb-3" id="paymentContent" data-page-type="payment" data-payment-v2="1" data-subscription-id="<?= htmlspecialchars((string)($subscriptionId ?? '')) ?>" data-subscription-status="<?= htmlspecialchars((string)($subscriptionStatus ?? '')) ?>" data-is-sandbox="<?= $isSandbox ? '1' : '0' ?>">
  <div class="container" style="max-width: 1000px; margin: 0 auto;">
    <div class="row mb-4 align-items-center">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <h2 class="h4 mb-1"><i class="fas fa-credit-card me-2 text-primary"></i>Pagamento PIX</h2>
          <p class="text-muted mb-0">Finalize seu pagamento com segurança</p>
        </div>
        <div>
          <span class="badge bg-primary fs-6 px-3 py-2"><i class="fas fa-shield-alt me-1"></i>Seguro</span>
        </div>
      </div>
    </div>

    <!-- Cards informativos (confiança/segurança) -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-start gap-3">
            <i class="fas fa-lock fa-lg text-primary mt-1"></i>
            <div>
              <h6 class="mb-1">Pagamento Seguro</h6>
              <p class="mb-0 small text-muted">Transações protegidas e criptografadas.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-start gap-3">
            <i class="fas fa-bolt fa-lg text-success mt-1"></i>
            <div>
              <h6 class="mb-1">PIX Instantâneo</h6>
              <p class="mb-0 small text-muted">Aprovação rápida após a confirmação.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-start gap-3">
            <i class="fas fa-headset fa-lg text-info mt-1"></i>
            <div>
              <h6 class="mb-1">Suporte Dedicado</h6>
              <p class="mb-0 small text-muted">Conte com nossa equipe para ajudar.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-start gap-3">
            <i class="fas fa-file-shield fa-lg text-warning mt-1"></i>
            <div>
              <h6 class="mb-1">Transparência</h6>
              <p class="mb-0 small text-muted">Resumo claro do pedido e valores.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-gradient-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-crown me-2"></i><?= htmlspecialchars($planNome) ?></h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <h6 class="text-muted mb-2">Descrição</h6>
              <p class="mb-0"><?= htmlspecialchars($planDescricao) ?></p>
            </div>
            <div class="border-top pt-3 d-flex justify-content-between align-items-center">
              <span class="text-muted">Valor</span>
              <span class="h5 mb-0 text-primary" id="planAmount">R$ <?= number_format((float)$valor, 2, ',', '.') ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <div id="paymentStatus" class="mb-3" role="status" aria-live="polite"></div>

            <div class="text-center mb-4">
              <button id="generatePaymentBtn" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-qrcode me-2"></i> Gerar PIX
              </button>
            </div>

            <div id="qrCodeSection" class="text-center mb-4" style="display:none;">
              <h6 class="mb-3"><i class="fas fa-qrcode me-2"></i>Escaneie o QR Code</h6>
              <div class="qr-code-container mb-3">
                <img id="qrCodeImage" src="" alt="QR Code PIX" class="img-fluid border rounded" style="max-width:300px;" />
              </div>
              <p class="text-muted small">Use o app do seu banco para escanear o código</p>
            </div>

            <div id="pixCodeSection" class="mb-4" style="display:none;">
              <h6 class="mb-2"><i class="fas fa-copy me-2"></i>Código PIX Copia e Cola</h6>
              <div class="input-group">
                <input type="text" id="pixCode" class="form-control" readonly />
                <button id="copyPixCode" class="btn btn-outline-primary" type="button">
                  <i class="fas fa-copy me-1"></i>Copiar
                </button>
              </div>
            </div>

            <div class="d-flex gap-2 justify-content-center">
              <button id="checkPaymentBtn" class="btn btn-primary" style="display:none;"><i class="fas fa-sync-alt me-1"></i>Verificar Pagamento</button>
              <button id="newPaymentBtn" class="btn btn-outline-secondary" style="display:none;"><i class="fas fa-plus me-1"></i>Novo Pagamento</button>
              <?php if ($isSandbox): ?>
              <button id="simulateApproveBtn" class="btn btn-outline-success"><i class="fas fa-magic me-1"></i>Simular Pagamento (Sandbox)</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.qr-code-container{background:#f8f9fa;border-radius:10px;padding:20px;display:inline-block}
.bg-gradient-primary{background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)}
</style>

<script>
(function(){
  // Desativar lógica inline para evitar duplicidade; usar pagamento_v2.js
  if (window && window.PAYMENT_V2_ACTIVE) { return; }
  // Guardas e estado
  const content = document.getElementById('paymentContent');
  if (!content || content.dataset.paymentV2 !== '1') return;

  let currentSubscriptionId = <?= json_encode($subscriptionId) ?> || null;
  let paymentCheckInterval = null;
  const planType = <?= json_encode($planType) ?> || (new URLSearchParams(location.search).get('plan') || 'basic');

  // Elementos
  const el = {
    generatePaymentBtn: document.getElementById('generatePaymentBtn'),
    copyPixCode: document.getElementById('copyPixCode'),
    checkPaymentBtn: document.getElementById('checkPaymentBtn'),
    newPaymentBtn: document.getElementById('newPaymentBtn'),
    qrCodeSection: document.getElementById('qrCodeSection'),
    pixCodeSection: document.getElementById('pixCodeSection'),
    paymentStatus: document.getElementById('paymentStatus'),
    qrCodeImage: document.getElementById('qrCodeImage'),
    pixCode: document.getElementById('pixCode'),
    planAmount: document.getElementById('planAmount')
  };

  // Util
  function showStatus(message, type){
    const icon = type==='success'?'check-circle':type==='warning'?'exclamation-triangle':'info-circle';
    el.paymentStatus.innerHTML = `
      <div class="alert alert-${type||'info'} d-flex align-items-center">
        <i class="fas fa-${icon} me-3"></i>
        <div><strong>${message}</strong></div>
      </div>`;
  }
  function showLoading(msg){
    el.paymentStatus.innerHTML = `
      <div class="alert alert-info d-flex align-items-center">
        <div class="spinner-border spinner-border-sm me-3" role="status"><span class="visually-hidden">...</span></div>
        <div><strong>${msg||'Processando...'}</strong><p class="mb-0 small">Aguarde...</p></div>
      </div>`;
  }
  function startPaymentCheck(){
    stopPaymentCheck();
    paymentCheckInterval = setInterval(checkPaymentStatus, 8000);
  }
  function stopPaymentCheck(){
    if (paymentCheckInterval){ clearInterval(paymentCheckInterval); paymentCheckInterval=null; }
  }

  // Map plano tipo->id
  function getPlanIdByType(t){
    const map={ basic:2, premium:3 };
    return map[t] || 2;
  }

  async function createPayment(){
    try{
      el.generatePaymentBtn.disabled = true;
      el.generatePaymentBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gerando PIX...';
      showLoading('Gerando pagamento...');
      const body = { plano_id: getPlanIdByType(planType), period: '6_meses', plan_type: planType };
      const res = await fetch(`${window.URLADM}payment/create`, {
        method:'POST', headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}, body: JSON.stringify(body)
      });
      if(!res.ok){ const txt = await res.text().catch(()=> ''); throw new Error(txt||`HTTP ${res.status}`); }
      const ct = res.headers.get('content-type')||''; if (!ct.includes('application/json')){ throw new Error('Resposta inválida do servidor'); }
      const data = await res.json();
      if(!data.success){ throw new Error(data.message || 'Falha ao criar pagamento'); }
      currentSubscriptionId = data.subscription_id || currentSubscriptionId;
      // UI
      if (data.qr_code_base64 && el.qrCodeImage){
        el.qrCodeImage.src = `data:image/png;base64,${data.qr_code_base64}`; el.qrCodeSection.style.display='block';
      }
      if (data.pix_copy_paste && el.pixCode){
        el.pixCode.value = data.pix_copy_paste; el.pixCodeSection.style.display='block';
      }
      if (el.checkPaymentBtn) el.checkPaymentBtn.style.display='inline-block';
      if (el.newPaymentBtn) el.newPaymentBtn.style.display='inline-block';
      showStatus('Aguardando pagamento...', 'info');
      startPaymentCheck();
    }catch(err){
      console.error('createPayment error', err);
      showStatus('Erro ao gerar pagamento. Tente novamente.', 'danger');
      el.generatePaymentBtn.disabled = false;
      el.generatePaymentBtn.innerHTML = '<i class="fas fa-qrcode me-2"></i> Gerar PIX';
    }
  }

  async function checkPaymentStatus(){
    if(!currentSubscriptionId) return;
    try{
      const res = await fetch(`${window.URLADM}payment/status?subscription_id=${encodeURIComponent(currentSubscriptionId)}`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if(!res.ok){ return; }
      const data = await res.json();
      if(!data || !data.success) return;
      if (data.status === 'approved'){
        stopPaymentCheck();
        showStatus('Pagamento aprovado! Redirecionando...', 'success');
        setTimeout(()=>{ window.location.href = window.URLADM + 'dashboard'; }, 1500);
      } else if (data.status === 'paid_awaiting_admin'){
        stopPaymentCheck();
        showStatus('Pagamento confirmado! Aguardando aprovação do administrador.', 'warning');
      }
    }catch(e){ console.warn('checkPaymentStatus error', e); }
  }

  function copyPix(){
    if(!el.pixCode) return;
    const text = el.pixCode.value || '';
    if(navigator.clipboard){ navigator.clipboard.writeText(text).then(()=>showToast('Código PIX copiado!','success')).catch(()=>fallback()); }
    else { fallback(); }
    function fallback(){ try{ el.pixCode.select(); document.execCommand('copy'); showToast('Código PIX copiado!','success'); }catch(e){ showToast('Não foi possível copiar. Copie manualmente.','error'); } }
  }

  function showToast(message, type){ console.log(`Toast: ${message} (${type||'info'})`); }

  function newPayment(){
    if(!confirm('Deseja gerar um novo pagamento? O pagamento anterior será cancelado.')) return;
    stopPaymentCheck();
    currentSubscriptionId = null;
    el.qrCodeSection.style.display='none';
    el.pixCodeSection.style.display='none';
    el.generatePaymentBtn.disabled = false;
    el.generatePaymentBtn.innerHTML = '<i class="fas fa-qrcode me-2"></i> Gerar PIX';
    showStatus('Pronto para pagar! Clique em Gerar PIX.', 'info');
  }

  // Listeners
  el.generatePaymentBtn?.addEventListener('click', createPayment);
  el.copyPixCode?.addEventListener('click', copyPix);
  el.checkPaymentBtn?.addEventListener('click', checkPaymentStatus);
  el.newPaymentBtn?.addEventListener('click', newPayment);

  // Estado inicial
  (function init(){
    // Se vier com assinatura pendente do backend, iniciar polling automaticamente
    const initialStatus = <?= json_encode($subscriptionStatus) ?>;
    if (currentSubscriptionId && initialStatus === 'pending'){
      if (el.checkPaymentBtn) el.checkPaymentBtn.style.display='inline-block';
      <?php if ($isSandbox): ?>
      const simBtn = document.getElementById('simulateApproveBtn'); if (simBtn) simBtn.style.display='inline-block';
      <?php endif; ?>
      showStatus('Aguardando pagamento...', 'info');
      startPaymentCheck();
    } else {
      showStatus('Pronto para pagar! Clique em Gerar PIX.', 'info');
    }
  })();
})();
</script>

<!-- Script v2 isolado -->
<script src="<?= htmlspecialchars((string)(URLADM ?? '')) ?>assets/js/pagamento_v2.js?v=<?= time() ?>"></script>
<script>
  // Inicialização explícita pós-injeção SPA
  try { if (typeof window.initializePaymentV2 === 'function') { window.initializePaymentV2(); } } catch(e) { console.warn('initializePaymentV2 indisponível', e); }
</script>
