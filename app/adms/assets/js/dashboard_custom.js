// Versão 36 - Adicionada funcionalidade de pausar/ativar anúncio no dashboard do usuário.
console.info("dashboard_custom.js (Versão 36) carregado. Configurando navegação SPA e funcionalidades adicionais.");

// Objeto global para armazenar todas as funcionalidades SPA
window.SpaUtils = window.SpaUtils || {};

// Handlers: Aprovar/Reprovar/Ativar/Desativar anúncio (admin)
window.setupAdminAnuncioActions = function() {
    try {
        // Se o script anuncio-admin.js já define performAdminAction, não fazer binding duplicado
        if (typeof window.performAdminAction === 'function') {
            console.info('AdminAnuncioActions: skipping custom bindings because performAdminAction is present.');
            return;
        }
        const bind = (id, endpoint, payloadExtra = {}) => {
            const btn = document.getElementById(id);
            if (!btn) return;
            if (btn._bound) return;
            btn._bound = true;
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                // Fechar qualquer modal aberto e remover backdrops residuais
                try {
                    if (window.bootstrap) {
                        document.querySelectorAll('.modal.show').forEach((m) => {
                            try { const inst = window.bootstrap.Modal.getInstance(m) || new window.bootstrap.Modal(m); inst.hide(); } catch(_){}
                        });
                    }
                    document.querySelectorAll('.modal-backdrop').forEach((bd) => { try { bd.remove(); } catch(_){} });
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                } catch(_){}
                const anuncioId = btn.getAttribute('data-anuncio-id');
                const anuncianteUserId = btn.getAttribute('data-anunciante-user-id');
                if (!anuncioId) { console.warn(id,'sem anuncio_id'); return; }
                try {
                    const resp = await fetch(`${window.URLADM}admin-anuncios/${endpoint}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(Object.assign({ anuncio_id: anuncioId, anunciante_user_id: anuncianteUserId }, payloadExtra))
                    });
                    const ct = resp.headers.get('content-type') || '';
                    let data = null;
                    if (ct.includes('application/json')) {
                        data = await resp.json().catch(() => null);
                    } else {
                        const text = await resp.text().catch(() => '');
                        console.warn('Admin action non-JSON response:', text);
                    }
                    if (resp.ok && data && data.success) {
                        if (typeof window.showFeedbackModal === 'function') {
                            window.showFeedbackModal('success', data.message || 'Ação realizada com sucesso.', 'Sucesso!', 1500);
                            setTimeout(() => { window.location.reload(); }, 1200);
                        } else {
                            alert(data.message || 'Ação realizada com sucesso.');
                            window.location.reload();
                        }
                    } else {
                        const msg = (data && data.message)
                            ? data.message
                            : `Falha (${resp.status}) - Resposta inesperada do servidor`;
                        if (typeof window.showFeedbackModal === 'function') {
                            window.showFeedbackModal('error', msg, 'Erro');
                        } else {
                            alert(msg);
                        }
                    }
                } catch (err) {
                    console.error('Erro na ação admin', endpoint, err);
                    if (typeof window.showFeedbackModal === 'function') {
                        window.showFeedbackModal('error', 'Erro de rede ao executar ação.', 'Erro');
                    } else {
                        alert('Erro de rede ao executar ação.');
                    }
                }
            });
        };

        bind('btnApproveAnuncio', 'approveAnuncio');
        bind('btnRejectAnuncio', 'rejectAnuncio');
        bind('btnActivateAnuncio', 'activateAnuncio');
        bind('btnDeactivateAnuncio', 'deactivateAnuncio');
    } catch (e) { console.warn('setupAdminAnuncioActions exception', e); }
};

// (REMOVIDO) Fix de layout SPA foi substituído por CSS estático para evitar duplo deslocamento

// Atualiza o CTA do card "Crie seu primeiro anúncio" (texto, cor e bloqueio) conforme plano/pagamento
window.updateCreateAnnouncementCardCTA = function() {
    try {
        const userPlan = document.body.dataset.userPlan || 'free';
        const paymentStatus = document.body.dataset.paymentStatus || 'pending';
        const card = document.querySelector('.create-announcement-card');
        if (!card) return;
        const alertWarn = card.querySelector('.alert.alert-warning');
        const payBtn = card.querySelector('a[href*="pagamento?plan="]');
        const createBtn = Array.from(card.querySelectorAll('a.btn, button.btn'))
            .find(b => b.textContent.toLowerCase().includes('criar anúncio'));

        const canCreate = (userPlan === 'free') || ((userPlan === 'basic' || userPlan === 'premium') && paymentStatus === 'paid');

        if (canCreate) {
            if (alertWarn) alertWarn.classList.add('d-none');
            if (payBtn) payBtn.classList.add('d-none');
            if (createBtn) {
                createBtn.removeAttribute('disabled');
                createBtn.classList.remove('btn-light');
                createBtn.classList.add('btn-primary');
                // Ajustar texto
                const txt = createBtn.textContent.trim().toLowerCase();
                if (txt.includes('bloqueado')) {
                    createBtn.innerHTML = '<i class="fas fa-rocket me-2"></i>Criar Anúncio Agora';
                }
                // Garantir link correto
                if (createBtn.tagName === 'A') {
                    createBtn.href = `${window.URLADM}anuncio/index`;
                    createBtn.setAttribute('data-spa', 'true');
                }
            }
        } else {
            // Pendente: mostrar aviso e manter bloqueado
            if (alertWarn) alertWarn.classList.remove('d-none');
            if (payBtn) payBtn.classList.remove('d-none');
            if (createBtn) {
                if (createBtn.tagName === 'BUTTON') {
                    createBtn.setAttribute('disabled', 'disabled');
                }
                createBtn.classList.remove('btn-primary');
                if (!createBtn.classList.contains('btn-light')) createBtn.classList.add('btn-light');
                // Ajustar texto
                createBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Criar Anúncio (Bloqueado)';
            }
        }
    } catch (e) { console.warn('updateCreateAnnouncementCardCTA error', e); }
};

// Handler global: confirmação e navegação para mudança de plano
window.confirmChangePlan = async function(planType) {
    try {
        if (!planType) return;
        const modalEl = document.getElementById('changePlanModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            const inst = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
            inst.hide();
        }
        const url = `${window.URLADM}pagamento?plan=${encodeURIComponent(planType)}`;
        const pagePath = getPagePathFromUrl(url);
        await window.SpaUtils.loadContent(url, pagePath);
    } catch (e) {
        console.warn('confirmChangePlan falhou, redirecionando padrão', e);
        window.location.href = `${window.URLADM}pagamento?plan=${encodeURIComponent(planType)}`;
    }
};

// Atualiza o Topbar (nome/foto/datasets) a partir do servidor
window.refreshTopbarFromServer = async function() {
    try {
        console.log('[Topbar] Fetching current user info...');
        const url = `${window.URLADM}perfil/getUserInfo`;
        const resp = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!resp.ok) {
            console.warn('[Topbar] getUserInfo HTTP not ok:', resp.status, resp.statusText);
            return;
        }
        const data = await resp.json();
        console.log('[Topbar] getUserInfo JSON:', data);
        if (!data || !data.success || !data.user) return;
        const { nome, foto } = data.user;
        const nameEl = document.getElementById('topbar-user-name');
        if (nameEl && nome) {
            if (nameEl.textContent !== nome) {
                console.log('[Topbar] Updating name to:', nome);
            }
            nameEl.textContent = nome;
        }
        // Atualizar datasets do body
        if (document && document.body && document.body.dataset) {
            if (nome) document.body.dataset.userName = nome;
        }
        // Atualizar foto com bust de cache opcional
        const imgEl = document.getElementById('topbar-user-photo');
        if (imgEl && foto) {
            const base = foto.startsWith('http') ? foto : `${window.URLADM}assets/images/users/${foto}`;
            // Se a src já aponta para o mesmo arquivo, apenas atualize o alt e opcionalmente force refresh
            imgEl.alt = nome || imgEl.alt || '';
            const urlNoQuery = (imgEl.src || '').split('?')[0];
            const nextSrc = `${base}?t=${Date.now()}`;
            if (imgEl.src !== nextSrc) {
                console.log('[Topbar] Updating photo src to:', nextSrc);
                imgEl.src = nextSrc;
            }
        }
    } catch (e) {
        console.warn('[Topbar] getUserInfo error:', e);
    }
};

// Mapeamento de caminhos de página para scripts específicos a serem carregados
const pageScripts = {
    'dashboard/index': 'dashboard_anuncios.js',
    'perfil/index': 'perfil.js',
    'anuncio': 'anuncio-simple.js',  // Adicionado para path 'anuncio'
    'anuncio/index': 'anuncio-simple.js',
    'anuncio/anuncio': 'anuncio-simple.js',
    'anuncio/editarAnuncio': 'anuncio-simple.js',
    'admin-users': 'admin-users.js',  // Adicionado para admin de usuários
    'pagamento': 'pagamento.js',  // Adicionado para página de pagamento
    'pix': 'pagamento.js',  // URL amigável para PIX
    'pagar': 'pagamento.js',  // URL amigável para pagar
    'checkout': 'pagamento.js',  // URL amigável para checkout
    'assinatura': 'pagamento.js',  // URL amigável para assinatura
    'pix/basic': 'pagamento.js',  // URL amigável para PIX básico
    'pix/premium': 'pagamento.js',  // URL amigável para PIX premium
    'pix/enterprise': 'pagamento.js',  // URL amigável para PIX enterprise
    'pagar/basic': 'pagamento.js',  // URL amigável para pagar básico
    'pagar/premium': 'pagamento.js',  // URL amigável para pagar premium
    'pagar/enterprise': 'pagamento.js',  // URL amigável para pagar enterprise
    'checkout/basic': 'pagamento.js',  // URL amigável para checkout básico
    'checkout/premium': 'pagamento.js',  // URL amigável para checkout premium
    'checkout/enterprise': 'pagamento.js',  // URL amigável para checkout enterprise
    'assinatura/basic': 'pagamento.js',  // URL amigável para assinatura básica
    'assinatura/premium': 'pagamento.js',  // URL amigável para assinatura premium
    'assinatura/enterprise': 'pagamento.js',  // URL amigável para assinatura enterprise
    // 'anuncio/visualizarAnuncio': 'visualizar_anuncio.js' // Removido - agora redireciona para STS
};

// Mapeamento de pagePath para funções de inicialização
const pageInitializers = {
    'dashboard/index': 'initializeAnunciosListPage',
    'perfil/index': 'initializePerfilPage',
    'anuncio': 'initializeAnuncioFormPage',  // Adicionado para path 'anuncio'
    'anuncio/index': 'initializeAnuncioFormPage',
    'anuncio/anuncio': 'initializeAnuncioFormPage',
    'anuncio/editarAnuncio': 'initializeAnuncioFormPage',
    'admin-users': 'initializeAdminUsersPage',  // Adicionado para admin de usuários
    'pagamento': 'initializePaymentPage',  // Adicionado para página de pagamento
    'pix': 'initializePaymentPage',  // URL amigável para PIX
    'pagar': 'initializePaymentPage',  // URL amigável para pagar
    'checkout': 'initializePaymentPage',  // URL amigável para checkout
    'assinatura': 'initializePaymentPage',  // URL amigável para assinatura
    'pix/basic': 'initializePaymentPage',  // URL amigável para PIX básico
    'pix/premium': 'initializePaymentPage',  // URL amigável para PIX premium
    'pix/enterprise': 'initializePaymentPage',  // URL amigável para PIX enterprise
    'pagar/basic': 'initializePaymentPage',  // URL amigável para pagar básico
    'pagar/premium': 'initializePaymentPage',  // URL amigável para pagar premium
    'pagar/enterprise': 'initializePaymentPage',  // URL amigável para pagar enterprise
    'checkout/basic': 'initializePaymentPage',  // URL amigável para checkout básico
    'checkout/premium': 'initializePaymentPage',  // URL amigável para checkout premium
    'checkout/enterprise': 'initializePaymentPage',  // URL amigável para checkout enterprise
    'assinatura/basic': 'initializePaymentPage',  // URL amigável para assinatura básica
    'assinatura/premium': 'initializePaymentPage',  // URL amigável para assinatura premium
    'assinatura/enterprise': 'initializePaymentPage',  // URL amigável para assinatura enterprise
    'admin-payments': 'initializeAdminPaymentsPage',  // Dashboard de pagamentos
    'adminpayments': 'initializeAdminPaymentsPage',  // Dashboard de pagamentos
    'gerenciar-pagamentos': 'initializeAdminPaymentsPage',  // Dashboard de pagamentos
    'pagamentos': 'initializeAdminPaymentsPage',  // Dashboard de pagamentos
    // 'anuncio/visualizarAnuncio': 'initializeVisualizarAnuncioPage', // Removido - agora redireciona para STS
};

// Cache para scripts que já foram carregados
const loadedScripts = new Set();

/**
 * Adiciona ou remove a classe 'd-none' para mostrar/esconder um elemento.
 * @param {HTMLElement} element O elemento a ser manipulado.
 * @param {boolean} show Se true, remove 'd-none'. Se false, adiciona 'd-none'.
 */
function toggleElementVisibility(element, show) {
    if (element) {
        if (show) {
            element.classList.remove('d-none');
        } else {
            element.classList.add('d-none');
        }
    }
}

// Handler global para abrir o modal grande de exclusão de conta
window.handleDeleteAccountClick = function(event) {
    try {
        if (event) event.preventDefault();
        const modalEl = document.getElementById('deleteAccountModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            // Fechar qualquer modal simples aberto
            const simple = document.getElementById('confirmDeleteAccountModal');
            if (simple) {
                const inst = window.bootstrap.Modal.getInstance(simple);
                if (inst) inst.hide();
            }
            const modal = new window.bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            modal.show();
            console.log('INFO JS: deleteAccountModal exibido.');
        } else {
            console.error('ERRO JS: Elemento #deleteAccountModal ou Bootstrap.Modal não disponível.');
        }
    } catch (e) {
        console.error('ERRO JS: handleDeleteAccountClick falhou:', e);
    }
};

/**
 * Atualiza os links da sidebar de acordo com o status do anúncio do usuário.
 * Essa função será chamada em cada carregamento de página (full ou via SPA).
 */
window.updateAnuncioSidebarLinks = function() {
    const userRole = document.body.dataset.userRole;
    const hasAnuncio = document.body.dataset.hasAnuncio === 'true';
    const userPlan = document.body.dataset.userPlan || 'free';
    const paymentStatus = document.body.dataset.paymentStatus || 'pending';
    
    console.log('DEBUG JS: updateAnuncioSidebarLinks - userRole:', userRole, '| hasAnuncio:', hasAnuncio, '| userPlan:', userPlan, '| paymentStatus:', paymentStatus);

    const criarAnuncioLink = document.getElementById('navCriarAnuncio');
    const editarAnuncioLink = document.getElementById('navEditarAnuncio');
    // const visualizarAnuncioLink = document.getElementById('navVisualizarAnuncio'); // Removido - agora redireciona para STS
    const pausarAnuncioLink = document.getElementById('navPausarAnuncio');
    
    const anuncioStatus = document.body.dataset.anuncioStatus;
    console.log('DEBUG JS: updateAnuncioSidebarLinks - anuncioStatus:', anuncioStatus);

    if (userRole === 'administrador') {
        // Se for administrador, esconde todos os links de usuário normal
        document.querySelectorAll('.user-only-link').forEach(link => link.classList.add('d-none'));
    } else { // Usuário normal
        // Mostra todos os links user-only inicialmente para a lógica
        document.querySelectorAll('.user-only-link').forEach(link => link.classList.remove('d-none'));
        
        // Verificar se o usuário pode criar anúncios baseado no plano e pagamento
        // DB usa payment_status: 'pending'|'paid'|'failed'
        const canCreateAnuncio = (userPlan === 'free') || ((userPlan === 'basic' || userPlan === 'premium') && (paymentStatus === 'paid'));
        
        if (hasAnuncio) {
            // Se o usuário TEM um anúncio, mostre as opções de gerenciar e esconda a de criar
            if (criarAnuncioLink) toggleElementVisibility(criarAnuncioLink, false);
            if (editarAnuncioLink) toggleElementVisibility(editarAnuncioLink, true);
            // if (visualizarAnuncioLink) toggleElementVisibility(visualizarAnuncioLink, true); // Removido - agora redireciona para STS

            // Adicionado: Lógica para o botão Pausar/Ativar com base no status do anúncio
            if (pausarAnuncioLink) {
                const pauseIcon = pausarAnuncioLink.querySelector('.fa-solid');
                const pauseText = pausarAnuncioLink.querySelector('span');
                if (anuncioStatus === 'active') {
                    toggleElementVisibility(pausarAnuncioLink, true);
                    if (pauseIcon) {
                        pauseIcon.classList.remove('fa-play');
                        pauseIcon.classList.add('fa-pause');
                    }
                    if (pauseText) {
                        pauseText.textContent = 'Pausar Anúncio';
                    }
                } else if (anuncioStatus === 'pausado') {
                    toggleElementVisibility(pausarAnuncioLink, true);
                    if (pauseIcon) {
                        pauseIcon.classList.remove('fa-pause');
                        pauseIcon.classList.add('fa-play');
                    }
                    if (pauseText) {
                        pauseText.textContent = 'Ativar Anúncio';
                    }
                } else {
                    // Para status 'pending', 'rejected', 'deleted' ou qualquer outro, esconde o botão de pausar/ativar
                    toggleElementVisibility(pausarAnuncioLink, false);
                }
            }
        } else {
            // Se o usuário NÃO TEM um anúncio, verificar se pode criar baseado no plano e pagamento
            if (criarAnuncioLink) {
                if (canCreateAnuncio) {
                    toggleElementVisibility(criarAnuncioLink, true);
                    criarAnuncioLink.style.pointerEvents = '';
                    criarAnuncioLink.style.opacity = '';
                    criarAnuncioLink.removeAttribute('title');
                } else {
                    // Esconder link até estar elegível (não apenas desabilitar)
                    toggleElementVisibility(criarAnuncioLink, false);
                }
            }
            if (editarAnuncioLink) toggleElementVisibility(editarAnuncioLink, false);
            // if (visualizarAnuncioLink) toggleElementVisibility(visualizarAnuncioLink, false); // Removido - agora redireciona para STS
            if (pausarAnuncioLink) toggleElementVisibility(pausarAnuncioLink, false);
        }
    }
    
    // Atualiza CTA do card "Crie seu primeiro anúncio" se existir na página
    if (typeof window.updateCreateAnnouncementCardCTA === 'function') {
        try { window.updateCreateAnnouncementCardCTA(); } catch(e){}
    }

    // Reconfigura os botões da dashboard após atualizar a sidebar
    window.setupDashboardButtons();
};


/**
 * Normaliza uma URL completa para um pagePath limpo e roteável.
 * Ex: 'http://localhost/adm/anuncio/editarAnuncio?id=123' -> 'anuncio/editarAnuncio'
 * @param {string} fullUrl O URL completo da página.
 * @returns {string} O caminho da página limpo para roteamento.
 */
function getPagePathFromUrl(fullUrl) {
    const baseUrl = window.URLADM;
    let pagePath = fullUrl.replace(baseUrl, '');

    const queryParamIndex = pagePath.indexOf('?');
    if (queryParamIndex !== -1) {
        pagePath = pagePath.substring(0, queryParamIndex);
    }

    if (pagePath.endsWith('/')) {
        pagePath = pagePath.slice(0, -1);
    }

    if (pagePath === 'dashboard' || pagePath === '') {
        pagePath = 'dashboard/index';
    }
    
    // Normalizar path 'anuncio' para 'anuncio/index'
    if (pagePath === 'anuncio') {
        pagePath = 'anuncio/index';
    }
    
    // Normalizar path 'admin-users' para 'admin-users'
    if (pagePath === 'admin-users') {
        pagePath = 'admin-users';
    }

    return pagePath;
}

/**
 * Carrega um script JavaScript dinamicamente, evitando carregamento duplicado.
 * @param {string} scriptUrl O URL completo do script.
 * @returns {Promise<void>} Uma promessa que resolve quando o script é carregado ou rejeita em caso de erro.
 */
window.SpaUtils.loadScript = function(scriptUrl) {
    return new Promise((resolve, reject) => {
        console.log('DEBUG JS: loadScript - Iniciando carregamento:', scriptUrl);
        console.log('DEBUG JS: loadScript - Scripts já carregados:', Array.from(loadedScripts));
        
        // Log específico para anuncio-simple.js
        if (scriptUrl.includes('anuncio-simple.js')) {
            console.log('🔧 DEBUG JS: Carregando anuncio-simple.js...');
        }
        
        // Verificar se o script já foi carregado (sem timestamp)
        const baseScriptUrl = scriptUrl.split('?')[0];
        const isAlreadyLoaded = Array.from(loadedScripts).some(loaded => loaded.split('?')[0] === baseScriptUrl);
        
        if (isAlreadyLoaded) {
            console.info(`INFO JS: loadScript - Script já carregado: ${baseScriptUrl}.`);
            resolve();
            return;
        }

        // Verificar se o script já existe no DOM
        const scriptFileName = baseScriptUrl.split('/').pop();
        const existingScript = document.querySelector(`script[src*="${scriptFileName}"]`);
        if (existingScript) {
            console.info(`INFO JS: loadScript - Script já existe no DOM: ${baseScriptUrl}.`);
            loadedScripts.add(scriptUrl);
            resolve();
            return;
        }

        // Verificar se o script está sendo carregado atualmente
        if (window.loadingScripts && window.loadingScripts.has(baseScriptUrl)) {
            console.info(`INFO JS: loadScript - Script já está sendo carregado: ${baseScriptUrl}.`);
            resolve();
            return;
        }

        // Inicializar controle de scripts sendo carregados
        if (!window.loadingScripts) {
            window.loadingScripts = new Set();
        }
        window.loadingScripts.add(baseScriptUrl);

        const script = document.createElement('script');
        script.src = scriptUrl;
        script.onload = () => {
            console.info('INFO JS: loadScript - Script carregado com sucesso:', scriptUrl);
            loadedScripts.add(scriptUrl);
            window.loadingScripts.delete(baseScriptUrl);
            console.log('DEBUG JS: loadScript - Scripts carregados após:', Array.from(loadedScripts));
            
            // Verifica se a função específica foi carregada
            if (scriptUrl.includes('anuncio-simple.js')) {
                console.log('🔧 DEBUG JS: anuncio-simple.js carregado!');
                console.log('🔧 DEBUG JS: Verificando se initializeAnuncioFormPage foi carregada:', typeof window.initializeAnuncioFormPage);
                console.log('🔧 DEBUG JS: Verificando se AnuncioCore foi carregado:', typeof window.AnuncioCore);
            }
            
            resolve();
        };
        script.onerror = () => {
            console.error('ERRO JS: loadScript - Falha ao carregar script:', scriptUrl);
            window.loadingScripts.delete(baseScriptUrl);
            reject(new Error(`Falha ao carregar script: ${scriptUrl}`));
        };
        document.body.appendChild(script);
        console.log('DEBUG JS: loadScript - Adicionando script tag ao body:', scriptUrl);
    });
};

// Handler: botão "Subir anúncio" (admin) na view de anúncio
window.setupAdminBoostButton = function() {
    try {
        const btn = document.getElementById('btnAdminBoostAnuncio');
        if (!btn) return;
        if (btn._bound) return; // evitar duplo bind
        btn._bound = true;
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            // Fechar qualquer modal aberto e remover backdrops residuais para evitar overlay travado
            try {
                if (window.bootstrap) {
                    document.querySelectorAll('.modal.show').forEach((m) => {
                        try { const inst = window.bootstrap.Modal.getInstance(m) || new window.bootstrap.Modal(m); inst.hide(); } catch(_){ }
                    });
                }
                document.querySelectorAll('.modal-backdrop').forEach((bd) => { try { bd.remove(); } catch(_){ } });
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            } catch(_){ }
            const anuncioId = btn.getAttribute('data-anuncio-id');
            if (!anuncioId) { console.warn('btnAdminBoostAnuncio sem anuncio_id'); return; }
            try {
                const resp = await fetch(`${window.URLADM}admin-anuncios/boostAnuncio`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ anuncio_id: anuncioId })
                });
                const ct = resp.headers.get('content-type') || '';
                let data = null;
                if (ct.includes('application/json')) {
                    data = await resp.json().catch(() => null);
                } else {
                    const text = await resp.text().catch(() => '');
                    console.warn('Boost action non-JSON response:', text);
                }
                if (resp.ok && data && data.success) {
                    if (typeof window.showFeedbackModal === 'function') {
                        window.showFeedbackModal('success', data.message || 'Anúncio subido com sucesso.', 'Sucesso!', 1500);
                        setTimeout(() => { window.location.reload(); }, 1200);
                    } else {
                        alert(data.message || 'Anúncio subido com sucesso.');
                        window.location.reload();
                    }
                } else {
                    const msg = (data && data.message) ? data.message : `Falha (${resp.status}) - Resposta inesperada do servidor`;
                    if (typeof window.showFeedbackModal === 'function') {
                        window.showFeedbackModal('error', msg, 'Erro');
                    } else {
                        alert(msg);
                    }
                }
            } catch (err) {
                console.error('Erro ao subir anúncio (admin):', err);
                if (typeof window.showFeedbackModal === 'function') {
                    window.showFeedbackModal('error', 'Erro de rede ao subir anúncio.', 'Erro');
                } else {
                    alert('Erro de rede ao subir anúncio.');
                }
            }
        });
    } catch (e) { console.warn('setupAdminBoostButton exception', e); }
};

/**
 * Chama uma função de inicialização de página se ela existir no escopo global.
 * @param {string} initializerFunctionName O nome da função de inicialização.
 * @param {string} fullUrlOrPagePath O URL completo da página (com query params) OU o pagePath limpo.
 * @param {object|null} [initialData=null] Dados JSON iniciais para a página.
 */
window.SpaUtils.callPageInitializer = function(initializerFunctionName, fullUrlOrPagePath, initialData = null) {
    console.log('🔧 DEBUG JS: callPageInitializer - Verificando função:', initializerFunctionName);
    console.log('🔧 DEBUG JS: callPageInitializer - fullUrlOrPagePath:', fullUrlOrPagePath);
    console.log('🔧 DEBUG JS: callPageInitializer - initialData:', initialData);
    console.log('🔧 DEBUG JS: callPageInitializer - Função existe?', typeof window[initializerFunctionName]);
    console.log('🔧 DEBUG JS: callPageInitializer - Função é function?', typeof window[initializerFunctionName] === 'function');
    
    if (typeof window[initializerFunctionName] === 'function') {
        // Resetar flag de inicialização para formulários
        if (initializerFunctionName === 'setupCompleteForm' && typeof window.resetFormInitialization === 'function') {
            window.resetFormInitialization();
        }
        
        console.info('INFO JS: callPageInitializer - Função', initializerFunctionName, 'chamada com sucesso.');
        window[initializerFunctionName](fullUrlOrPagePath, initialData);
    } else {
        console.warn('AVISO JS: callPageInitializer - Função de inicialização', initializerFunctionName, 'não encontrada para o caminho', fullUrlOrPagePath);
        console.log('DEBUG JS: callPageInitializer - Funções disponíveis no window:', Object.keys(window).filter(key => key.includes('initialize')));
    }
};

/**
 * Anexa listeners de submit a todos os formulários com data-spa="true".
 * @returns {void}
 */
window.SpaUtils.setupSpaForms = function() {
    const spaForms = document.querySelectorAll('form[data-spa="true"]');
    console.log(`DEBUG JS: setupSpaForms - Encontrados ${spaForms.length} formulários SPA.`);
    spaForms.forEach(form => {
        form.removeEventListener('submit', handleSpaFormSubmit);
        form.addEventListener('submit', handleSpaFormSubmit);
    });
};

/**
 * Handler para a submissão de formulários SPA.
 * @param {Event} event O evento de submissão.
 * @returns {Promise<void>}
 */
async function handleSpaFormSubmit(event) {
    const form = event.target;
    event.preventDefault();

    console.info('INFO JS: handleSpaFormSubmit - Submissão de formulário SPA detectada.');

    const fullUrl = form.action;
    const cleanPagePath = getPagePathFromUrl(fullUrl);

    try {
        const formData = new FormData(form);
        const response = await fetch(fullUrl, {
            method: form.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('ERRO JS: Form submit - Resposta de rede não OK:', response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            console.log('INFO JS: Form submit - Resposta JSON recebida:', data);
        
        // DEBUG: Mostrar erros específicos se houver
        if (data.errors) {
            console.log('ERRO JS: Erros específicos do servidor:', data.errors);
            Object.keys(data.errors).forEach(field => {
                console.log(`ERRO JS: Campo ${field}: ${data.errors[field]}`);
            });
            
            // Debug específico para preços
            if (data.errors.debug_precos) {
                console.log('🔍 DEBUG PREÇOS PHP:', data.errors.debug_precos);
                console.log('🔍 DEBUG PREÇOS PHP - Detalhes:');
                console.log('  - price_15min_original:', data.errors.debug_precos.price_15min_original);
                console.log('  - price_30min_original:', data.errors.debug_precos.price_30min_original);
                console.log('  - price_1h_original:', data.errors.debug_precos.price_1h_original);
                console.log('  - price15_processado:', data.errors.debug_precos.price15_processado);
                console.log('  - price30_processado:', data.errors.debug_precos.price30_processado);
                console.log('  - price1h_processado:', data.errors.debug_precos.price1h_processado);
                console.log('  - todos_menor_igual_zero:', data.errors.debug_precos.todos_menor_igual_zero);
            }
        }
            
            // VERIFICAR AQUI A RESPOSTA PARA ATUALIZAR A SIDEBAR
            if (data.success === true) {
                console.log('🎉 DEBUG JS: Form submit - Sucesso detectado! Processando resposta...');
                
                // Atualizar dados do body se fornecidos
                if (data.has_anuncio !== undefined) {
                    document.body.dataset.hasAnuncio = data.has_anuncio ? 'true' : 'false';
                }
                if (data.anuncio_status) {
                    document.body.dataset.anuncioStatus = data.anuncio_status;
                }
                if (data.anuncio_id) {
                    document.body.dataset.anuncioId = data.anuncio_id;
                }
                
                // Atualizar sidebar
                window.updateAnuncioSidebarLinks();
                
                // Mostrar modal de sucesso
                window.showFeedbackModal('success', data.message || 'Ação realizada com sucesso!', 'Sucesso!', 2000);
                
                console.log('🎉 DEBUG JS: Form submit - Modal de sucesso exibido!');
            } else if (data.html) {
                await window.SpaUtils.loadContent(fullUrl, cleanPagePath, data);
            } else {
                const initializerFunction = pageInitializers[cleanPagePath];
                if (initializerFunction) {
                    window.SpaUtils.callPageInitializer(initializerFunction, fullUrl, data);
                } else {
                    console.warn('AVISO JS: Form submit - Nenhuma função de inicialização definida para o caminho:', cleanPagePath);
                    if (data.success === true) {
                        window.showFeedbackModal('success', data.message || 'Ação realizada com sucesso!', 'Sucesso!');
                    }
                }
            }
        } else {
            await window.SpaUtils.loadContent(fullUrl, cleanPagePath, null);
        }
    } catch (error) {
        console.error('ERRO JS: Form submit - Erro ao processar submissão:', error);
        window.showFeedbackModal('error', `Não foi possível processar a requisição. Detalhes: ${error.message}`, 'Erro na Requisição');
    }
}


/**
 * Carrega o conteúdo de uma URL via AJAX e o injeta na área de conteúdo principal.
 * Também gerencia o carregamento de scripts específicos da página e a atualização da sidebar.
 * @param {string} url O URL completo do conteúdo a ser carregado (inclui query params).
 * @param {string} pagePath O caminho da página (ex: 'dashboard/index', 'anuncio/editarAnuncio').
 * @param {object|null} [initialData=null] Dados JSON iniciais para a página (se for uma resposta JSON).
 */
window.SpaUtils.loadContent = async function(url, pagePath, initialData = null) {
    // Bypass SPA para a rota de pagamento: redireciona full-page
    if (/\/adms\/pagamento/i.test(url) || /(^|\/)pagamento(\?|$)/i.test(url)) {
        console.log('SPA: Bypass para pagamento. Redirecionando full-page:', url);
        window.location.href = url;
        return;
    }
    console.log('INFO JS: loadContent - Iniciando carregamento de conteúdo para:', url);

    const contentArea = document.getElementById('dynamic-content');
    if (!contentArea) {
        console.error('ERRO JS: loadContent - Elemento #dynamic-content não encontrado.');
        window.showFeedbackModal('error', 'Erro interno: Área de conteúdo não encontrada.', 'Erro de Layout');
        return;
    }

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('ERRO JS: loadContent - Resposta de rede não OK:', response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            console.log('INFO JS: loadContent - Resposta é JSON. Processando dados.');
            if (data.html) {
                contentArea.innerHTML = data.html;
                console.log('INFO JS: loadContent - Conteúdo HTML do JSON injetado com sucesso.');
            } else {
                contentArea.innerHTML = '<div class="alert alert-info">Nenhum conteúdo HTML para exibir.</div>';
                console.warn('AVISO JS: loadContent - Resposta JSON não contém HTML para injetar.');
            }
            initialData = data;
        } else {
            const html = await response.text();
            contentArea.innerHTML = html;
            // Detectar Payment v2 e sinalizar para scripts legados
            try {
                const paymentV2El = contentArea.querySelector('#paymentContent[data-payment-v2="1"]');
                if (paymentV2El) {
                    window.PAYMENT_V2_ACTIVE = true;
                    document.documentElement.setAttribute('data-payment-v2', '1');
                    console.log('SPA: Payment v2 detectado. PAYMENT_V2_ACTIVE=true.');
                    // Carregar script v2 explicitamente (scripts inseridos via innerHTML não executam)
                    try {
                        const v2ScriptUrl = `${window.URLADM}assets/js/pagamento_v2.js?v=${Date.now()}`;
                        await window.SpaUtils.loadScript(v2ScriptUrl);
                        if (typeof window.initializePaymentV2 === 'function') {
                            window.initializePaymentV2();
                        }
                        // Acessibilidade: mover foco para o botão principal se existir
                        setTimeout(() => {
                            const gen = document.getElementById('generatePaymentBtn');
                            if (gen && typeof gen.focus === 'function') { try { gen.focus(); } catch(e){} }
                        }, 60);
                    } catch (e) {
                        console.warn('SPA: Falha ao carregar/rodar pagamento_v2.js', e);
                    }
                } else {
                    // Reset flag quando sair da página de pagamento v2
                    if (window.PAYMENT_V2_ACTIVE) {
                        delete window.PAYMENT_V2_ACTIVE;
                        document.documentElement.removeAttribute('data-payment-v2');
                        console.log('SPA: Payment v2 não detectado. Flag reset.');
                    }
                }
            } catch (e) { console.warn('SPA: Falha ao sinalizar payment v2', e); }
            console.log('INFO JS: loadContent - Resposta é HTML. Injetando conteúdo.');
        }

        console.log('INFO JS: Conteúdo dinâmico injetado com sucesso.');
        
        // CORREÇÃO: Reinicializar uploads após carregamento SPA se for página de anúncio
        if (pagePath.includes('anuncio')) {
            setTimeout(() => {
                if (window.AnuncioUploads && window.AnuncioUploads.reinit) {
                    console.log("🔄 SPA: Reinicializando uploads após carregamento de página de anúncio...");
                    window.AnuncioUploads.reinit();
                }
            }, 300);
        }

        if (typeof window.clearPageEvents === 'function') {
            window.clearPageEvents();
        }

        if (typeof window.setupInputMasks === 'function') {
            window.setupInputMasks();
        }
        if (typeof window.setupAutoDismissAlerts === 'function') {
            window.setupAutoDismissAlerts();
        }
        window.SpaUtils.setupSpaForms();
        
        // Reconfigura o event listener do link de exclusão de conta após carregar conteúdo dinâmico
        setupDeleteAccountLink();

        // Configura botões de ações do administrador, quando presentes
        if (typeof window.setupAdminBoostButton === 'function') {
            try { window.setupAdminBoostButton(); } catch(e) { console.warn('setupAdminBoostButton error', e); }
        }
        if (typeof window.setupAdminAnuncioActions === 'function') {
            try { window.setupAdminAnuncioActions(); } catch(e) { console.warn('setupAdminAnuncioActions error', e); }
        }

        const scriptToLoad = pageScripts[pagePath];
        const isPaymentV2 = !!document.querySelector('#paymentContent[data-payment-v2="1"]');
        if (scriptToLoad && !(isPaymentV2 && scriptToLoad === 'pagamento.js')) {
            const scriptUrl = `${window.URLADM}assets/js/${scriptToLoad}?v=${Date.now()}`;
            console.log('DEBUG JS: loadContent - Chamando loadScript para:', scriptUrl);
            await window.SpaUtils.loadScript(scriptUrl);
        } else {
            console.log('INFO JS: loadContent - Nenhum script específico para carregar (ou v2 ativo) para o caminho:', pagePath);
        }

        const initializerFunction = pageInitializers[pagePath];
        if (initializerFunction && !(isPaymentV2 && initializerFunction === 'initializePaymentPage')) {
            console.log('DEBUG JS: loadContent - Chamando callPageInitializer para:', pagePath);
            // Adiciona um pequeno delay para garantir que o script seja carregado
            setTimeout(() => {
                window.SpaUtils.callPageInitializer(initializerFunction, url, initialData);
                // Atualizar Topbar a partir do servidor (nome/foto/plano)
                if (typeof window.refreshTopbarFromServer === 'function') {
                    setTimeout(window.refreshTopbarFromServer, 80);
                }
                
                // Verificação adicional para perfil - garantir que o preview de foto funcione
                if (pagePath === 'perfil/index') {
                    console.log('🔍 DEBUG: Página de perfil carregada via SPA, verificando elementos...');
                    setTimeout(() => {
                        const fotoPreview = document.getElementById('fotoPreview');
                        const fotoInput = document.getElementById('fotoInput');
                        if (fotoPreview && fotoInput) {
                            console.log('🔍 DEBUG: Elementos de foto encontrados, configurando preview...');
                            if (typeof window.setupFotoPreview === 'function') {
                                window.setupFotoPreview();
                            }
                        }
                    }, 200);
                }
            }, 100);
        } else {
            console.warn('AVISO JS: loadContent - Nenhuma função de inicialização definida para o caminho:', pagePath);
        }

        history.pushState({ pagePath: pagePath, url: url }, '', url);

        window.updateAnuncioSidebarLinks();
        if (typeof window.refreshTopbarFromServer === 'function') {
            setTimeout(window.refreshTopbarFromServer, 60);
        }
        console.log('INFO JS: loadContent - Sidebar atualizada após loadContent.');

    } catch (error) {
        console.error('ERRO JS: loadContent - Erro ao carregar conteúdo:', error);
        contentArea.innerHTML = `<div class="alert alert-danger">Erro ao carregar a página: ${error.message}</div>`;
        window.showFeedbackModal('error', `Não foi possível carregar a página. Detalhes: ${error.message}`, 'Erro de Carregamento');
    }
};

// =================================================================================================
// LÓGICA DE NAVEGAÇÃO SPA (SINGLE PAGE APPLICATION)
// =================================================================================================

document.addEventListener('DOMContentLoaded', async () => {
    console.info("DOMContentLoaded disparado em dashboard_custom.js. Configurando navegação SPA.");

    // Atualizar Topbar imediatamente ao carregar
    if (typeof window.refreshTopbarFromServer === 'function') {
        window.refreshTopbarFromServer();
        // Atualizar quando a janela ganha foco (volta da aba)
        window.addEventListener('focus', () => {
            setTimeout(window.refreshTopbarFromServer, 50);
        });
        // Atualização periódica leve
        if (!window.__topbarRefreshTimer) {
            window.__topbarRefreshTimer = setInterval(() => {
                window.refreshTopbarFromServer();
            }, 30000); // 30s
        }
    }

    document.body.addEventListener('click', async (event) => {
        // Não interceptar cliques na página de admin de usuários
        if (window.location.pathname.includes('admin-users')) {
            return;
        }
        
        const link = event.target.closest('a[data-spa="true"]');
        if (link) {
            event.preventDefault();
            const fullUrl = link.href;
            const cleanPagePath = getPagePathFromUrl(fullUrl);

            console.info('INFO JS: DOMContentLoaded - Clique em link SPA detectado. Carregando conteúdo para:', fullUrl, '(pagePath limpo para roteamento:', cleanPagePath + ')');
            await window.SpaUtils.loadContent(fullUrl, cleanPagePath);

            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (mainContent) {
                    mainContent.classList.remove('sidebar-hidden');
                }
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            }
        }
    });

    window.SpaUtils.setupSpaForms();

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('active'); // Adiciona/remove classe active no botão
            mainContent.classList.toggle('sidebar-hidden');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
            // Reajustar posição do conteúdo de pagamento, se aplicável
            if (typeof window.adjustPaymentContentPosition === 'function') {
                setTimeout(window.adjustPaymentContentPosition, 60);
            }
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active'); // Remove classe active do botão
            mainContent.classList.remove('sidebar-hidden');
            sidebarOverlay.classList.remove('active');
            // Reajustar posição do conteúdo de pagamento, se aplicável
            if (typeof window.adjustPaymentContentPosition === 'function') {
                setTimeout(window.adjustPaymentContentPosition, 60);
            }
        });
    }

    window.addEventListener('popstate', async (event) => {
        if (event.state && event.state.url && event.state.pagePath) {
            console.log('INFO JS: popstate - Navegando para:', event.state.url);
            await window.SpaUtils.loadContent(event.state.url, event.state.pagePath);
        } else {
            // Verificar se estamos na página de admin de usuários
            const currentPath = window.location.pathname;
            if (currentPath.includes('admin-users')) {
                console.log('INFO JS: popstate - Permanecendo na página admin-users');
                return; // Não fazer nada, deixar a página funcionar normalmente
            }
            console.warn('AVISO JS: popstate - Estado não encontrado, recarregando dashboard.');
            await window.SpaUtils.loadContent(`${window.URLADM}dashboard`, 'dashboard/index');
        }
    });

    const initialUrl = window.location.href;
    const cleanInitialPagePath = getPagePathFromUrl(initialUrl);
    
    console.log('🔍 DEBUG JS: URL inicial:', initialUrl);
    console.log('🔍 DEBUG JS: PagePath limpo:', cleanInitialPagePath);

    // Verificar se há parâmetros SPA na URL
    const urlParams = new URLSearchParams(window.location.search);
    const spaPage = urlParams.get('spa');
    const anuncioId = urlParams.get('id');

    // Verificar se é uma página que precisa de carregamento via SPA
    if (cleanInitialPagePath === 'admin-users') {
        console.log('🔧 DEBUG JS: Página admin-users detectada - carregando via SPA');
        await window.SpaUtils.loadContent(initialUrl, cleanInitialPagePath);
    } else {
        // if (spaPage && spaPage === 'visualizarAnuncio' && anuncioId) { // Removido - agora redireciona para STS
        //     console.log('DEBUG JS: Parâmetro SPA detectado - Carregando visualizarAnuncio via SPA');
        //     const visualizarUrl = `${window.URLADM}anuncio/visualizarAnuncio?id=${anuncioId}`;
        //     await window.SpaUtils.loadContent(visualizarUrl, 'anuncio/visualizarAnuncio');
        //     
        //     // Limpar parâmetros da URL
        //     const newUrl = window.location.pathname;
        //     window.history.replaceState({}, '', newUrl);
        // } else {
        const scriptToLoadForInitial = pageScripts[cleanInitialPagePath];
        // Guard: se a página atual já contém payment v2 no HTML inicial, não carregar pagamento.js legado
        const isPaymentV2Initial = !!document.querySelector('#paymentContent[data-payment-v2="1"]');
        if (scriptToLoadForInitial && !(isPaymentV2Initial && scriptToLoadForInitial === 'pagamento.js')) {
            console.log('DEBUG JS: Script a carregar:', scriptToLoadForInitial);
            console.log('DEBUG JS: URL completa:', `${window.URLADM}assets/js/${scriptToLoadForInitial}`);
            try {
                await window.SpaUtils.loadScript(`${window.URLADM}assets/js/${scriptToLoadForInitial}`);
                console.log('DEBUG JS: Carga inicial - Script extra carregado para', cleanInitialPagePath);
            } catch (error) {
                console.error('ERRO JS: Carga inicial - Falha ao carregar script inicial:', error);
            }
        } else {
            console.log('DEBUG JS: Nenhum script específico encontrado para:', cleanInitialPagePath);
            }

        console.log('DEBUG JS: Carga inicial - Chamando callPageInitializer para:', cleanInitialPagePath);
        console.log('DEBUG JS: Função de inicialização encontrada:', pageInitializers[cleanInitialPagePath]);
        console.log('DEBUG JS: Função existe no window:', typeof window[pageInitializers[cleanInitialPagePath]]);
        
        // Delay específico para anuncio-simple.js
        if (scriptToLoadForInitial === 'anuncio-simple.js') {
            console.log('🔧 DEBUG JS: Aguardando 500ms para anuncio-simple.js...');
            setTimeout(() => {
                window.SpaUtils.callPageInitializer(pageInitializers[cleanInitialPagePath], initialUrl);
            }, 500);
        } else {
            // Guard: não chamar initializer legado para pagamento quando v2 estiver presente
            const initializerForInitial = pageInitializers[cleanInitialPagePath];
            if (!(isPaymentV2Initial && initializerForInitial === 'initializePaymentPage')) {
                window.SpaUtils.callPageInitializer(initializerForInitial, initialUrl);
            } else {
                console.log('INFO JS: Inicializador legado de pagamento ignorado (v2 ativo no HTML inicial).');
            }
        }
    }

    window.SpaUtils.setupSpaForms();
    
    // Interceptar formulários de perfil para garantir AJAX
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'formNome' && e.target.action.includes('atualizarNome')) {
            console.log('🔍 DEBUG: Interceptando submit do formNome via listener global');
            e.preventDefault();
            if (typeof window.handleFormNomeSubmit === 'function') {
                window.handleFormNomeSubmit(e);
            } else {
                console.error('🔍 DEBUG: handleFormNomeSubmit não encontrada');
            }
        }
    });

    window.updateAnuncioSidebarLinks();
    console.log('INFO JS: Carga inicial - Sidebar atualizada.');
    
    // Adiciona event listener para o link de exclusão de conta no topbar
    console.log('🔍 DEBUG: Chamando setupDeleteAccountLink...');
    setupDeleteAccountLink();
    
    // Inicializa os botões da dashboard
    setupDashboardButtons();
});

function setupDeleteAccountLink() {
    console.log('🔍 DEBUG: setupDeleteAccountLink chamada');
    
    // Debug: verificar todos os links na página
    const allLinks = document.querySelectorAll('a');
    console.log('🔍 DEBUG: Total de links na página:', allLinks.length);
    
    const deleteAccountLink = document.getElementById('deleteAccountLink');
    console.log('🔍 DEBUG: deleteAccountLink encontrado:', !!deleteAccountLink);
    
    if (deleteAccountLink) {
        console.log('🔍 DEBUG: Link encontrado:', deleteAccountLink);
        console.log('🔍 DEBUG: Texto do link:', deleteAccountLink.textContent);
    }
    
    if (deleteAccountLink) {
        console.log('🔍 DEBUG: Configurando event listener...');
        
        // Remove listener antigo para evitar duplicação
        if (deleteAccountLink._clickHandler) {
            console.log('🔍 DEBUG: Removendo listener antigo');
            deleteAccountLink.removeEventListener('click', deleteAccountLink._clickHandler);
        }
        
        // Adiciona novo listener
        console.log('🔍 DEBUG: Adicionando novo listener');
        deleteAccountLink.addEventListener('click', window.handleDeleteAccountClick);
        deleteAccountLink._clickHandler = window.handleDeleteAccountClick;
        
        console.log('INFO JS: Event listener para deleteAccountLink configurado.');
    } else {
        console.error('❌ ERRO: deleteAccountLink não encontrado!');
    }
}

/**
 * Configura os botões de pausar/ativar anúncio na dashboard
 */
function setupDashboardButtons() {
    console.log('INFO JS: setupDashboardButtons - Configurando botões da dashboard.');
    
    const btnPausarAnuncio = document.getElementById('btnPausarAnuncio');
    const btnAtivarAnuncio = document.getElementById('btnAtivarAnuncio');
    
    if (btnPausarAnuncio) {
        // Remove listener antigo para evitar duplicação
        if (btnPausarAnuncio._clickHandler) {
            btnPausarAnuncio.removeEventListener('click', btnPausarAnuncio._clickHandler);
            btnPausarAnuncio._clickHandler = null;
        }
        
        // Adiciona novo listener
        const clickHandler = function(event) {
            event.preventDefault();
            handleDashboardPausarAnuncioClick(event);
        };
        
        btnPausarAnuncio.addEventListener('click', clickHandler);
        btnPausarAnuncio._clickHandler = clickHandler;
        
        console.log('INFO JS: Event listener para btnPausarAnuncio configurado.');
    } else {
        console.log('INFO JS: btnPausarAnuncio não encontrado - não configurando listener.');
    }
    
    if (btnAtivarAnuncio) {
        // Remove listener antigo para evitar duplicação
        if (btnAtivarAnuncio._clickHandler) {
            btnAtivarAnuncio.removeEventListener('click', btnAtivarAnuncio._clickHandler);
            btnAtivarAnuncio._clickHandler = null;
        }
        
        // Adiciona novo listener
        const clickHandler = function(event) {
            event.preventDefault();
            handleDashboardAtivarAnuncioClick(event);
        };
        
        btnAtivarAnuncio.addEventListener('click', clickHandler);
        btnAtivarAnuncio._clickHandler = clickHandler;
        
        console.log('INFO JS: Event listener para btnAtivarAnuncio configurado.');
    } else {
        console.log('INFO JS: btnAtivarAnuncio não encontrado - não configurando listener.');
    }
}

/**
 * Função para lidar com o clique no botão "Pausar Anúncio" da dashboard
 */
function handleDashboardPausarAnuncioClick(event) {
    console.log('DEBUG JS: handleDashboardPausarAnuncioClick - Função chamada');
    event.preventDefault();
    
    const userId = document.body.dataset.userId;
    const anuncioStatus = document.body.dataset.anuncioStatus;
    
    console.log('DEBUG JS: handleDashboardPausarAnuncioClick - userId:', userId, 'anuncioStatus:', anuncioStatus);
    
    if (!userId) {
        console.error('ERRO JS: User ID não encontrado para toggleAnuncioStatus.');
        window.showFeedbackModal('error', 'Não foi possível identificar o usuário para esta ação.', 'Erro!');
        return;
    }
    
    if (anuncioStatus !== 'active') {
        console.error('DEBUG JS: Status do anúncio não permite pausar:', anuncioStatus);
        window.showFeedbackModal('error', 'Status do anúncio não permite esta operação.', 'Erro!');
        return;
    }
    
    const confirmTitle = 'Confirmar Ação';
    const confirmMessage = 'Tem certeza que deseja PAUSAR seu anúncio? Ele não ficará visível publicamente.';
    const actionType = 'deactivate';
    
    console.log('DEBUG JS: handleDashboardPausarAnuncioClick - Mostrando modal de confirmação. actionType:', actionType);
    
    showConfirmModal(confirmTitle, confirmMessage).then(async (confirmed) => {
        console.log('DEBUG JS: handleDashboardPausarAnuncioClick - Resposta do modal:', confirmed);
        
        if (!confirmed) {
            console.log('DEBUG JS: Usuário cancelou a ação');
            return;
        }
        
        console.log('DEBUG JS: Usuário confirmou a ação. Enviando requisição AJAX...');
        console.log('DEBUG JS: user_id:', userId, 'action:', actionType);
        
        window.showLoadingModal('Processando...');
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', actionType);
            
            console.log('DEBUG JS: Enviando requisição para:', `${window.URLADM}anuncio/toggleAnuncioStatus`);
            
            const response = await fetch(`${window.URLADM}anuncio/toggleAnuncioStatus`, {
                method: 'POST',
                body: formData
            });

            console.log('DEBUG JS: Resposta recebida. Status:', response.status);

            const responseText = await response.text();
            console.log('DEBUG JS: Resposta bruta do toggleAnuncioStatus:', responseText);

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('ERRO JS: Erro ao parsear JSON da resposta:', jsonError, 'Resposta:', responseText);
                throw new Error('Resposta inválida do servidor. Verifique os logs do PHP.');
            }
            
            await window.hideLoadingModal();
            
            if (result.success) {
                console.log('INFO JS: Anúncio pausado com sucesso. Atualizando interface...');
                
                // Atualiza o status no body dataset
                document.body.dataset.anuncioStatus = 'pausado';
                
                // Atualiza a sidebar
                window.updateAnuncioSidebarLinks();
                
                // Recarrega a página para atualizar a dashboard
                window.location.reload();
                
                window.showFeedbackModal('success', result.message, 'Sucesso!');
            } else {
                console.error('ERRO JS: Falha ao pausar anúncio:', result.message);
                window.showFeedbackModal('error', result.message, 'Erro!');
            }
        } catch (error) {
            console.error('ERRO JS: Erro na requisição AJAX:', error);
            await window.hideLoadingModal();
            window.showFeedbackModal('error', 'Erro interno. Tente novamente mais tarde.', 'Erro!');
        }
    });
}

/**
 * Função para lidar com o clique no botão "Ativar Anúncio" da dashboard
 */
function handleDashboardAtivarAnuncioClick(event) {
    console.log('DEBUG JS: handleDashboardAtivarAnuncioClick - Função chamada');
    event.preventDefault();
    
    const userId = document.body.dataset.userId;
    const anuncioStatus = document.body.dataset.anuncioStatus;
    
    console.log('DEBUG JS: handleDashboardAtivarAnuncioClick - userId:', userId, 'anuncioStatus:', anuncioStatus);
    
    if (!userId) {
        console.error('ERRO JS: User ID não encontrado para toggleAnuncioStatus.');
        window.showFeedbackModal('error', 'Não foi possível identificar o usuário para esta ação.', 'Erro!');
        return;
    }
    
    if (anuncioStatus !== 'pausado') {
        console.error('DEBUG JS: Status do anúncio não permite ativar:', anuncioStatus);
        window.showFeedbackModal('error', 'Status do anúncio não permite esta operação.', 'Erro!');
        return;
    }
    
    const confirmTitle = 'Confirmar Ação';
    const confirmMessage = 'Tem certeza que deseja ATIVAR seu anúncio? Ele voltará a ficar visível publicamente.';
    const actionType = 'activate';
    
    console.log('DEBUG JS: handleDashboardAtivarAnuncioClick - Mostrando modal de confirmação. actionType:', actionType);
    
    showConfirmModal(confirmTitle, confirmMessage).then(async (confirmed) => {
        console.log('DEBUG JS: handleDashboardAtivarAnuncioClick - Resposta do modal:', confirmed);
        
        if (!confirmed) {
            console.log('DEBUG JS: Usuário cancelou a ação');
            return;
        }
        
        console.log('DEBUG JS: Usuário confirmou a ação. Enviando requisição AJAX...');
        console.log('DEBUG JS: user_id:', userId, 'action:', actionType);
        
        window.showLoadingModal('Processando...');
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', actionType);
            
            console.log('DEBUG JS: Enviando requisição para:', `${window.URLADM}anuncio/toggleAnuncioStatus`);
            
            const response = await fetch(`${window.URLADM}anuncio/toggleAnuncioStatus`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            console.log('DEBUG JS: Resposta recebida. Status:', response.status);
            
            const responseText = await response.text();
            console.log('DEBUG JS: Resposta bruta do toggleAnuncioStatus:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('ERRO JS: Erro ao parsear JSON da resposta:', jsonError, 'Resposta:', responseText);
                throw new Error('Resposta inválida do servidor. Verifique os logs do PHP.');
            }
            
            await window.hideLoadingModal();
            
            if (result.success) {
                console.log('INFO JS: Anúncio ativado com sucesso. Atualizando interface...');
                
                // Atualiza o status no body dataset
                document.body.dataset.anuncioStatus = 'active';
            
            // Atualiza a sidebar
            window.updateAnuncioSidebarLinks();
            
                // Recarrega a página para atualizar a dashboard
                window.location.reload();
                
                window.showFeedbackModal('success', result.message, 'Sucesso!');
        } else {
                console.error('ERRO JS: Falha ao ativar anúncio:', result.message);
                window.showFeedbackModal('error', result.message, 'Erro!');
            }
        } catch (error) {
            console.error('ERRO JS: Erro na requisição AJAX:', error);
            await window.hideLoadingModal();
            window.showFeedbackModal('error', 'Erro interno. Tente novamente mais tarde.', 'Erro!');
        }
    });
}

function showConfirmModal(title, message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('confirmModalLabel');
        const modalBody = document.getElementById('confirmModalBody');
        const confirmBtn = document.getElementById('confirmModalConfirmBtn');
        const cancelBtn = document.getElementById('confirmModalCancelBtn');
        
        if (modal && modalTitle && modalBody && confirmBtn && cancelBtn) {
            modalTitle.textContent = title;
            modalBody.textContent = message;
            
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            const handleConfirm = () => {
                modalInstance.hide();
                confirmBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                resolve(true);
            };
            
            const handleCancel = () => {
                modalInstance.hide();
                confirmBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                resolve(false);
            };
            
            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
        } else {
            // Fallback se o modal não existir
            resolve(confirm(title + '\n\n' + message));
        }
    });
}

// Função para lidar com o clique no link "Pausar/Ativar Anúncio" da sidebar
window.handlePausarAnuncioClick = function(event) {
    console.log('DEBUG JS: handlePausarAnuncioClick - Função chamada');
    event.preventDefault();
    
    const userId = document.body.dataset.userId;
    const anuncioStatus = document.body.dataset.anuncioStatus;
    
    console.log('DEBUG JS: handlePausarAnuncioClick - userId:', userId, 'anuncioStatus:', anuncioStatus);
    console.log('DEBUG JS: handlePausarAnuncioClick - Status atual do body:', document.body.dataset.anuncioStatus);
    
    if (!userId) {
        console.error('ERRO JS: User ID não encontrado para toggleAnuncioStatus.');
        window.showFeedbackModal('error', 'Não foi possível identificar o usuário para esta ação.', 'Erro!');
        return;
    }
    
    let confirmTitle = 'Confirmar Ação';
    let confirmMessage = '';
    let actionType = '';
    
    // Verificação adicional para garantir que o status está correto
    if (anuncioStatus !== document.body.dataset.anuncioStatus) {
        console.warn('DEBUG JS: Inconsistência detectada! anuncioStatus:', anuncioStatus, 'body.dataset.anuncioStatus:', document.body.dataset.anuncioStatus);
        // Usa o valor do body dataset como fonte da verdade
        const correctedStatus = document.body.dataset.anuncioStatus;
        console.log('DEBUG JS: Usando status corrigido:', correctedStatus);
        
        if (correctedStatus === 'active') {
            confirmMessage = 'Tem certeza que deseja PAUSAR seu anúncio? Ele não ficará visível publicamente.';
            actionType = 'deactivate';
        } else if (correctedStatus === 'pausado' || correctedStatus === 'inactive') {
            confirmMessage = 'Tem certeza que deseja ATIVAR seu anúncio? Ele voltará a ficar visível publicamente.';
            actionType = 'activate';
        } else {
            console.error('DEBUG JS: Status do anúncio não permite esta operação:', correctedStatus);
            window.showFeedbackModal('error', 'Status do anúncio não permite esta operação.', 'Erro!');
            return;
        }
    } else {
        if (anuncioStatus === 'active') {
            confirmMessage = 'Tem certeza que deseja PAUSAR seu anúncio? Ele não ficará visível publicamente.';
            actionType = 'deactivate';
        } else if (anuncioStatus === 'pausado' || anuncioStatus === 'inactive') {
            confirmMessage = 'Tem certeza que deseja ATIVAR seu anúncio? Ele voltará a ficar visível publicamente.';
            actionType = 'activate';
        } else {
            console.error('DEBUG JS: Status do anúncio não permite esta operação:', anuncioStatus);
            window.showFeedbackModal('error', 'Status do anúncio não permite esta operação.', 'Erro!');
            return;
        }
    }
    
    console.log('DEBUG JS: handlePausarAnuncioClick - Mostrando modal de confirmação. actionType:', actionType);
    
    showConfirmModal(confirmTitle, confirmMessage).then(async (confirmed) => {
        console.log('DEBUG JS: handlePausarAnuncioClick - Resposta do modal:', confirmed);
        
        if (!confirmed) {
            console.log('DEBUG JS: Usuário cancelou a ação');
            return; // Usuário cancelou
        }
        
        console.log('DEBUG JS: Usuário confirmou a ação. Enviando requisição AJAX...');
        console.log('DEBUG JS: user_id:', userId, 'action:', actionType);
        
        window.showLoadingModal('Processando...');
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', actionType);
            
            console.log('DEBUG JS: Enviando requisição para:', `${window.URLADM}anuncio/toggleAnuncioStatus`);
            
            const response = await fetch(`${window.URLADM}anuncio/toggleAnuncioStatus`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            console.log('DEBUG JS: Resposta recebida. Status:', response.status);
            
            const responseText = await response.text();
            console.log('DEBUG JS: Resposta bruta do toggleAnuncioStatus:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('ERRO JS: Erro ao parsear JSON da resposta:', jsonError, 'Resposta:', responseText);
                throw new Error('Resposta inválida do servidor. Verifique os logs do PHP.');
            }
            
            await window.hideLoadingModal();
            
            if (result.success) {
                console.log('DEBUG JS: Operação realizada com sucesso. Novo status:', result.new_anuncio_status);
                console.log('DEBUG JS: Status anterior no body:', document.body.dataset.anuncioStatus);
                
                window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
                document.body.dataset.anuncioStatus = result.new_anuncio_status || anuncioStatus;
                document.body.dataset.hasAnuncio = result.has_anuncio !== undefined ? (result.has_anuncio ? 'true' : 'false') : document.body.dataset.hasAnuncio;
                document.body.dataset.anuncioId = result.anuncio_id || '';
                
                console.log('DEBUG JS: Status atualizado no body:', document.body.dataset.anuncioStatus);
                
                window.updateAnuncioSidebarLinks();
                
                // Recarrega a página após 2 segundos para atualizar o status
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                console.error('DEBUG JS: Erro na operação:', result.message);
                window.showFeedbackModal('error', result.message || 'Erro ao realizar a ação.', 'Erro!');
            }
        } catch (error) {
            console.error('ERRO JS: Erro na requisição AJAX de toggleAnuncioStatus:', error);
            await window.hideLoadingModal();
            window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
        }
    });
};

/**
 * FUNÇÃO GLOBAL: Manipula o clique no link "Excluir Conta" (sidebar e topbar)
 */
// Função para processar exclusão sem conflitos
async function processDeleteAccount(deleteAccountUrl) {
    console.log('🔧 DEBUG: Iniciando processo de exclusão...');
    
    // Verificar se há outros modais abertos
    const openModals = document.querySelectorAll('.modal.show');
    console.log('🔍 DEBUG: Modais abertos:', openModals.length);
    
    if (openModals.length > 0) {
        console.log('⚠️ DEBUG: Ainda há modais abertos, aguardando...');
        openModals.forEach((modal, index) => {
            console.log(`🔍 DEBUG: Modal ${index}:`, modal.id, modal.className);
        });
        setTimeout(async () => {
            await processDeleteAccount(deleteAccountUrl);
        }, 500);
        return;
    }
    
    // Mostrar loading
    console.log('🔧 DEBUG: Mostrando modal de loading...');
    window.showLoadingModal('Excluindo conta...');
    
    try {
        const response = await fetch(deleteAccountUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'delete_account' })
        });

        const data = await response.json();
        
        // Esconder loading
        console.log('🔧 DEBUG: Escondendo modal de loading...');
        window.hideLoadingModal();
        
        // Aguardar loading fechar completamente
        setTimeout(() => {
            console.log('🔧 DEBUG: Mostrando modal de feedback...');
            if (data.success) {
                window.showFeedbackModal('success', data.message || 'Sua conta foi excluída com sucesso!', 'Conta Excluída');
                setTimeout(() => {
                    window.location.href = data.redirect_url || window.URLADM + 'login';
                }, 2000);
            } else {
                window.showFeedbackModal('error', data.message || 'Erro ao excluir a conta', 'Erro na Exclusão');
            }
        }, 500);
    } catch (error) {
        console.log('🔧 DEBUG: Erro na requisição, escondendo loading...');
        window.hideLoadingModal();
        console.error('Erro na requisição de exclusão:', error);
        setTimeout(() => {
            console.log('🔧 DEBUG: Mostrando modal de erro...');
            window.showFeedbackModal('error', 'Não foi possível excluir a conta. Tente novamente.', 'Erro na Exclusão');
        }, 500);
    }
}

window.handleDeleteAccountClick = function(event) {
    console.log('🔍 DEBUG: handleDeleteAccountClick chamada!');
    console.log('🔍 DEBUG: Evento:', event);
    console.log('🔍 DEBUG: Target:', event.target);
    event.preventDefault();
    console.log("INFO: Função handleDeleteAccountClick iniciada");
    
    // VERIFICAÇÃO DE SEGURANÇA: Impedir admin de excluir própria conta
    const currentUserId = document.body.dataset.userId;
    const userRole = document.body.dataset.userRole;
    
    console.log('🔍 DEBUG: currentUserId:', currentUserId);
    console.log('🔍 DEBUG: userRole:', userRole);
    
    if (userRole === 'admin') {
        console.error('❌ ERRO: Administrador tentando excluir própria conta!');
        window.showFeedbackModal('error', 'Você não pode excluir sua própria conta de administrador!', 'Erro de Segurança');
        return;
    }
    
    // A URL para a ação de exclusão no controlador Perfil.php
    const deleteAccountUrl = window.URLADM + 'perfil/deleteAccount'; 

    // Mostrar modal personalizado
    console.log('🔍 DEBUG: Chamando showDeleteAccountModal...');
    showDeleteAccountModal().then(async (confirmed) => {
        if (confirmed) {
            console.log("INFO: Usuário confirmou a exclusão da conta. Prosseguindo com a requisição.");
            
            // Aguardar modal fechar completamente
            setTimeout(async () => {
                await processDeleteAccount(deleteAccountUrl);
            }, 800);
        } else {
            console.log("INFO: Exclusão de conta cancelada pelo usuário.");
        }
    });
};

/**
 * FUNÇÃO: Mostra o modal personalizado para exclusão de conta
 */
/**
 * FUNÇÃO: Mostra o modal de exclusão de conta (versão limpa)
 */
function showDeleteAccountModal() {
    return new Promise((resolve) => {
        console.log('🗑️ DEBUG: Iniciando showDeleteAccountModal');
        
        const modal = document.getElementById('deleteAccountModal');
        const confirmBtn = document.getElementById('deleteAccountConfirmBtn');
        const cancelBtn = document.getElementById('deleteAccountCancelBtn');
        
        console.log('🔍 DEBUG MODAL: Modal encontrado:', !!modal);
        console.log('🔍 DEBUG MODAL: Modal elemento:', modal);
        console.log('🔍 DEBUG MODAL: ConfirmBtn encontrado:', !!confirmBtn);
        console.log('🔍 DEBUG MODAL: CancelBtn encontrado:', !!cancelBtn);
        
        if (!modal || !confirmBtn || !cancelBtn) {
            console.error('❌ ERRO: Elementos do modal não encontrados');
            console.error('❌ ERRO: Modal:', modal);
            console.error('❌ ERRO: ConfirmBtn:', confirmBtn);
            console.error('❌ ERRO: CancelBtn:', cancelBtn);
            resolve(false);
            return;
        }
        
        // Configurar event listeners
        const handleConfirm = () => {
            console.log('🔧 DEBUG: Usuário confirmou exclusão');
            
            // Fechar modal de exclusão primeiro
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
            
            // Aguardar modal fechar completamente
            setTimeout(() => {
                modal.removeEventListener('hidden.bs.modal', handleCancel);
                confirmBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                resolve(true);
            }, 300);
        };
        
        const handleCancel = () => {
            console.log('🔍 DEBUG SCROLL: Modal sendo fechado');
            console.log('🔍 DEBUG SCROLL: document.body.style.overflow antes:', document.body.style.overflow);
            
            modal.removeEventListener('hidden.bs.modal', handleCancel);
            confirmBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', handleCancel);
            
            // Debug: Verificar scroll após fechar
            setTimeout(() => {
                console.log('🔍 DEBUG SCROLL: Após fechar modal');
                console.log('🔍 DEBUG SCROLL: document.body.style.overflow:', document.body.style.overflow);
                console.log('🔍 DEBUG SCROLL: document.documentElement.style.overflow:', document.documentElement.style.overflow);
                console.log('🔍 DEBUG SCROLL: Modal backdrop removido:', !document.querySelector('.modal-backdrop'));
                
                const bodyOverflow = getComputedStyle(document.body).overflow;
                const htmlOverflow = getComputedStyle(document.documentElement).overflow;
                console.log('🔍 DEBUG SCROLL: Computed body overflow após fechar:', bodyOverflow);
                console.log('🔍 DEBUG SCROLL: Computed html overflow após fechar:', htmlOverflow);
            }, 100);
            
            resolve(false);
        };
        
        // Adicionar event listeners
        confirmBtn.addEventListener('click', handleConfirm);
        cancelBtn.addEventListener('click', handleCancel);
        modal.addEventListener('hidden.bs.modal', handleCancel);
        
        // Mostrar modal
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: false
        });
        
        // Debug: Verificar scroll antes de mostrar
        console.log('🔍 DEBUG SCROLL: Antes de mostrar modal');
        console.log('🔍 DEBUG SCROLL: document.body.style.overflow:', document.body.style.overflow);
        console.log('🔍 DEBUG SCROLL: document.documentElement.style.overflow:', document.documentElement.style.overflow);
        console.log('🔍 DEBUG SCROLL: window.innerHeight:', window.innerHeight);
        console.log('🔍 DEBUG SCROLL: document.body.scrollHeight:', document.body.scrollHeight);
        
        // Debug: Informações do dispositivo
        console.log('🔍 DEBUG DEVICE: window.innerWidth:', window.innerWidth);
        console.log('🔍 DEBUG DEVICE: window.innerHeight:', window.innerHeight);
        console.log('🔍 DEBUG DEVICE: screen.width:', screen.width);
        console.log('🔍 DEBUG DEVICE: screen.height:', screen.height);
        console.log('🔍 DEBUG DEVICE: User Agent:', navigator.userAgent);
        console.log('🔍 DEBUG DEVICE: É mobile?', window.innerWidth <= 768);
        console.log('🔍 DEBUG DEVICE: É tablet?', window.innerWidth > 768 && window.innerWidth <= 1024);
        console.log('🔍 DEBUG DEVICE: É desktop?', window.innerWidth > 1024);
        
        bsModal.show();
        console.log('✅ Modal de exclusão exibido');
        
        // Forçar modal a aparecer
        setTimeout(() => {
            console.log('🔧 DEBUG: Forçando modal a aparecer...');
            modal.style.display = 'block';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.zIndex = '999999';
            modal.style.background = 'rgba(0,0,0,0.5)';
            modal.classList.add('show');
            
            // Forçar scroll interno em mobile
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.maxHeight = '95vh';
                modalContent.style.overflowY = 'auto';
                console.log('🔧 DEBUG: Scroll interno configurado');
            }
            
            console.log('🔧 DEBUG: Modal forçado a aparecer');
        }, 50);
        
        // Correção imediata para scroll
        setTimeout(() => {
            console.log('🔧 DEBUG SCROLL: Aplicando correção imediata...');
            document.body.style.overflow = 'auto';
            document.documentElement.style.overflow = 'auto';
            document.body.style.paddingRight = '0px';
            document.documentElement.style.paddingRight = '0px';
            document.body.classList.remove('modal-open');
            console.log('🔧 DEBUG SCROLL: Correção imediata aplicada');
        }, 100);
        
        // Debug adicional para verificar se o modal apareceu
        setTimeout(() => {
            console.log('🔍 DEBUG MODAL: Verificando se modal está visível...');
            console.log('🔍 DEBUG MODAL: Modal display:', getComputedStyle(modal).display);
            console.log('🔍 DEBUG MODAL: Modal visibility:', getComputedStyle(modal).visibility);
            console.log('🔍 DEBUG MODAL: Modal opacity:', getComputedStyle(modal).opacity);
            console.log('🔍 DEBUG MODAL: Modal z-index:', getComputedStyle(modal).zIndex);
            console.log('🔍 DEBUG MODAL: Modal position:', getComputedStyle(modal).position);
            console.log('🔍 DEBUG MODAL: Modal classes:', modal.className);
            console.log('🔍 DEBUG MODAL: Modal parent:', modal.parentElement);
            console.log('🔍 DEBUG MODAL: Modal no DOM:', document.contains(modal));
            
            // Debug de posicionamento
            const rect = modal.getBoundingClientRect();
            console.log('🔍 DEBUG MODAL: Modal rect:', rect);
            console.log('🔍 DEBUG MODAL: Modal top:', rect.top);
            console.log('🔍 DEBUG MODAL: Modal left:', rect.left);
            console.log('🔍 DEBUG MODAL: Modal width:', rect.width);
            console.log('🔍 DEBUG MODAL: Modal height:', rect.height);
            
            // Verificar se está fora da tela
            if (rect.top < 0 || rect.left < 0 || rect.top > window.innerHeight || rect.left > window.innerWidth) {
                console.log('⚠️ DEBUG MODAL: Modal pode estar fora da tela!');
                console.log('🔧 DEBUG MODAL: Tentando reposicionar...');
                modal.style.top = '0px';
                modal.style.left = '0px';
                modal.style.transform = 'none';
            }
            
            // Verificar se há outros modais interferindo
            const allModals = document.querySelectorAll('.modal');
            console.log('🔍 DEBUG MODAL: Total de modais na página:', allModals.length);
            allModals.forEach((m, i) => {
                console.log(`🔍 DEBUG MODAL: Modal ${i}:`, m.id, m.className, getComputedStyle(m).display);
            });
            
            // Verificar backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            console.log('🔍 DEBUG MODAL: Backdrop presente:', !!backdrop);
            if (backdrop) {
                console.log('🔍 DEBUG MODAL: Backdrop z-index:', getComputedStyle(backdrop).zIndex);
            }
        }, 1000);
        
        // Debug: Verificar scroll após mostrar
        setTimeout(() => {
            console.log('🔍 DEBUG SCROLL: Após mostrar modal');
            console.log('🔍 DEBUG SCROLL: document.body.style.overflow:', document.body.style.overflow);
            console.log('🔍 DEBUG SCROLL: document.documentElement.style.overflow:', document.documentElement.style.overflow);
            console.log('🔍 DEBUG SCROLL: Modal backdrop presente:', !!document.querySelector('.modal-backdrop'));
            console.log('🔍 DEBUG SCROLL: Modal classes:', modal.className);
            
            // Verificar se há scroll bloqueado
            const bodyOverflow = getComputedStyle(document.body).overflow;
            const htmlOverflow = getComputedStyle(document.documentElement).overflow;
            console.log('🔍 DEBUG SCROLL: Computed body overflow:', bodyOverflow);
            console.log('🔍 DEBUG SCROLL: Computed html overflow:', htmlOverflow);
            
            if (bodyOverflow === 'hidden' || htmlOverflow === 'hidden') {
                console.log('⚠️ DEBUG SCROLL: Scroll pode estar bloqueado!');
                console.log('🔧 DEBUG SCROLL: Tentando restaurar scroll...');
                
                // Correção mais robusta para scroll
                document.body.style.overflow = 'auto';
                document.documentElement.style.overflow = 'auto';
                document.body.style.paddingRight = '0px';
                document.documentElement.style.paddingRight = '0px';
                document.body.classList.remove('modal-open');
                
                // Forçar reflow
                document.body.offsetHeight;
                
                console.log('🔧 DEBUG SCROLL: Scroll restaurado manualmente');
                console.log('🔧 DEBUG SCROLL: Verificação pós-correção:');
                console.log('🔧 DEBUG SCROLL: body overflow:', getComputedStyle(document.body).overflow);
                console.log('🔧 DEBUG SCROLL: html overflow:', getComputedStyle(document.documentElement).overflow);
                
                // Debug adicional para scroll
                console.log('🔍 DEBUG SCROLL: Testando scroll...');
                console.log('🔍 DEBUG SCROLL: window.scrollY:', window.scrollY);
                console.log('🔍 DEBUG SCROLL: document.body.scrollTop:', document.body.scrollTop);
                console.log('🔍 DEBUG SCROLL: document.documentElement.scrollTop:', document.documentElement.scrollTop);
                
                // Tentar forçar scroll
                window.scrollTo(0, 100);
                setTimeout(() => {
                    console.log('🔍 DEBUG SCROLL: Após scrollTo(0, 100)');
                    console.log('🔍 DEBUG SCROLL: window.scrollY:', window.scrollY);
                }, 100);
                
            } else {
                console.log('✅ DEBUG SCROLL: Scroll não está bloqueado');
            }
        }, 500);
    });
}

/**
 * FUNÇÃO: Fechar modal de exclusão de conta
 */
function fecharModal() {
    const modal = document.getElementById('deleteAccountModal');
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    }
}

/**
 * FUNÇÃO: Confirmar exclusão de conta
 */
function confirmarExclusao() {
    const deleteAccountUrl = window.URLADM + 'perfil/deleteAccount';
    
    // Fechar modal
    fecharModal();
    
    // Mostrar loading
    window.showLoadingModal('Excluindo conta...');
    
    // Fazer requisição
    fetch(deleteAccountUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: 'delete_account' })
    })
    .then(response => response.json())
    .then(data => {
        window.hideLoadingModal();
        
        setTimeout(() => {
            if (data.success) {
                window.showFeedbackModal('success', data.message || 'Sua conta foi excluída com sucesso!', 'Conta Excluída');
                setTimeout(() => {
                    window.location.href = data.redirect_url || window.URLADM + 'login';
                }, 2000);
            } else {
                window.showFeedbackModal('error', data.message || 'Erro ao excluir a conta', 'Erro na Exclusão');
            }
        }, 300);
    })
    .catch(error => {
        console.error('ERRO: Erro na requisição de exclusão de conta:', error);
        window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
    });
}

// Event listeners para o novo modal
document.addEventListener('DOMContentLoaded', function() {
    // Botão cancelar
    const cancelBtn = document.getElementById('deleteAccountCancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', fecharModal);
    }
    
    // Botão confirmar
    const confirmBtn = document.getElementById('deleteAccountConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmarExclusao);
    }
    
    // Event listener para fechar modal com ESC (desabilitado pelo data-bs-keyboard="false")
    // O modal tem data-bs-backdrop="static" então não fecha clicando fora
});

/**
 * Inicializa a página de pagamento
 */
function initializePaymentPage() {
    console.log('🚀 Inicializando página de pagamento...');
    
    // Aguardar jQuery estar disponível
    const waitForJQuery = () => {
        if (typeof $ !== 'undefined' && $.fn) {
            console.log('✅ jQuery disponível na página de pagamento');
            return true;
        }
        return false;
    };
    
    // Tentar inicializar imediatamente
    if (waitForJQuery()) {
        setupPaymentPage();
    } else {
        // Aguardar jQuery carregar
        const checkInterval = setInterval(() => {
            if (waitForJQuery()) {
                clearInterval(checkInterval);
                setupPaymentPage();
            }
        }, 100);
        
        // Timeout de segurança
        setTimeout(() => {
            clearInterval(checkInterval);
            if (!waitForJQuery()) {
                console.warn('⚠️ jQuery não carregou em 5 segundos, configurando sem jQuery');
                setupPaymentPage();
            }
        }, 5000);
    }
}

/**
 * Configura a página de pagamento
 */
function setupPaymentPage() {
    console.log('🔧 Configurando página de pagamento...');
    
    // Verificar se os elementos existem
    const generateBtn = document.getElementById('generatePaymentBtn');
    const copyBtn = document.getElementById('copyPixCode');
    const checkBtn = document.getElementById('checkPaymentBtn');
    const newBtn = document.getElementById('newPaymentBtn');
    
    if (generateBtn) {
        console.log('✅ Botão "Gerar PIX" encontrado');
        // Remover listeners antigos se existirem
        generateBtn.replaceWith(generateBtn.cloneNode(true));
    }
    
    if (copyBtn) {
        console.log('✅ Botão "Copiar" encontrado');
        // Remover listeners antigos se existirem
        copyBtn.replaceWith(copyBtn.cloneNode(true));
    }
    
    if (checkBtn) {
        console.log('✅ Botão "Verificar Pagamento" encontrado');
        // Remover listeners antigos se existirem
        checkBtn.replaceWith(checkBtn.cloneNode(true));
    }
    
    if (newBtn) {
        console.log('✅ Botão "Novo Pagamento" encontrado');
        // Remover listeners antigos se existirem
        newBtn.replaceWith(newBtn.cloneNode(true));
    }
    
    // Aguardar um pouco para garantir que os elementos foram recriados
    setTimeout(() => {
        console.log('✅ Página de pagamento configurada com sucesso');
        console.log('ℹ️ Configurando event listeners...');
        
        // Configurar event listeners manualmente
        const generateBtn = document.getElementById('generatePaymentBtn');
        const copyBtn = document.getElementById('copyPixCode');
        const checkBtn = document.getElementById('checkPaymentBtn');
        const newBtn = document.getElementById('newPaymentBtn');
        
        if (generateBtn) {
            generateBtn.addEventListener('click', function() {
                console.log('🔘 Botão Gerar PIX clicado');
                handleGeneratePayment();
            });
            console.log('✅ Event listener do botão Gerar PIX configurado');
        }
        
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                console.log('🔘 Botão Copiar clicado');
                if (typeof copyPixCode === 'function') {
                    copyPixCode();
                } else {
                    console.error('❌ Função copyPixCode não encontrada');
                }
            });
            console.log('✅ Event listener do botão Copiar configurado');
        }
        
        if (checkBtn) {
            checkBtn.addEventListener('click', function() {
                console.log('🔘 Botão Verificar Pagamento clicado');
                if (typeof checkPaymentStatus === 'function') {
                    checkPaymentStatus();
                } else {
                    console.error('❌ Função checkPaymentStatus não encontrada');
                }
            });
            console.log('✅ Event listener do botão Verificar Pagamento configurado');
        }
        
        if (newBtn) {
            newBtn.addEventListener('click', function() {
                console.log('🔘 Botão Novo Pagamento clicado');
                if (typeof createNewPayment === 'function') {
                    createNewPayment();
                } else {
                    console.error('❌ Função createNewPayment não encontrada');
                }
            });
            console.log('✅ Event listener do botão Novo Pagamento configurado');
        }
        
        console.log('✅ Todos os event listeners configurados com sucesso');
        // Garantir que a view de pagamento seja centralizada após setup
        try {
            // Tentar chamar imediatamente; se não estiver disponível, fazer polling por um curto período
            const callAdjust = async () => {
                try {
                    if (typeof window.adjustPaymentContentPosition === 'function') {
                        const res = window.adjustPaymentContentPosition();
                        // se a função retornar uma Promise, aguardar sua resolução
                        if (res && typeof res.then === 'function') {
                            try {
                                const ok = await res;
                                return !!ok;
                            } catch (e) {
                                return false;
                            }
                        }
                        return true;
                    }
                    return false;
                } catch (innerErr) {
                    console.warn('dashboard_custom.js: erro dentro de callAdjust', innerErr);
                    return false;
                }
            };

            // Função fallback local que replica a lógica de centralização da view
            // Implementa amostragem em múltiplos frames (rAF) para evitar medições instáveis
            const localAdjust = () => {
                try {
                    const sidebar = document.getElementById('sidebar');
                    const paymentContainer = document.querySelector('#paymentContent > .container');
                    if (!paymentContainer) { console.log('dashboard_custom.js: localAdjust - paymentContainer não encontrado'); return false; }

                    // Capturar 3 amostras em frames consecutivos e escolher a mais alta largura do container
                    return new Promise((resolve) => {
                        const samples = [];
                        let frames = 0;
                        const sampleFrame = () => {
                            try {
                                const sidebarWidth = sidebar ? sidebar.getBoundingClientRect().width : 0;
                                const viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
                                const containerWidth = paymentContainer.getBoundingClientRect().width;
                                const parentLeft = paymentContainer.parentElement.getBoundingClientRect().left;

                                samples.push({ sidebarWidth, viewportWidth, containerWidth, parentLeft });
                            } catch (e) {
                                console.warn('dashboard_custom.js: localAdjust sample erro', e);
                            }

                            frames++;
                            if (frames < 3) {
                                requestAnimationFrame(sampleFrame);
                                return;
                            }

                            // Escolher a amostra com maior containerWidth (mais confiável após reflow)
                            const chosen = samples.reduce((acc, s) => s.containerWidth > acc.containerWidth ? s : acc, samples[0]);

                            const available = Math.max(0, chosen.viewportWidth - chosen.sidebarWidth);
                            const desiredLeft = chosen.sidebarWidth + Math.max(0, (available - chosen.containerWidth) / 2);
                            let marginLeft = desiredLeft - chosen.parentLeft;
                            if (marginLeft < 0) marginLeft = 0;

                            paymentContainer.style.marginLeft = Math.round(marginLeft) + 'px';
                            paymentContainer.style.marginRight = 'auto';
                            resolve(true);
                        };

                        requestAnimationFrame(sampleFrame);
                    });
                } catch (err) {
                    console.warn('dashboard_custom.js: localAdjust erro', err);
                    return false;
                }
            };

            if (!callAdjust()) {
                // Polling: tentar a cada 50ms por até 20 tentativas (~1s)
                let attempts = 0;
                const maxAttempts = 20;
                const intervalMs = 50;
                const poll = setInterval(async () => {
                    attempts++;
                    try {
                        const called = await callAdjust();
                        if (called) {
                            clearInterval(poll);
                            return;
                        }
                    } catch (e) {
                        // continuar para fallback
                    }

                    // se ajustador da view ainda não existir e elementos já presentes, tentar fallback local
                    const paymentContainerExists = !!document.querySelector('#paymentContent > .container');
                    if (paymentContainerExists) {
                        try {
                            const res = localAdjust();
                            if (res && typeof res.then === 'function') {
                                const ok = await res;
                                if (ok) { clearInterval(poll); return; }
                            } else if (res) { clearInterval(poll); return; }
                        } catch (e) {}
                    }

                    if (attempts >= maxAttempts) {
                        clearInterval(poll);
                        console.warn('dashboard_custom.js: adjustPaymentContentPosition não foi definida após polling; tentou fallback local sem sucesso');
                    }
                }, intervalMs);
            }
        } catch (e) { console.warn('Erro ao chamar adjustPaymentContentPosition a partir de setupPaymentPage', e); }
    }, 100);
}

// Variáveis globais para pagamento
if (typeof currentSubscriptionId === 'undefined') {
    var currentSubscriptionId = null;
}
if (typeof paymentCheckInterval === 'undefined') {
    var paymentCheckInterval = null;
}

/**
 * Manipula o clique no botão Gerar PIX
 */
async function handleGeneratePayment() {
    try {
        console.log('🚀 Iniciando geração de pagamento...');
        
        // Esconder botão e mostrar loading
        const generateBtn = document.getElementById('generatePaymentBtn');
        if (generateBtn) {
            generateBtn.style.display = 'none';
        }
        
        showPaymentLoading('Gerando pagamento...');
        
        // Criar nova assinatura e pagamento
        const response = await createPaymentRequest();
        
        if (response.success) {
            currentSubscriptionId = response.subscription_id;
            showPaymentData(response);
            startPaymentCheck();
        } else {
            showPaymentError(response.message || 'Erro ao gerar pagamento');
            // Mostrar botão novamente em caso de erro
            if (generateBtn) {
                generateBtn.style.display = 'inline-block';
            }
        }
    } catch (error) {
        console.error('Erro ao gerar pagamento:', error);
        showPaymentError('Erro interno. Tente novamente.');
        // Mostrar botão novamente em caso de erro
        const generateBtn = document.getElementById('generatePaymentBtn');
        if (generateBtn) {
            generateBtn.style.display = 'inline-block';
        }
    }
}

/**
 * Cria uma requisição de pagamento
 */
async function createPaymentRequest() {
    const planType = new URLSearchParams(window.location.search).get('plan') || 'basic';
    
    const requestData = {
        plano_id: getPlanoIdByType(planType),
        period: '6_meses'
    };
    
    console.log('📤 Enviando requisição de pagamento:', requestData);
    
    try {
        const response = await fetch(`${window.projectBaseURL}payment_mp_working.php?t=${Date.now()}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestData)
        });
        
        console.log('🔍 DEBUG: Response status:', response.status);
        console.log('🔍 DEBUG: Response headers:', response.headers);
        
        const responseText = await response.text();
        console.log('🔍 DEBUG: Response text (first 200 chars):', responseText.substring(0, 200) + '...');
        
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('📥 Resposta recebida:', data);
        } catch (e) {
            console.error('❌ Erro ao fazer parse do JSON:', e);
            console.error('❌ Resposta completa:', responseText);
            throw new Error('Resposta não é JSON válido: ' + e.message);
        }
        
        return data;
    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        throw error;
    }
}

/**
 * Obtém o ID do plano pelo tipo
 */
function getPlanoIdByType(planType) {
    const planIds = {
        'basic': 1,
        'premium': 2,
        'free': 0
    };
    return planIds[planType] || 1;
}

/**
 * Mostra loading de pagamento
 */
function showPaymentLoading(message) {
    const statusDiv = document.getElementById('paymentStatus');
    if (statusDiv) {
        statusDiv.innerHTML = `
            <div class="alert alert-info d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-3" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <div>
                    <strong>Processando...</strong>
                    <p class="mb-0 small">${message}</p>
                </div>
            </div>
        `;
    }
}

/**
 * Mostra dados do pagamento
 */
    function showPaymentData(paymentData) {
        console.log('🔍 DEBUG: showPaymentData chamada com:', paymentData);
        
        const statusDiv = document.getElementById('paymentStatus');
        const qrImage = document.getElementById('qrCodeImage');
        const qrSection = document.getElementById('qrCodeSection');
        const pixCodeDiv = document.getElementById('pixCode');
        const pixSection = document.getElementById('pixCodeSection');
        
        console.log('🔍 DEBUG: Elementos encontrados:', {
            statusDiv: !!statusDiv,
            qrImage: !!qrImage,
            qrSection: !!qrSection,
            pixCodeDiv: !!pixCodeDiv,
            pixSection: !!pixSection
        });
        
        if (statusDiv) {
            statusDiv.innerHTML = `
                <div class="alert alert-success d-flex align-items-center">
                    <i class="fas fa-check-circle me-3"></i>
                    <div>
                        <strong>Pagamento gerado com sucesso!</strong>
                        <p class="mb-0 small">Escaneie o QR Code ou copie o código PIX</p>
                    </div>
                </div>
            `;
        }
        
        if (qrImage && paymentData.qr_code_base64) {
            console.log('🔍 DEBUG: Configurando QR Code...');
            console.log('🔍 DEBUG: QR Type:', paymentData.qr_type || 'unknown');
            
            // Detectar tipo de QR Code e usar formato correto
            if (paymentData.qr_type === 'png') {
                qrImage.src = `data:image/png;base64,${paymentData.qr_code_base64}`;
                console.log('🔍 DEBUG: QR Code PNG configurado');
            } else {
                qrImage.src = `data:image/svg+xml;base64,${paymentData.qr_code_base64}`;
                console.log('🔍 DEBUG: QR Code SVG configurado');
            }
            
            console.log('🔍 DEBUG: QR Code src definido:', qrImage.src.substring(0, 100) + '...');
            if (qrSection) {
                qrSection.style.display = 'block';
                console.log('🔍 DEBUG: QR Section exibida');
            }
        } else {
            console.log('🔍 DEBUG: QR Code não configurado - qrImage:', !!qrImage, 'qr_code_base64:', !!paymentData.qr_code_base64);
        }
        
        if (pixCodeDiv && paymentData.pix_copy_paste) {
            console.log('🔍 DEBUG: Configurando PIX Code...');
            pixCodeDiv.value = paymentData.pix_copy_paste;
            if (pixSection) {
                pixSection.style.display = 'block';
                console.log('🔍 DEBUG: PIX Section exibida');
            }
        } else {
            console.log('🔍 DEBUG: PIX Code não configurado - pixCodeDiv:', !!pixCodeDiv, 'pix_copy_paste:', !!paymentData.pix_copy_paste);
        }
    }

/**
 * Mostra erro de pagamento
 */
function showPaymentError(message) {
    const statusDiv = document.getElementById('paymentStatus');
    if (statusDiv) {
        statusDiv.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-3"></i>
                <div>
                    <strong>Erro:</strong> ${message}
                </div>
            </div>
        `;
    }
}

/**
 * Inicia verificação de pagamento
 */
function startPaymentCheck() {
    if (paymentCheckInterval) {
        clearInterval(paymentCheckInterval);
    }
    
    console.log('🔄 Iniciando verificação de pagamento...');
    
    paymentCheckInterval = setInterval(async () => {
        try {
            const response = await fetch(`${window.projectBaseURL}payment_status_mercadopago_real.php?subscription_id=${currentSubscriptionId}`);
            const data = await response.json();
            
            if (data.success && data.status === 'approved') {
                console.log('✅ Pagamento aprovado!');
                clearInterval(paymentCheckInterval);
                showPaymentSuccess();
            }
        } catch (error) {
            console.error('❌ Erro ao verificar pagamento:', error);
        }
    }, 5000);
}

/**
 * Mostra sucesso do pagamento
 */
function showPaymentSuccess() {
    const statusDiv = document.getElementById('paymentStatus');
    if (statusDiv) {
        statusDiv.innerHTML = `
            <div class="alert alert-success d-flex align-items-center">
                <i class="fas fa-check-circle me-3"></i>
                <div>
                    <strong>Pagamento confirmado!</strong>
                    <p class="mb-0 small">Seu plano foi ativado com sucesso</p>
                </div>
            </div>
        `;
    }
}

/**
 * Inicializa a página de gerenciamento de pagamentos
 */
function initializeAdminPaymentsPage() {
    console.log('🚀 Inicializando página de gerenciamento de pagamentos...');
    
    // Aguardar jQuery estar disponível
    const waitForJQuery = () => {
        if (typeof $ !== 'undefined' && $.fn) {
            console.log('✅ jQuery disponível na página de pagamentos');
            return true;
        }
        return false;
    };
    
    if (waitForJQuery()) {
        setupAdminPaymentsPage();
    } else {
        // Aguardar jQuery carregar
        const checkInterval = setInterval(() => {
            if (waitForJQuery()) {
                clearInterval(checkInterval);
                setupAdminPaymentsPage();
            }
        }, 100);
    }
}

/**
 * Configura a página de gerenciamento de pagamentos
 */
function setupAdminPaymentsPage() {
    console.log('🔧 Configurando página de gerenciamento de pagamentos...');
    
    // Configurar botões de ação
    const approveButtons = document.querySelectorAll('[onclick*="approvePayment"]');
    const rejectButtons = document.querySelectorAll('[onclick*="rejectPayment"]');
    
    console.log('✅ Botões de aprovação encontrados:', approveButtons.length);
    console.log('✅ Botões de rejeição encontrados:', rejectButtons.length);
    
    // Configurar modais se existirem
    if (typeof $ !== 'undefined' && $.fn.modal) {
        console.log('✅ Bootstrap modals disponíveis');
    }
    
    console.log('✅ Página de gerenciamento de pagamentos configurada com sucesso');
}

