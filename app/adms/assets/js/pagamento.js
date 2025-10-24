// JavaScript para página de pagamento PIX
function initializePaymentPageElements() {
    console.log('Inicializando elementos da página de pagamento');
    
    // Elementos principais - estrutura real da página carregada via SPA
    const elements = {
        // Botões principais
        generatePaymentBtn: document.getElementById('generatePaymentBtn'),
        copyPixCode: document.getElementById('copyPixCode'),
        checkPaymentBtn: document.getElementById('checkPaymentBtn'),
        newPaymentBtn: document.getElementById('newPaymentBtn'),
        
        // Seções de conteúdo
        qrCodeSection: document.getElementById('qrCodeSection'),
        pixCodeSection: document.getElementById('pixCodeSection'),
        paymentStatus: document.getElementById('paymentStatus'),
        
        // Elementos de dados
        qrCodeImage: document.getElementById('qrCodeImage'),
        pixCode: document.getElementById('pixCode'),
        planAmount: document.getElementById('planAmount')
    };
    
    // Debug: verificar quais elementos foram encontrados
    console.log('Elementos encontrados:', {
        generatePaymentBtn: !!elements.generatePaymentBtn,
        copyPixCode: !!elements.copyPixCode,
        checkPaymentBtn: !!elements.checkPaymentBtn,
        newPaymentBtn: !!elements.newPaymentBtn,
        qrCodeSection: !!elements.qrCodeSection,
        pixCodeSection: !!elements.pixCodeSection,
        paymentStatus: !!elements.paymentStatus,
        qrCodeImage: !!elements.qrCodeImage,
        pixCode: !!elements.pixCode,
        planAmount: !!elements.planAmount
    });
    
    let selectedPeriod = null;
    let currentPaymentId = null;
    let paymentCheckInterval = null;
    
    // Configurar event listeners para os botões
    if (elements.generatePaymentBtn && !elements.generatePaymentBtn.dataset.bound) {
        elements.generatePaymentBtn.dataset.bound = '1';
        elements.generatePaymentBtn.addEventListener('click', function() {
            console.log('Botão Gerar PIX clicado');
            if (typeof window.initializePayment === 'function') {
                window.initializePayment();
            } else {
                initializePayment();
            }
        });
    }
    
    if (elements.copyPixCode && !elements.copyPixCode.dataset.bound) {
        elements.copyPixCode.dataset.bound = '1';
        elements.copyPixCode.addEventListener('click', function() {
            console.log('Botão Copiar PIX clicado');
            copyPixCode();
        });
    }
    
    if (elements.checkPaymentBtn && !elements.checkPaymentBtn.dataset.bound) {
        elements.checkPaymentBtn.dataset.bound = '1';
        elements.checkPaymentBtn.addEventListener('click', function() {
            console.log('Botão Verificar Pagamento clicado');
            checkPaymentStatus();
        });
    }
    
    if (elements.newPaymentBtn && !elements.newPaymentBtn.dataset.bound) {
        elements.newPaymentBtn.dataset.bound = '1';
        elements.newPaymentBtn.addEventListener('click', function() {
            console.log('Botão Novo Pagamento clicado');
            createNewPayment();
        });
    }
    
    // Função para criar pagamento PIX
    function createPixPayment() {
        console.log('Criando pagamento PIX...');
        
        // Mostrar loading
        if (elements.generatePaymentBtn) {
            elements.generatePaymentBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gerando PIX...';
            elements.generatePaymentBtn.disabled = true;
        }
        
        const paymentData = {
            // Em rota nova, buscamos pelo tipo de plano; manter compatibilidade usando mapeamento simples
            plano_id: window.PLANO_ID || 2,
            period: selectedPeriod || '6_meses'
        };
        
        fetch(`${window.URLADM}payment/create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(paymentData)
        })
        .then(async (response) => {
            const ct = response.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                const txt = await response.text().catch(() => '');
                console.warn('createPixPayment: resposta não-JSON recebida:', (txt || '').slice(0, 300));
                return { success: false, message: 'Resposta inválida do servidor' };
            }
            return response.json();
        })
        .then(data => {
            console.log('Resposta do pagamento:', data);
            
            if (data && data.success) {
                window.currentPaymentId = data.payment_id;
                window.currentSubscriptionId = data.subscription_id || window.currentSubscriptionId || null;
                
                // Mostrar seções do PIX
                if (elements.qrCodeSection && data.qr_code_base64) {
                    elements.qrCodeSection.style.display = 'block';
                }
                
                if (elements.pixCodeSection && data.pix_copy_paste) {
                    elements.pixCodeSection.style.display = 'block';
                }
                
                // Configurar QR Code
                if (data.qr_code_base64 && elements.qrCodeImage) {
                    elements.qrCodeImage.src = `data:image/png;base64,${data.qr_code_base64}`;
                }
                
                // Configurar código PIX
                if (data.pix_copy_paste && elements.pixCode) {
                    elements.pixCode.value = data.pix_copy_paste;
                }
                
                // Mostrar botões de ação
                if (elements.checkPaymentBtn) {
                    elements.checkPaymentBtn.style.display = 'inline-block';
                }
                if (elements.newPaymentBtn) {
                    elements.newPaymentBtn.style.display = 'inline-block';
                }
                
                // Iniciar verificação automática
                startPaymentCheck();
                
            } else {
                alert('Erro ao criar pagamento: ' + (data && data.message ? data.message : 'Resposta inválida'));
                if (elements.generatePaymentBtn) {
                    elements.generatePaymentBtn.innerHTML = '<i class="fas fa-qrcode me-2"></i>Gerar PIX';
                    elements.generatePaymentBtn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Erro ao criar pagamento. Tente novamente.');
            if (elements.generatePaymentBtn) {
                elements.generatePaymentBtn.innerHTML = '<i class="fas fa-qrcode me-2"></i>Gerar PIX';
                elements.generatePaymentBtn.disabled = false;
            }
        });
    }
    
    // Função para alternar entre opções do PIX
    function switchPixOption(option) {
        // Mostrar/esconder conteúdo
        if (elements.qrCodeSection) {
            elements.qrCodeSection.style.display = option === 'qr-code' ? 'block' : 'none';
        }
        if (elements.pixCodeSection) {
            elements.pixCodeSection.style.display = option === 'copy-paste' ? 'block' : 'none';
        }
    }
    
    // Função para copiar código PIX
    function copyPixCode() {
        if (!elements.pixCode || !elements.copyPixCode) {
            console.warn('Elementos para copiar PIX não encontrados');
            return;
        }
        
        elements.pixCode.select();
        elements.pixCode.setSelectionRange(0, 99999); // Para mobile
        
        try {
            document.execCommand('copy');
            
            // Feedback visual
            const originalText = elements.copyPixCode.innerHTML;
            elements.copyPixCode.innerHTML = '<i class="fas fa-check me-1"></i>Copiado!';
            elements.copyPixCode.classList.add('btn-success');
            elements.copyPixCode.classList.remove('btn-outline-primary');
                
                setTimeout(() => {
                elements.copyPixCode.innerHTML = originalText;
                elements.copyPixCode.classList.remove('btn-success');
                elements.copyPixCode.classList.add('btn-outline-primary');
                }, 2000);
            
        } catch (err) {
            console.error('Erro ao copiar:', err);
            alert('Erro ao copiar código PIX');
        }
    }
    
    // Função para verificar status do pagamento
    function checkPaymentStatus() {
        const subId = window.currentSubscriptionId;
        if (!subId) return;
        console.log('Verificando status do pagamento (subscription_id)...');
        fetch(`${window.URLADM}payment/status?subscription_id=${encodeURIComponent(subId)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async (response) => {
            const ct = response.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                const txt = await response.text().catch(() => '');
                console.warn('checkPaymentStatus (top): resposta não-JSON recebida:', (txt || '').slice(0, 300));
                return { success: false };
            }
            return response.json();
        })
        .then(data => {
            console.log('Status do pagamento:', data);
            if (data && data.success) {
                if (data.status === 'approved' || data.status === 'paid_awaiting_admin') {
                    showPaymentSuccess();
                } else {
                    console.log('Pagamento ainda pendente');
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status:', error);
        });
    }
    
    // Função para iniciar verificação automática
    function startPaymentCheck() {
        // Evitar múltiplos timers (SPA + reload)
        if (window.paymentCheckInterval) {
            clearInterval(window.paymentCheckInterval);
            window.paymentCheckInterval = null;
        }
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
            paymentCheckInterval = null;
        }
        window.paymentCheckInterval = setInterval(() => {
            checkPaymentStatus();
        }, 10000);
        paymentCheckInterval = window.paymentCheckInterval;
    }
    
    // Função para parar verificação automática
    function stopPaymentCheck() {
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
            paymentCheckInterval = null;
        }
        if (window.paymentCheckInterval) {
            clearInterval(window.paymentCheckInterval);
            window.paymentCheckInterval = null;
        }
    }
    
    // Função para mostrar sucesso do pagamento
    function showPaymentSuccess() {
        stopPaymentCheck();
        
        // Mostrar modal de sucesso
        if (typeof window.showFeedbackModal === 'function') {
            window.showFeedbackModal('success', 'Pagamento Aprovado!', 'Seu pagamento foi processado com sucesso. Redirecionando...');
        } else {
            alert('Pagamento aprovado! Redirecionando...');
        }
        
        // Redirecionar para dashboard
        setTimeout(() => {
            window.location.href = window.URLADM + 'dashboard';
        }, 2000);
    }
    
    // Função para mostrar erro do pagamento
    function showPaymentError(message) {
        stopPaymentCheck();
        
        if (typeof window.showFeedbackModal === 'function') {
            window.showFeedbackModal('error', 'Erro no Pagamento', message);
        } else {
            alert('Erro: ' + message);
        }
    }
    
    // Função para cancelar pagamento
    function cancelPayment() {
        stopPaymentCheck();
        
        // Esconder seções do PIX
        if (elements.qrCodeSection) {
            elements.qrCodeSection.style.display = 'none';
        }
        if (elements.pixCodeSection) {
            elements.pixCodeSection.style.display = 'none';
        }
        
        // Esconder botões de ação
        if (elements.checkPaymentBtn) {
            elements.checkPaymentBtn.style.display = 'none';
        }
        if (elements.newPaymentBtn) {
            elements.newPaymentBtn.style.display = 'none';
        }
        
        // Resetar botão principal
        if (elements.generatePaymentBtn) {
            elements.generatePaymentBtn.innerHTML = '<i class="fas fa-qrcode me-2"></i>Gerar PIX';
            elements.generatePaymentBtn.disabled = false;
        }
        
        selectedPeriod = null;
        window.currentPaymentId = null;
    }
    
    // Função para formatar tempo de expiração
    function formatExpirationTime(expiresAt) {
        const now = new Date();
        const expires = new Date(expiresAt);
        const diff = expires - now;
        
        if (diff <= 0) {
            return 'Expirado';
        }
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
    
    // Atualizar contador de expiração
    function updateExpirationCounter() {
        if (elements.expiresAt && elements.expiresAt.textContent !== '--:--') {
            const currentTime = elements.expiresAt.textContent;
            if (currentTime !== 'Expirado') {
                // Implementar lógica de contagem regressiva se necessário
            }
        }
    }
    
    // Atualizar contador a cada segundo
    setInterval(updateExpirationCounter, 1000);
    
    // Função para criar novo pagamento
    function createNewPayment() {
        if (confirm('Deseja gerar um novo pagamento? O pagamento anterior será cancelado.')) {
            currentPaymentId = null;
            stopPaymentCheck();
            initializePayment();
        }
    }
    
    // Função para inicializar pagamento (chamada pelo botão)
    function initializePayment() {
        console.log('Inicializando pagamento...');
        createPixPayment();
    }
}

// Função para ajustar posição do conteúdo de pagamento
function adjustPaymentContentPosition() {
    try {
        const sidebar = document.getElementById('sidebar');
        const paymentContainer = document.querySelector('#paymentContent > .container');
        console.log('adjustPaymentContentPosition: running');
        
        if (!paymentContainer) { 
            console.log('adjustPaymentContentPosition: no paymentContainer found'); 
            return;
        }

        const sidebarWidth = sidebar ? sidebar.getBoundingClientRect().width : 0;
        const viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
        const containerWidth = paymentContainer.getBoundingClientRect().width;
        console.log('adjustPaymentContentPosition: sidebarWidth=', sidebarWidth, 'viewportWidth=', viewportWidth, 'containerWidth=', containerWidth);

        // área disponível à direita do sidebar
        const available = Math.max(0, viewportWidth - sidebarWidth);

        // posição desejada (em relação ao viewport) para centrar dentro da área disponível
        let desiredLeft = sidebarWidth + Math.max(0, (available - containerWidth) / 2);

        // calcular margin-left relativa ao pai
        const parentLeft = paymentContainer.parentElement.getBoundingClientRect().left;
        let marginLeft = desiredLeft - parentLeft;
        if (marginLeft < 0) marginLeft = 0;

        paymentContainer.style.marginLeft = Math.round(marginLeft) + 'px';
        paymentContainer.style.marginRight = 'auto';
        console.log('adjustPaymentContentPosition: applied marginLeft=', Math.round(marginLeft));
    } catch (e) {
        console.warn('adjustPaymentContentPosition erro', e);
    }
}

// Variáveis globais para pagamento (evitar redeclaração quando payment.php já definiu com var)
if (typeof window.currentPaymentId === 'undefined') {
    window.currentPaymentId = null;
}
if (typeof window.paymentCheckInterval === 'undefined') {
    window.paymentCheckInterval = null;
}

// Funções globais para pagamento
function initializePayment() {
    console.log('Inicializando pagamento...');
    createPixPayment();
}

function copyPixCode() {
    const pixCode = document.getElementById('pixCode');
    const copyBtn = document.getElementById('copyPixCode');
    
    if (!pixCode || !copyBtn) {
        console.warn('Elementos para copiar PIX não encontrados');
        return;
    }
    
    pixCode.select();
    pixCode.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        
        // Feedback visual
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check me-1"></i>Copiado!';
        copyBtn.classList.add('btn-success');
        copyBtn.classList.remove('btn-outline-primary');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('btn-success');
            copyBtn.classList.add('btn-outline-primary');
        }, 2000);
        
    } catch (err) {
        console.error('Erro ao copiar:', err);
        alert('Erro ao copiar código PIX');
    }
}

function checkPaymentStatus() {
    // Preferir checar por assinatura (novo fluxo)
    const subId = window.currentSubscriptionId;
    if (!subId) return;
    console.log('Verificando status do pagamento (subscription_id)...');
    fetch(`${window.URLADM}payment/status?subscription_id=${encodeURIComponent(subId)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async (response) => {
        const ct = response.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const txt = await response.text().catch(() => '');
            console.warn('checkPaymentStatus: resposta não-JSON recebida:', (txt || '').slice(0, 300));
            return { success: false };
        }
        return response.json();
    })
    .then(data => {
        if (!data) return;
        console.log('Status do pagamento:', data);
        if (data.success) {
            if (data.status === 'approved' || data.status === 'paid_awaiting_admin') {
                showPaymentSuccess();
            } else {
                console.log('Pagamento ainda pendente');
            }
        }
    })
    .catch(error => {
        console.error('Erro ao verificar status:', error);
    });
}

function createNewPayment() {
    if (confirm('Deseja gerar um novo pagamento? O pagamento anterior será cancelado.')) {
        currentPaymentId = null;
        stopPaymentCheck();
        initializePayment();
    }
}

function createPixPayment() {
    console.log('Criando pagamento PIX...');
    const generateBtn = document.getElementById('generatePaymentBtn');
    if (generateBtn) {
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gerando PIX...';
        generateBtn.disabled = true;
    }
    const paymentData = {
        plano_id: window.PLANO_ID || 2,
        period: '6_meses'
    };
    fetch(`${window.URLADM}payment/create`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(paymentData)
    })
    .then(async (response) => {
        const ct = response.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const txt = await response.text().catch(() => '');
            console.warn('createPixPayment(legacy): resposta não-JSON recebida:', (txt || '').slice(0, 300));
            return { success: false, message: 'Resposta inválida do servidor' };
        }
        return response.json();
    })
    .then(data => {
        console.log('Resposta do pagamento:', data);
        if (data && data.success) {
            window.currentPaymentId = data.payment_id;
            window.currentSubscriptionId = data.subscription_id || window.currentSubscriptionId || null;
            // Mostrar seções do PIX
            const qrCodeSection = document.getElementById('qrCodeSection');
            const pixCodeSection = document.getElementById('pixCodeSection');
            if (qrCodeSection && data.qr_code_base64) {
                qrCodeSection.style.display = 'block';
                const qrCodeImage = document.getElementById('qrCodeImage');
                if (qrCodeImage) {
                    qrCodeImage.src = `data:image/png;base64,${data.qr_code_base64}`;
                }
            }
            if (pixCodeSection && data.pix_copy_paste) {
                pixCodeSection.style.display = 'block';
                const pixCode = document.getElementById('pixCode');
                if (pixCode) {
                    pixCode.value = data.pix_copy_paste;
                }
            }
            // Mostrar botões de ação
            const checkBtn = document.getElementById('checkPaymentBtn');
            const newBtn = document.getElementById('newPaymentBtn');
            if (checkBtn) checkBtn.style.display = 'inline-block';
            if (newBtn) newBtn.style.display = 'inline-block';
            // Iniciar verificação automática
            startPaymentCheck();
        } else {
            alert('Erro ao criar pagamento: ' + (data && data.message ? data.message : 'Resposta inválida'));
            if (generateBtn) {
                generateBtn.innerHTML = '<i class="fas fa-qrcode me-2"></i>Gerar PIX';
                generateBtn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        alert('Erro ao criar pagamento. Tente novamente.');
        if (generateBtn) {
            generateBtn.innerHTML = '<i class="fas fa-qrcode me-2"></i>Gerar PIX';
            generateBtn.disabled = false;
        }
    });
}

function startPaymentCheck() {
    if (window.paymentCheckInterval) {
        clearInterval(window.paymentCheckInterval);
        window.paymentCheckInterval = null;
    }
    window.paymentCheckInterval = setInterval(() => {
        checkPaymentStatus();
    }, 10000);
}

function stopPaymentCheck() {
    if (window.paymentCheckInterval) {
        clearInterval(window.paymentCheckInterval);
        window.paymentCheckInterval = null;
    }
}

function showPaymentSuccess() {
    stopPaymentCheck();
    
    // Mostrar modal de sucesso
    if (typeof window.showFeedbackModal === 'function') {
        window.showFeedbackModal('success', 'Pagamento Aprovado!', 'Seu pagamento foi processado com sucesso. Redirecionando...');
    } else {
        alert('Pagamento aprovado! Redirecionando...');
    }
    
    // Redirecionar para dashboard
    setTimeout(() => {
        window.location.href = window.URLADM + 'dashboard';
    }, 2000);
}

function showPaymentError(message) {
    stopPaymentCheck();
    
    if (typeof window.showFeedbackModal === 'function') {
        window.showFeedbackModal('error', 'Erro no Pagamento', message);
    } else {
        alert('Erro: ' + message);
    }
}

// Expor funções globalmente para uso pelo HTML
window.initializePayment = initializePayment;
window.copyPixCode = copyPixCode;
window.checkPaymentStatus = checkPaymentStatus;
window.createNewPayment = createNewPayment;

// Função global para inicialização via SPA
window.initializePaymentPage = function(fullUrl, initialData) {
    if (window.PAYMENT_V2_ACTIVE === true || document.querySelector('#paymentContent[data-payment-v2="1"]')) {
        console.log('pagamento.js: v2 ativo (SPA/flag), ignorando initializePaymentPage');
        return;
    }
    console.log('initializePaymentPage chamado via SPA para:', fullUrl);
    setTimeout(() => {
        initializePaymentPageElements();
        setTimeout(() => { adjustPaymentContentPosition(); }, 100);
    }, 50);
};

// Expor função de ajuste globalmente
window.adjustPaymentContentPosition = adjustPaymentContentPosition;

// Inicialização tradicional para carregamento direto da página
document.addEventListener('DOMContentLoaded', function() {
    if (window.PAYMENT_V2_ACTIVE === true || document.querySelector('#paymentContent[data-payment-v2="1"]')) {
        console.log('pagamento.js: v2 ativo (DOMContentLoaded), ignorando inicialização padrão');
        return;
    }
    console.log('Página de pagamento carregada via DOMContentLoaded');
    initializePaymentPageElements();
    setTimeout(() => { adjustPaymentContentPosition(); }, 100);
});

// Ajustar posição em caso de redimensionamento
window.addEventListener('resize', () => {
    setTimeout(adjustPaymentContentPosition, 50);
});