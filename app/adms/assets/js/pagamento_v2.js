// pagamento_v2.js - lógica isolada da página de pagamento v2
(function(){
  console.log('[payment_v2] script loaded');
  function byId(id){ return document.getElementById(id); }

  let currentSubscriptionId = null;
  let paymentCheckInterval = null;

  function showStatus(message, type){
    const el = byId('paymentStatus');
    if (!el) return;
    const icon = type==='success'?'check-circle':type==='warning'?'exclamation-triangle':'info-circle';
    el.innerHTML = `
      <div class="alert alert-${type||'info'} d-flex align-items-center">
        <i class="fas fa-${icon} me-3"></i>
        <div><strong>${message}</strong></div>
      </div>`;
  }
  function showLoading(msg){
    const el = byId('paymentStatus');
    if (!el) return;
    el.innerHTML = `
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
  function getPlanIdByType(t){
    const map={ basic:2, premium:3 };
    return map[t] || 2;
  }
  async function simulateApprove(){
    if (!currentSubscriptionId){
      showStatus('Nenhuma cobrança ativa. Clique em Gerar PIX primeiro.', 'warning');
      return;
    }
    try{
      showLoading('Simulando pagamento (sandbox)...');
      const url = `${window.URLADM}payment/devApprove?subscription_id=${encodeURIComponent(currentSubscriptionId)}`;
      const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      const data = await res.json().catch(()=>null);
      if (!res.ok || !data || data.success !== true){
        showStatus('Falha ao simular aprovação. Verifique os logs.', 'danger');
        return;
      }
      showStatus('Pagamento simulado como aprovado! Verificando status...', 'success');
      setTimeout(checkPaymentStatus, 800);
    }catch(e){ showStatus('Erro ao simular aprovação.', 'danger'); }
  }
  async function createPayment(){
    console.log('[payment_v2] createPayment called');
    const planType = (new URLSearchParams(location.search).get('plan')) || 'basic';
    const btn = byId('generatePaymentBtn');
    try{
      if (btn){ btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gerando PIX...'; }
      showLoading('Gerando pagamento...');
      const body = { plano_id: getPlanIdByType(planType), period: '6_meses', plan_type: planType };
      const res = await fetch(`${window.URLADM}payment/create`, { method:'POST', headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}, body: JSON.stringify(body) });
      if(!res.ok){ const txt = await res.text().catch(()=> ''); throw new Error(txt||`HTTP ${res.status}`); }
      const ct = res.headers.get('content-type')||''; if (!ct.includes('application/json')){ throw new Error('Resposta inválida do servidor'); }
      const data = await res.json();
      if(!data.success){ throw new Error(data.message || 'Falha ao criar pagamento'); }
      currentSubscriptionId = data.subscription_id || currentSubscriptionId;
      // UI
      if (data.qr_code_base64 && byId('qrCodeImage')){
        byId('qrCodeImage').src = `data:image/png;base64,${data.qr_code_base64}`;
        byId('qrCodeSection').style.display='block';
      }
      if (data.pix_copy_paste && byId('pixCode')){
        byId('pixCode').value = data.pix_copy_paste;
        byId('pixCodeSection').style.display='block';
      }
      const checkBtn = byId('checkPaymentBtn'); const newBtn = byId('newPaymentBtn');
      if (checkBtn) checkBtn.style.display='inline-block';
      if (newBtn) newBtn.style.display='inline-block';
      // Exibir botão de simulação em sandbox
      try {
        const root = document.getElementById('paymentContent');
        const isSandbox = root && root.getAttribute('data-is-sandbox') === '1';
        const simBtn = byId('simulateApproveBtn');
        if (isSandbox && simBtn) simBtn.style.display = 'inline-block';
      } catch(_) {}
      showStatus('Aguardando pagamento...', 'info');
      startPaymentCheck();
    }catch(err){
      console.error('createPayment error', err);
      showStatus('Erro ao gerar pagamento. Tente novamente.', 'danger');
      if (btn){ btn.disabled = false; btn.innerHTML = '<i class="fas fa-qrcode me-2"></i> Gerar PIX'; }
    }
  }
  async function checkPaymentStatus(){
    if(!currentSubscriptionId){
      showStatus('Nenhuma cobrança ativa para verificar. Clique em Gerar PIX.', 'warning');
      return;
    }
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
    const el = byId('pixCode');
    if(!el) return;
    const text = el.value || '';
    if(navigator.clipboard){ navigator.clipboard.writeText(text).then(()=>console.log('PIX copiado')).catch(()=>fallback()); }
    else { fallback(); }
    function fallback(){ try{ el.select(); document.execCommand('copy'); }catch(e){} }
  }
  function newPayment(){
    if(!confirm('Deseja gerar um novo pagamento? O pagamento anterior será cancelado.')) return;
    stopPaymentCheck();
    currentSubscriptionId = null;
    const qr = byId('qrCodeSection'); const px = byId('pixCodeSection');
    if (qr) qr.style.display='none';
    if (px) px.style.display='none';
    const btn = byId('generatePaymentBtn');
    if (btn){ btn.disabled = false; btn.innerHTML = '<i class="fas fa-qrcode me-2"></i> Gerar PIX'; }
    showStatus('Pronto para pagar! Clique em Gerar PIX.', 'info');
  }

  function bind(){
    console.log('[payment_v2] binding buttons');
    const gen = byId('generatePaymentBtn');
    if (gen){ gen.addEventListener('click', createPayment); }
    const cp = byId('copyPixCode'); if (cp){ cp.addEventListener('click', copyPix); }
    const chk = byId('checkPaymentBtn'); if (chk){ chk.addEventListener('click', checkPaymentStatus); }
    const nw = byId('newPaymentBtn'); if (nw){ nw.addEventListener('click', newPayment); }
    const sim = byId('simulateApproveBtn'); if (sim){ sim.addEventListener('click', simulateApprove); }
  }

  function init(){
    console.log('[payment_v2] init');
    // Definir subscription inicial se presente via dataset
    try {
      const el = document.getElementById('paymentContent');
      const initialSubId = el ? (el.getAttribute('data-subscription-id')||null) : null;
      const initialStatus = el ? (el.getAttribute('data-subscription-status')||null) : null;
      const isSandbox = el ? (el.getAttribute('data-is-sandbox') === '1') : false;
      if (initialSubId){ currentSubscriptionId = parseInt(initialSubId, 10) || null; }
      // Mostrar botão de simulação imediatamente em sandbox
      const simBtn = byId('simulateApproveBtn'); if (simBtn && isSandbox) simBtn.style.display='inline-block';
      if (currentSubscriptionId && initialStatus === 'pending'){
        const checkBtn = byId('checkPaymentBtn'); if (checkBtn) checkBtn.style.display='inline-block';
        showStatus('Aguardando pagamento...', 'info');
        startPaymentCheck();
        return;
      }
      showStatus('Pronto para pagar! Clique em Gerar PIX.', 'info');
    } catch(e) { showStatus('Pronto para pagar! Clique em Gerar PIX.', 'info'); }
    bind();
  }

  // Expor inicializador para ser chamado pelo SPA após injetar HTML
  // Delegação global para capturar clique cedo mesmo se bind ainda não ocorreu
  document.addEventListener('click', function(e){
    const t = e.target;
    if (t && t.id === 'generatePaymentBtn') {
      // Evitar duplo bind: se já tem listener normal, apenas loga
      console.log('[payment_v2] delegated click on #generatePaymentBtn');
    }
  }, { capture: false });

  window.initializePaymentV2 = function(){ try { bind(); init(); } catch(e){ console.warn('initializePaymentV2 falhou', e); } };
})();
