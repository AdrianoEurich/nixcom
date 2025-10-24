// admin-users.js
// JavaScript para funcionalidades de gerenciamento de usuários

console.log("👑 ADMIN USERS JS carregado");

// Variáveis globais
let currentPage = 1;
let currentSearch = '';
let currentPlan = 'all';
let currentLimit = 5;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    console.log("🔧 Inicializando Admin Users...");
    
    // Verificar se estamos na página correta
    if (document.getElementById('adminUsersContent')) {
        console.log("🔧 Elemento adminUsersContent encontrado - inicializando...");
        initializeAdminUsers();
        setupEventListeners();
        // Altura do input segue classes Bootstrap; sem sincronização JS
    } else {
        console.log("🔧 Elemento adminUsersContent não encontrado - aguardando carregamento via SPA...");
    }
});

/**
 * Inicializa o sistema de admin de usuários
 */
function initializeAdminUsers() {
    console.log("🔧 Inicializando sistema de admin de usuários...");
    
    // Carregar dados iniciais
    loadUsersData();
    loadUsersStats();
    
    // Configurar filtros iniciais
    const urlParams = new URLSearchParams(window.location.search);
    currentSearch = urlParams.get('search') || '';
    currentPlan = urlParams.get('plan') || 'all';
    
    // Aplicar filtros iniciais
    if (document.getElementById('searchUsers')) {
        document.getElementById('searchUsers').value = currentSearch;
    }
    if (document.getElementById('filterPlan')) {
        document.getElementById('filterPlan').value = currentPlan;
    }
}

/**
 * Configura event listeners
 */
function setupEventListeners() {
    console.log("🔧 Configurando event listeners...");
    
    // Botão de atualizar
    const refreshBtn = document.getElementById('refreshUsersBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadUsersData();
            loadUsersStats();
        });
    }
    
    // Botão de exportar
    const exportBtn = document.getElementById('exportUsersBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportUsers();
        });
    }
    
    // Botão de aplicar filtros
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            applyFilters();
        });
    }
    
    // Event listener para paginação (delegation)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.page-link[data-page]')) {
            e.preventDefault();
            e.stopPropagation();
            const page = parseInt(e.target.closest('.page-link').getAttribute('data-page'));
            if (page && page > 0) {
                changePage(page);
            }
        }
    });
    
    // Pesquisa em tempo real
    const searchInput = document.getElementById('searchUsers');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });
        // Aplicar imediatamente ao pressionar Enter
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });
    }
    
    // Filtros
    const planFilter = document.getElementById('filterPlan');
    if (planFilter) {
        planFilter.addEventListener('change', applyFilters);
    }
    
    // Botão de salvar edição rápida
    const saveEditBtn = document.getElementById('saveQuickEditBtn');
    if (saveEditBtn) {
        saveEditBtn.addEventListener('click', function() {
            saveQuickEdit();
        });
    }

    // Sem sincronização de altura via JS
}


/**
 * Carrega dados dos usuários
 */
async function loadUsersData() {
    console.log("📡 Carregando dados dos usuários...");
    console.log("📡 URL:", `${window.URLADM}admin-users/listUsers?page=${currentPage}&search=${encodeURIComponent(currentSearch)}&plan=${currentPlan}`);
    
    try {
        const response = await fetch(`${window.URLADM}admin-users/listUsers?page=${currentPage}&search=${encodeURIComponent(currentSearch)}&plan=${currentPlan}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log("📡 Resposta recebida:", response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log("📡 Dados recebidos:", data);
        
        if (data.success) {
            renderUsersTable(data.users);
            renderPagination(data.pagination);
        } else {
            throw new Error(data.message || 'Erro ao carregar usuários');
        }
    } catch (error) {
        console.error("❌ Erro ao carregar usuários:", error);
        showError("Erro ao carregar usuários: " + error.message);
    }
}

/**
 * Carrega estatísticas dos usuários
 */
async function loadUsersStats() {
    console.log("📊 Carregando estatísticas...");
    console.log("📊 URL:", `${window.URLADM}admin-users/getStats`);
    
    try {
        const response = await fetch(`${window.URLADM}admin-users/getStats`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log("📊 Resposta recebida:", response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log("📊 Dados recebidos:", data);
        
        if (data.success) {
            updateStatsCards(data.stats);
        }
    } catch (error) {
        console.error("❌ Erro ao carregar estatísticas:", error);
    }
}

/**
 * Renderiza a tabela de usuários
 */
function renderUsersTable(users) {
    console.log("🎨 Renderizando tabela de usuários...");
    
    const tbody = document.getElementById('usersTableBody');
    
    if (!tbody) {
        console.error("❌ Elemento usersTableBody não encontrado");
        return;
    }
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum usuário encontrado</h5>
                    <p class="text-muted">Tente ajustar os filtros de pesquisa</p>
                </td>
            </tr>
        `;
        return;
    }
    
    const normalizePayment = (val) => {
        if (!val) return 'pending';
        const v = String(val).toLowerCase();
        if (v === 'paid') return 'approved';
        if (v === 'failed') return 'rejected';
        return v;
    };

    tbody.innerHTML = users.map(user => {
        const paymentCanon = normalizePayment(user.payment_status);
        return `
        <tr>
            <td class="col-id">
                <div class="d-flex align-items-center">
                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill fw-bold">
                        ${user.id}
                    </span>
                </div>
            </td>
            <td>
                <div class="user-info">
                    <div class="user-name">${escapeHtml(user.nome)}</div>
                    <div class="user-email">${escapeHtml(user.email)}</div>
                </div>
            </td>
            <td class="col-plan">
                <span class="badge-plan-${user.plan_type}">
                    <i class="fas fa-crown me-1"></i>
                    ${getPlanDisplayName(user.plan_type)}
                </span>
            </td>
            <td class="col-payment">
                <span class="badge-payment-${paymentCanon}">
                    <i class="fas fa-credit-card me-1"></i>
                    ${getPaymentDisplayName(paymentCanon)}
                </span>
            </td>
            <td class="col-actions">
                <div class="d-flex justify-content-center gap-1">
                    <button class="btn-action btn-view" onclick="viewUserDetails(${user.id})" title="Ver detalhes">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action btn-edit" onclick="openQuickEdit(${user.id}, '${user.status}', '${user.plan_type}', '${user.payment_status}')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteUser(${user.id}, '${escapeHtml(user.nome)}')" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `}).join('');
}

/**
 * Renderiza a paginação
 */
function renderPagination(pagination) {
    console.log("🎨 Renderizando paginação...");
    
    const container = document.getElementById('paginationContainer');
    
    if (!container) {
        console.error("❌ Elemento paginationContainer não encontrado");
        return;
    }
    
    const { current_page, total_pages, total_users } = pagination;
    
    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // Botão anterior
    if (current_page > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${current_page - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }
    
    // Páginas
    const startPage = Math.max(1, current_page - 2);
    const endPage = Math.min(total_pages, current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Botão próximo
    if (current_page < total_pages) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${current_page + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    container.innerHTML = paginationHTML;
}

/**
 * Atualiza os cards de estatísticas
 */
function updateStatsCards(stats) {
    console.log("📊 Atualizando cards de estatísticas...");
    
    const totalUsersEl = document.getElementById('totalUsersCount');
    const activeUsersEl = document.getElementById('activeUsersCount');
    const paidPlansEl = document.getElementById('paidPlansCount');
    const approvedPaymentsEl = document.getElementById('approvedPaymentsCount');
    
    // Atualizar cards principais
    if (totalUsersEl) totalUsersEl.textContent = stats.total_usuarios || 0;
    if (activeUsersEl) activeUsersEl.textContent = stats.usuarios_ativos || 0;
    if (paidPlansEl) paidPlansEl.textContent = (stats.usuarios_basic || 0) + (stats.usuarios_premium || 0);
    if (approvedPaymentsEl) approvedPaymentsEl.textContent = stats.pagamentos_aprovados || 0;
    // Atualizar indicadores de crescimento (se disponíveis)
    const setGrowth = (wrapId, value, iconUp = 'fa-arrow-up', iconDown = 'fa-arrow-down') => {
        const wrap = document.getElementById(wrapId + 'Wrap') || document.getElementById(wrapId);
        const span = document.getElementById(wrapId);
        if (!span || !wrap) return;
        let v = value;
        if (v === undefined || v === null || v === '') {
            wrap.classList.remove('positive', 'negative');
            span.textContent = '—';
            return;
        }
        // normalizar string/number para número (aceita +, -, %, vírgula e textos)
        if (typeof v === 'string') {
            const m = v.match(/[+-]?\d+[\.,]?\d*/);
            v = m ? m[0].replace(',','.') : v.replace('%','');
        }
        v = Number(v);
        if (isNaN(v)) {
            wrap.classList.remove('positive', 'negative');
            span.textContent = String(value);
            return;
        }
        // arredondar para ficar como no dashboard
        v = Math.round(v);
        const positive = v >= 0;
        wrap.classList.remove('positive', 'negative');
        wrap.classList.add(positive ? 'positive' : 'negative');
        span.textContent = `${positive ? '+' : ''}${v}% este mês`;
        const icon = wrap.querySelector('i');
        if (icon) {
            icon.classList.remove(iconUp, iconDown);
            icon.classList.add(positive ? iconUp : iconDown);
        }
    };

    // Fallbacks para nomes de campos vindos do backend
    const pick = (...keys) => {
        for (const k of keys) {
            if (k in stats && stats[k] !== undefined && stats[k] !== null) return stats[k];
        }
        return undefined;
    };

    let totalGrowth = pick('growth_total_usuarios','total_usuarios_growth','growth_total','users_growth','growth_users');
    let activeGrowth = pick('growth_usuarios_ativos','usuarios_ativos_growth','growth_active_users','active_users_growth');
    let approvedGrowth = pick('growth_pagamentos_aprovados','pagamentos_aprovados_growth','growth_payments_approved','payments_approved_growth');
    const paidCombinedGrowth = pick('growth_planos_pagos','planos_pagos_growth');
    // Se não houver combinado, somar basic+premium (melhor que nada)
    const paidGrowth = paidCombinedGrowth ?? ((stats.growth_usuarios_basic ?? 0) + (stats.growth_usuarios_premium ?? 0));

    // Defaults estáticos (como no dashboard) quando não houver dados do backend
    if (totalGrowth === undefined) totalGrowth = 12;
    if (activeGrowth === undefined) activeGrowth = 8;
    if (approvedGrowth === undefined) approvedGrowth = 5;

    setGrowth('totalUsersGrowth', totalGrowth);
    setGrowth('activeUsersGrowth', activeGrowth);
    setGrowth('paidPlansGrowth', paidGrowth ?? 6);
    setGrowth('approvedPaymentsGrowth', approvedGrowth);
}

/**
 * Aplica filtros
 */
function applyFilters() {
    console.log("🔍 Aplicando filtros...");
    
    const searchInput = document.getElementById('searchUsers');
    const planSelect = document.getElementById('filterPlan');
    
    if (searchInput) currentSearch = searchInput.value;
    if (planSelect) currentPlan = planSelect.value;
    
    currentPage = 1;
    
    loadUsersData();
}

/**
 * Muda de página
 */
function changePage(page) {
    console.log("📄 Mudando para página:", page);
    
    currentPage = page;
    loadUsersData();
}

/**
 * Visualiza detalhes do usuário
 */
async function viewUserDetails(userId) {
    console.log("👁️ Visualizando detalhes do usuário:", userId);
    
    try {
        const response = await fetch(`${window.URLADM}admin-users/viewUser?id=${userId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showUserDetailsModal(data.user, data.anuncios);
        } else {
            throw new Error(data.message || 'Erro ao carregar detalhes do usuário');
        }
    } catch (error) {
        console.error("❌ Erro ao carregar detalhes do usuário:", error);
        showError("Erro ao carregar detalhes do usuário: " + error.message);
    }
}

/**
 * Mostra modal de detalhes do usuário
 */
function showUserDetailsModal(user, anuncios) {
    console.log("🎭 Mostrando modal de detalhes...");
    const modalEl = document.getElementById('userDetailsModal');
    const modal = new bootstrap.Modal(modalEl);
    const content = document.getElementById('userDetailsContent');
    const initials = (user.nome || '?').split(' ').map(p => p[0]).slice(0,2).join('').toUpperCase();
    const createdAt = user.created ? formatDate(user.created) : '—';
    const lastAccess = user.ultimo_acesso ? formatDate(user.ultimo_acesso) : 'Nunca';
    // Plano normalizado com fallback (mapeia variações pt-BR para chaves CSS)
    const rawPlan = (user.plan_type || user.plan || 'free').toString().toLowerCase();
    const canonPlan = (p => {
        if (p === 'basico' || p === 'básico') return 'basic';
        if (p === 'gratis' || p === 'grátis' || p === 'gratuito' || p === 'free') return 'free';
        if (p === 'premium' || p === 'premio') return 'premium';
        return p; // assume já é 'basic' | 'premium' | 'free'
    })(rawPlan);
    const planType = canonPlan;
    const planLabel = (typeof getPlanDisplayName === 'function') ? getPlanDisplayName(planType) : (planType === 'premium' ? 'Premium' : planType === 'basic' ? 'Básico' : 'Free');

    content.innerHTML = `
        <div class="p-3 border rounded-3 mb-3 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;background:#eef2ff;color:#4f46e5;font-weight:800;">${initials}</div>
            <div class="flex-grow-1">
                <div class="fw-bold text-dark">${escapeHtml(user.nome)}</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="fw-bold mb-3">Informações Pessoais</div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-user me-2 text-primary"></i>Nome</div><div class="text-dark text-truncate-wrap">${escapeHtml(user.nome || '—')}</div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-envelope me-2 text-info"></i>E-mail</div><div class="text-dark text-truncate-wrap">${escapeHtml(user.email || '—')}</div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-phone me-2 text-success"></i>Telefone</div><div class="text-dark">${escapeHtml(user.telefone || user.phone || '—')}</div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-id-card me-2 text-warning"></i>CPF</div><div class="text-dark">${escapeHtml(user.cpf || '—')}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="fw-bold mb-3">Conta e Acesso</div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-hashtag me-2 text-secondary"></i>ID</div><div class="text-dark">${user.id}</div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-calendar me-2 text-primary"></i>Cadastrado</div><div class="text-dark">${createdAt}</div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-sign-in-alt me-2 text-info"></i>Último acesso</div><div class="text-dark">${lastAccess}</div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-toggle-on me-2 text-success"></i>Status</div><div><span class="badge badge-status-${user.status}">${getStatusDisplayName(user.status)}</span></div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-crown me-2 text-warning"></i>Plano</div><div><span class="badge badge-plan-${planType} text-dark">${planLabel}</span></div></div>
                        <div class="d-flex justify-content-between py-1"><div class="text-muted d-flex align-items-center"><i class="fas fa-credit-card me-2 text-primary"></i>Pagamento</div><div><span class="badge badge-payment-${user.payment_status}">${getPaymentDisplayName(user.payment_status)}</span></div></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Debug helper to inspect modal/backdrop/body state
    const logModalDebug = (phase) => {
        try {
            const dlg = modalEl.querySelector('.modal-dialog');
            const cnt = modalEl.querySelector('.modal-content');
            const body = modalEl.querySelector('.modal-body');
            const cs = (el) => el ? window.getComputedStyle(el) : null;
            const csModal = cs(modalEl);
            const csDlg = cs(dlg);
            const csCnt = cs(cnt);
            const csBody = cs(body);
            const bRect = (el) => el ? el.getBoundingClientRect() : null;
            const rectModal = bRect(modalEl);
            const rectCnt = bRect(cnt);
            const backdrops = document.querySelectorAll('.modal-backdrop');
            const centerX = Math.floor(window.innerWidth / 2);
            const centerY = Math.floor(window.innerHeight / 2);
            const topAtCenter = document.elementFromPoint(centerX, centerY);
            const csTop = topAtCenter ? window.getComputedStyle(topAtCenter) : null;
            console.group(`🧪 MODAL DEBUG [${phase}]`);
            console.log('body.classList:', document.body.className);
            console.log('#userDetailsModal classes:', modalEl.className);
            console.log('Backdrop count:', backdrops.length, backdrops);
            console.log('Computed modal z-index/overflow/visibility:', csModal && { zIndex: csModal.zIndex, overflow: csModal.overflow, visibility: csModal.visibility, display: csModal.display, position: csModal.position });
            console.log('Computed dialog max-height/overflow:', csDlg && { maxHeight: csDlg.maxHeight, overflow: csDlg.overflow });
            console.log('Computed content max-height/overflow:', csCnt && { maxHeight: csCnt.maxHeight, overflow: csCnt.overflow });
            console.log('Computed body max-height/overflow:', csBody && { maxHeight: csBody.maxHeight, overflow: csBody.overflow });
            console.log('Rects modal/content:', { rectModal, rectCnt });
            console.log('Viewport vh:', window.innerHeight, 'vw:', window.innerWidth);
            console.log('Top element at center:', topAtCenter);
            console.log('Top element selector:', topAtCenter ? (topAtCenter.id ? `#${topAtCenter.id}` : topAtCenter.className || topAtCenter.tagName) : null);
            console.log('Top element computed (zIndex/pointerEvents):', csTop && { zIndex: csTop.zIndex, pointerEvents: csTop.pointerEvents, opacity: csTop.opacity });
            console.groupEnd();
        } catch (e) {
            console.warn('Debug modal error:', e);
        }
    };

    // Log before show
    logModalDebug('before show');
    // Attach events and show
    modalEl.addEventListener('shown.bs.modal', () => {
        logModalDebug('after shown');
        setTimeout(() => logModalDebug('after shown +100ms'), 100);
    }, { once: false });
    modalEl.addEventListener('hide.bs.modal', () => {
        console.log('🧪 MODAL DEBUG [hide] body.classList:', document.body.className);
    });
    modal.show();
}

/**
 * Abre edição rápida
 */
function openQuickEdit(userId, status, plan, payment) {
    console.log("✏️ Abrindo edição rápida para usuário:", userId);
    
    // Armazenar valores originais para comparação
    const originalValues = {
        status: status,
        plan: plan,
        payment: payment
    };
    
    // Preencher campos do formulário
    document.getElementById('editUserId').value = userId;
    const displayIdEl = document.getElementById('editUserDisplayId');
    if (displayIdEl) displayIdEl.textContent = userId;
    document.getElementById('editUserStatus').value = status;
    document.getElementById('editUserPlan').value = plan;
    document.getElementById('editPaymentStatus').value = payment;
    // Limpar inputs de usuário até carregar
    const nameInput = document.getElementById('editFullName');
    const emailInput = document.getElementById('editEmail');
    const phoneInput = document.getElementById('editPhone');
    const cpfInput = document.getElementById('editCpf');
    if (nameInput) nameInput.value = '';
    if (emailInput) emailInput.value = '';
    if (phoneInput) phoneInput.value = '';
    if (cpfInput) cpfInput.value = '';
    
    // Armazenar valores originais nos datasets
    document.getElementById('editUserStatus').dataset.original = status;
    document.getElementById('editUserPlan').dataset.original = plan;
    document.getElementById('editPaymentStatus').dataset.original = payment;
    
    // Carregar informações adicionais do usuário
    const contentName = document.getElementById('editUserName');
    const contentEmail = document.getElementById('editUserEmail');
    const contentCreated = document.getElementById('editUserCreatedAt');
    if (contentName) contentName.textContent = 'Carregando...';
    if (contentEmail) contentEmail.textContent = 'Carregando...';
    if (contentCreated) contentCreated.innerHTML = '<span class="text-muted">Carregando...</span>';
    loadUserDetailsForEdit(userId);
    
    // Configurar listeners para detectar mudanças
    setupQuickEditChangeListeners(originalValues);
    
    const modal = new bootstrap.Modal(document.getElementById('quickEditModal'));
    const saveBtn = document.getElementById('saveQuickEditBtn');
    if (saveBtn) saveBtn.disabled = true;
    modal.show();
}

/**
 * Carrega detalhes do usuário para exibição no modal
 */
async function loadUserDetailsForEdit(userId) {
    try {
        const response = await fetch(`${window.URLADM}admin-users/viewUser?id=${userId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.success && result.user) {
                const user = result.user;

                // Normalização dos campos vindos do backend
                const nameVal = user.nome || user.name || user.full_name || '';
                const emailVal = user.email || user.user_email || '';
                const phoneVal = user.telefone || user.phone || user.celular || '';
                const cpfVal = user.cpf || user.documento || user.cpf_cnpj || '';

                // Atualizar informações do usuário (header)
                const headerNameEl = document.getElementById('editUserName');
                const headerEmailEl = document.getElementById('editUserEmail');
                if (headerNameEl) headerNameEl.textContent = nameVal || 'Nome não informado';
                if (headerEmailEl) headerEmailEl.textContent = emailVal || 'Email não informado';

                // Popular inputs editáveis (se existirem)
                const nameInput = document.getElementById('editFullName');
                const emailInput = document.getElementById('editEmail');
                const phoneInput = document.getElementById('editPhone');
                const cpfInput = document.getElementById('editCpf');
                if (nameInput) { nameInput.value = nameVal; nameInput.dataset.original = nameInput.value; }
                if (emailInput) { emailInput.value = emailVal; emailInput.dataset.original = emailInput.value; }
                if (phoneInput) { phoneInput.value = phoneVal; phoneInput.dataset.original = phoneInput.value; }
                if (cpfInput) { cpfInput.value = cpfVal; cpfInput.dataset.original = cpfInput.value; }

                // Guardar data de cadastro somente se o elemento existir (pode ter sido removido)
                const createdEl = document.getElementById('editUserCreatedAt');
                if (createdEl) {
                    const createdRaw = user.created || user.created_at;
                    if (createdRaw) {
                        const date = new Date(createdRaw);
                        const formattedDate = isNaN(date.getTime())
                            ? String(createdRaw)
                            : date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                        createdEl.innerHTML = `<i class="fas fa-calendar text-muted me-2"></i><span class="text-dark">${formattedDate}</span>`;
                    } else {
                        createdEl.innerHTML = `<i class="fas fa-calendar text-muted me-2"></i><span class="text-muted">Não informado</span>`;
                    }
                }
            }
        }
    } catch (error) {
        console.error("❌ Erro ao carregar detalhes do usuário:", error);
        // Manter valores padrão em caso de erro (se elementos existirem)
        const headerNameEl = document.getElementById('editUserName');
        const headerEmailEl = document.getElementById('editUserEmail');
        if (headerNameEl) headerNameEl.textContent = 'Usuário #' + userId;
        if (headerEmailEl) headerEmailEl.textContent = 'Detalhes não disponíveis';
    }
}

/**
 * Configura listeners para detectar mudanças no formulário
 */
function setupQuickEditChangeListeners(originalValues) {
    const form = document.getElementById('quickEditForm');
    const changeAlert = document.getElementById('changeAlert');
    const changeDetails = document.getElementById('changeDetails');
    const saveBtn = document.getElementById('saveQuickEditBtn');
    
    // Remover listeners anteriores
    form.removeEventListener('change', handleQuickEditChange);
    
    // Adicionar novo listener
    form.addEventListener('change', function() {
        handleQuickEditChange(originalValues, changeAlert, changeDetails);
        const sEl = document.getElementById('editUserStatus');
        const pEl = document.getElementById('editUserPlan');
        const payEl = document.getElementById('editPaymentStatus');
        const nameInput = document.getElementById('editFullName');
        const emailInput = document.getElementById('editEmail');
        const phoneInput = document.getElementById('editPhone');
        const cpfInput = document.getElementById('editCpf');
        const hasChanges = (sEl && (sEl.value !== (sEl.dataset.original ?? originalValues.status)))
            || (pEl && (pEl.value !== (pEl.dataset.original ?? originalValues.plan)))
            || (payEl && (payEl.value !== (payEl.dataset.original ?? originalValues.payment)))
            || (nameInput && nameInput.value !== (nameInput.dataset.original ?? ''))
            || (emailInput && emailInput.value !== (emailInput.dataset.original ?? ''))
            || (phoneInput && phoneInput.value !== (phoneInput.dataset.original ?? ''))
            || (cpfInput && cpfInput.value !== (cpfInput.dataset.original ?? ''));
        if (saveBtn) saveBtn.disabled = !hasChanges;
    });
}

/**
 * Manipula mudanças no formulário de edição rápida
 */
function handleQuickEditChange(originalValues, changeAlert, changeDetails) {
    const currentStatus = document.getElementById('editUserStatus').value;
    const currentPlan = document.getElementById('editUserPlan').value;
    const currentPayment = document.getElementById('editPaymentStatus').value;
    
    const changes = [];
    
    if (currentStatus !== originalValues.status) {
        changes.push(`Status: ${getStatusLabel(originalValues.status)} → ${getStatusLabel(currentStatus)}`);
    }
    
    if (currentPlan !== originalValues.plan) {
        changes.push(`Plano: ${getPlanLabel(originalValues.plan)} → ${getPlanLabel(currentPlan)}`);
    }
    
    if (currentPayment !== originalValues.payment) {
        changes.push(`Pagamento: ${getPaymentLabel(originalValues.payment)} → ${getPaymentLabel(currentPayment)}`);
    }
    
    // Verificar se os elementos existem antes de manipulá-los
    if (changeAlert && changeDetails) {
        if (changes.length > 0) {
            changeDetails.innerHTML = changes.map(change => `<div class="small">• ${change}</div>`).join('');
            changeAlert.style.display = 'block';
        } else {
            changeAlert.style.display = 'none';
        }
    }
}

/**
 * Retorna rótulos para os valores
 */
function getStatusLabel(status) {
    const labels = {
        'ativo': 'Ativo',
        'inativo': 'Inativo',
        'suspenso': 'Suspenso'
    };
    return labels[status] || status;
}

function getPlanLabel(plan) {
    const labels = {
        'free': 'Gratuito',
        'basic': 'Básico',
        'premium': 'Premium'
    };
    return labels[plan] || plan;
}

function getPaymentLabel(payment) {
    const labels = {
        'pending': 'Pendente',
        'approved': 'Aprovado',
        'rejected': 'Rejeitado'
    };
    return labels[payment] || payment;
}

/**
 * Salva edição rápida
 */
async function saveQuickEdit() {
    console.log("💾 Salvando edição rápida...");
    
    const userId = document.getElementById('editUserId').value;
    const status = document.getElementById('editUserStatus').value;
    const plan = document.getElementById('editUserPlan').value;
    const payment = document.getElementById('editPaymentStatus').value;
    const nameInput = document.getElementById('editFullName');
    const emailInput = document.getElementById('editEmail');
    const phoneInput = document.getElementById('editPhone');
    const cpfInput = document.getElementById('editCpf');
    
    try {
        // Criar payload JSON conforme backend (AdminUsersController::updateUser)
        const updateData = {};
        updateData.user_id = userId;
        if (status) updateData.status = status;
        if (plan) updateData.plan_type = plan;
        if (payment) updateData.payment_status = payment;
        // Campos extras (backend atual ignora, mas mantemos para futura compatibilidade)
        if (nameInput && nameInput.value !== undefined) updateData.nome = nameInput.value;
        if (emailInput && emailInput.value !== undefined) updateData.email = emailInput.value;
        if (phoneInput && phoneInput.value !== undefined) updateData.telefone = phoneInput.value;
        if (cpfInput && cpfInput.value !== undefined) updateData.cpf = cpfInput.value;

        const response = await fetch(`${window.URLADM}admin-users/updateUser`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(updateData)
        });

        if (response.ok) {
            let result;
            const ct = response.headers.get('content-type') || '';
            if (ct.includes('application/json')) {
                result = await response.json();
            } else {
                const text = await response.text();
                const preview = text.replace(/\s+/g, ' ').slice(0, 300) + (text.length > 300 ? '…' : '');
                console.error('❌ updateUser retornou conteúdo não-JSON:', preview);
                throw new Error('Resposta do servidor não é JSON. Preview: ' + preview);
            }
            if (result && result.success) {
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickEditModal'));
                modal.hide();
                
                // Recarregar dados
                loadUsersData();
                
                showSuccess(result.message || "Usuário atualizado com sucesso!");
            } else {
                const msg = result && result.message ? result.message : 'Erro ao atualizar usuário';
                throw new Error(msg);
            }
        } else {
            throw new Error('Erro na requisição');
        }
        
    } catch (error) {
        console.error("❌ Erro ao salvar edição:", error);
        showError("Erro ao salvar alterações: " + error.message);
    }
}

/**
 * Atualiza status do usuário
 */
async function updateUserStatus(userId, status) {
    const response = await fetch(`${window.URLADM}admin-users/updateUserStatus`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ user_id: userId, status: status })
    });
    
    const data = await response.json();
    
    if (!data.success) {
        throw new Error(data.message || 'Erro ao atualizar status');
    }
}

/**
 * Atualiza plano do usuário
 */
async function updateUserPlan(userId, plan) {
    const response = await fetch(`${window.URLADM}admin-users/updateUserPlan`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ user_id: userId, plan_type: plan })
    });
    
    const data = await response.json();
    
    if (!data.success) {
        throw new Error(data.message || 'Erro ao atualizar plano');
    }
}

/**
 * Atualiza status de pagamento
 */
async function updatePaymentStatus(userId, payment) {
    const response = await fetch(`${window.URLADM}admin-users/updatePaymentStatus`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ user_id: userId, payment_status: payment })
    });
    
    const data = await response.json();
    
    if (!data.success) {
        throw new Error(data.message || 'Erro ao atualizar status de pagamento');
    }
}

/**
 * Exclui usuário
 */
async function deleteUser(userId, userName) {
    console.log("🗑️ Excluindo usuário:", userId);

    try {
        console.debug('DEBUG deleteUser: typeof window.showConfirmModal =', typeof window.showConfirmModal);
        try {
            const labelEl = document.getElementById('confirmModalLabel');
            const labelTextEl = document.getElementById('confirmModalLabelText');
            const bodyEl = document.getElementById('confirmModalBody');
            const headerEl = document.querySelector('#confirmModal .modal-header');
            const confirmBtn = document.getElementById('confirmModalConfirmBtn');
            const cancelBtn = document.getElementById('confirmModalCancelBtn');
            console.debug('DEBUG deleteUser: antes do showConfirmModal - elementos', {
                hasLabel: !!labelEl,
                hasLabelTextSpan: !!labelTextEl,
                labelHTML: labelEl ? labelEl.innerHTML : null,
                hasBody: !!bodyEl,
                bodyHTML: bodyEl ? bodyEl.innerHTML : null,
                hasHeader: !!headerEl,
                hasConfirmBtn: !!confirmBtn,
                hasCancelBtn: !!cancelBtn
            });
        } catch (e) { console.warn('WARN deleteUser: falha ao logar elementos do modal', e); }
        const confirmed = await new Promise((resolve) => {
            try {
                const modalEl = document.getElementById('confirmModal');
                const headerEl = modalEl ? modalEl.querySelector('.modal-header') : null;
                const titleEl = document.getElementById('confirmModalLabel');
                const titleTextEl = document.getElementById('confirmModalLabelText');
                const bodyEl = document.getElementById('confirmModalBody');
                const confirmBtn = document.getElementById('confirmModalConfirmBtn');
                const cancelBtn = document.getElementById('confirmModalCancelBtn');

                if (!modalEl || !titleEl || !bodyEl || !confirmBtn || !cancelBtn) {
                    console.warn('WARN deleteUser: confirmModal incompleto, usando confirm() nativo.');
                    const r = confirm('Excluir Conta do Usuário\n\nTem certeza que deseja excluir esta conta? Todos os anúncios deste usuário serão removidos. Esta ação é irreversível.');
                    resolve(!!r);
                    return;
                }

                // Estiliza cabeçalho (mantém gradiente se existir)
                if (headerEl && !headerEl.classList.contains('modal-header-beautiful')) {
                    headerEl.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    headerEl.style.color = '#fff';
                    headerEl.style.border = 'none';
                    headerEl.style.borderTopLeftRadius = '15px';
                    headerEl.style.borderTopRightRadius = '15px';
                }

                // Define título com ícone e tipografia elegante
                const title = 'Excluir Conta do Usuário';
                if (titleTextEl) {
                    // Garante ícone fixo à esquerda
                    if (!titleEl.querySelector('i')) {
                        titleEl.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-trash fa-lg me-2"></i>');
                    }
                    titleTextEl.textContent = title;
                } else {
                    titleEl.innerHTML = `<i class="fa-solid fa-trash fa-lg me-2"></i>${title}`;
                }
                // Tipografia do título
                titleEl.style.fontWeight = '700';
                titleEl.style.display = 'flex';
                titleEl.style.alignItems = 'center';
                titleEl.style.letterSpacing = '0.2px';
                titleEl.style.textShadow = '0 1px 2px rgba(0,0,0,0.15)';

                // Define corpo da mensagem
                bodyEl.innerHTML = '<p style="margin:0; font-size:16px; line-height:1.7; color:#0f172a;">Tem certeza que deseja excluir esta conta? Todos os anúncios deste usuário serão removidos. Esta ação é irreversível.</p>';

                // Configura botões
                confirmBtn.innerHTML = '<i class="fa-solid fa-trash me-1"></i>Excluir';
                cancelBtn.textContent = 'Cancelar';
                confirmBtn.classList.remove('btn-success','btn-info','btn-warning','btn-primary');
                confirmBtn.classList.add('btn-danger');
                // Botões com visual elegante
                confirmBtn.style.borderRadius = '25px';
                confirmBtn.style.padding = '10px 25px';
                confirmBtn.style.fontWeight = '600';
                cancelBtn.style.borderRadius = '25px';
                cancelBtn.style.padding = '10px 25px';
                cancelBtn.style.fontWeight = '500';

                // Remove handlers antigos
                if (confirmBtn._currentHandler) confirmBtn.removeEventListener('click', confirmBtn._currentHandler);
                if (cancelBtn._currentHandler) cancelBtn.removeEventListener('click', cancelBtn._currentHandler);

                // Adiciona novos handlers
                confirmBtn._currentHandler = () => { try { bsInstance.hide(); } catch(_){} resolve(true); };
                cancelBtn._currentHandler = () => { try { bsInstance.hide(); } catch(_){} resolve(false); };
                confirmBtn.addEventListener('click', confirmBtn._currentHandler);
                cancelBtn.addEventListener('click', cancelBtn._currentHandler);

                // Fecha no X também como cancelamento, se existir
                const closeBtn = modalEl.querySelector('.btn-close');
                if (closeBtn) {
                    if (closeBtn._currentHandler) closeBtn.removeEventListener('click', closeBtn._currentHandler);
                    closeBtn._currentHandler = () => { try { bsInstance.hide(); } catch(_){} resolve(false); };
                    closeBtn.addEventListener('click', closeBtn._currentHandler);
                }

                // Exibe modal
                const bsInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsInstance.show();
            } catch (e) {
                console.error('ERRO deleteUser: falha ao exibir modal customizado, usando confirm() nativo.', e);
                const r = confirm('Excluir Conta do Usuário\n\nTem certeza que deseja excluir esta conta? Todos os anúncios deste usuário serão removidos. Esta ação é irreversível.');
                resolve(!!r);
            }
        });
        console.debug('DEBUG deleteUser: showConfirmModal retornou -> confirmed =', confirmed);
        if (!confirmed) return;

        // Opcional: mostrar loading (se disponível)
        if (typeof window.showLoadingModal === 'function') window.showLoadingModal();

        // Enviar como JSON (o backend decodifica php://input como JSON)
        const response = await fetch(`${window.URLADM}admin-users/deleteUser`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });

        // Ocultar loading (se disponível)
        if (typeof window.hideLoadingModal === 'function') await window.hideLoadingModal();

        if (!response.ok) {
            const text = await response.text();
            throw new Error(`HTTP ${response.status}: ${text.slice(0,200)}...`);
        }

        let data;
        const ct = response.headers.get('content-type') || '';
        if (ct.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            if (text.includes('id="adminUsersContent"')) {
                showSuccess('Usuário excluído com sucesso!');
                loadUsersData();
                return;
            }
            // tentar extrair mensagem simples
            throw new Error(text.replace(/\s+/g,' ').slice(0,200) + (text.length>200?'…':''));
        }

        if (data.success) {
            showSuccess("Usuário excluído com sucesso!");
            loadUsersData();
        } else {
            throw new Error(data.message || 'Erro ao excluir usuário');
        }
    } catch (error) {
        // Ocultar loading se algo falhar
        if (typeof window.hideLoadingModal === 'function') await window.hideLoadingModal();
        console.error("❌ Erro ao excluir usuário:", error);
        showError("Erro ao excluir usuário: " + error.message);
    }
}

/**
 * Exporta usuários
 */
function exportUsers() {
    console.log("📤 Exportando usuários...");
    
    // Implementar exportação
    showInfo("Funcionalidade de exportação será implementada em breve.");
}

// Funções auxiliares
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'Nunca';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function getPlanDisplayName(plan) {
    const plans = {
        'free': 'Gratuito',
        'basic': 'Básico',
        'premium': 'Premium'
    };
    return plans[plan] || plan;
}

function getStatusDisplayName(status) {
    const statuses = {
        'ativo': 'Ativo',
        'inativo': 'Inativo',
        'suspenso': 'Suspenso'
    };
    return statuses[status] || status;
}

function getPaymentDisplayName(payment) {
    const payments = {
        'pending': 'Pendente',
        'approved': 'Aprovado',
        'rejected': 'Rejeitado',
        // suportar valores do BD diretamente em caso de uso em outros fluxos
        'paid': 'Aprovado',
        'failed': 'Rejeitado'
    };
    return payments[payment] || payment;
}

function getAnuncioStatusColor(status) {
    const colors = {
        'active': 'success',
        'pending': 'warning',
        'rejected': 'danger',
        'pausado': 'info'
    };
    return colors[status] || 'secondary';
}

function getAnuncioStatusName(status) {
    const names = {
        'active': 'Ativo',
        'pending': 'Pendente',
        'rejected': 'Rejeitado',
        'pausado': 'Pausado'
    };
    return names[status] || status;
}

// Funções de feedback
function showSuccess(message) {
    if (typeof window.showFeedbackModal === 'function') {
        window.showFeedbackModal('success', message, 'Sucesso!');
    } else {
        alert(message);
    }
}

function showError(message) {
    if (typeof window.showFeedbackModal === 'function') {
        window.showFeedbackModal('error', message, 'Erro!');
    } else {
        alert(message);
    }
}

function showInfo(message) {
    if (typeof window.showFeedbackModal === 'function') {
        window.showFeedbackModal('info', message, 'Informação');
    } else {
        alert(message);
    }
}

console.log("✅ ADMIN USERS JS carregado e pronto!");

/**
 * Função de inicialização para a página de admin de usuários
 * Esta função é chamada pelo sistema SPA
 */
window.initializeAdminUsersPage = function(fullUrl, initialData) {
    console.log("🔧 Inicializando página de admin de usuários...");
    console.log("🔧 URL:", fullUrl);
    console.log("🔧 Dados iniciais:", initialData);
    
    // Verificar se o elemento existe antes de inicializar
    if (document.getElementById('adminUsersContent')) {
        console.log("🔧 Elemento adminUsersContent encontrado - inicializando sistema...");
        initializeAdminUsers();
        setupEventListeners();
    } else {
        console.log("❌ Elemento adminUsersContent não encontrado!");
    }
    
    console.log("✅ Página de admin de usuários inicializada!");
};