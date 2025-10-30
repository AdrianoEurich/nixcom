// general-utils.js
// Versão 42 - Adicionado listener de evento para o link de exclusão de conta
console.info('INFO JS: general-utils.js (Versão 42 - Adicionado listener para exclusão de conta) carregado. Configurando funcionalidades gerais.');

// Variáveis globais para armazenar referências aos elementos e instâncias dos modais Bootstrap
let feedbackModalElement;
let feedbackModalInstance;
let feedbackModalLabel;
let feedbackMessage;
let feedbackIcon;
let feedbackOkButton;
let feedbackModalHeader;

let confirmModalElement;
let confirmModalInstance;
let confirmModalLabel;
let confirmModalBody;
let confirmModalConfirmBtn;
let confirmModalCancelBtn;
let confirmModalHeader;

let loadingModalElement;
let loadingModalInstance;

// Utilitário global: limpeza de ambiente de modais
function cleanupModalEnvironment() {
    try {
        // Remover quaisquer backdrops órfãos
        document.querySelectorAll('.modal-backdrop').forEach(el => { try { el.remove(); } catch(_){} });
        // Resetar classes/estilos do body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    } catch(_) {}
}

// Helper global: debug de modais/backdrops no DOM
window.debugModals = function() {
    try {
        const open = document.querySelectorAll('.modal.show').length;
        const backs = document.querySelectorAll('.modal-backdrop').length;
        console.log('openModals:', open, 'backdrops:', backs);
        [...document.querySelectorAll('.modal, .modal-backdrop')].forEach((el, i) => {
            const st = window.getComputedStyle(el);
            console.log(i, el.id || el.className, 'zIndex:', st.zIndex, 'display:', st.display, 'aria-hidden:', el.getAttribute('aria-hidden'));
        });
    } catch (e) {
        console.warn('debugModals error', e);
    }
};

// =============================================
// 1. MODAL DE FEEDBACK (Função Global)
// =============================================

/**
 * Exibe um modal de feedback com um tipo específico (sucesso/erro/info/warning/primary) e mensagem.
 * @param {string} type - 'success', 'error', 'info', 'warning', ou 'primary'.
 * @param {string} message - A mensagem a ser exibida.
 * @param {string} [title=''] - O título do modal.
 * @param {number} [autoCloseDelay=3000] - Opcional: Atraso em ms para fechar automaticamente (0 para não fechar).
 */
window.showFeedbackModal = function(type, message, title = '', autoCloseDelay = 3000) {
    // Preferir o gerenciador global centralizado quando disponível
    try {
        if (window.NixcomModalManager && typeof window.NixcomModalManager.showSimple === 'function') {
            // mapear parâmetros para a API do manager
            window.NixcomModalManager.showSimple(type, message, title || null, autoCloseDelay);
            // garantir limpeza de orfãos
            if (typeof window.NixcomModalManager.cleanOrphans === 'function') {
                setTimeout(window.NixcomModalManager.cleanOrphans, 300);
            }
            return;
        }
    } catch (e) {
        console.warn('NixcomModalManager indisponível, usando implementação legada', e);
    }

    console.log(`INFO JS: showFeedbackModal chamado - Tipo: ${type}, Mensagem: ${message}, Título: ${title}`);

    // Verificar se há loading modal aberto e fechá-lo primeiro
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal && loadingModal.classList.contains('show')) {
        console.log('🔧 DEBUG: Loading modal ainda aberto, fechando primeiro...');
        // Race entre o fechamento e um timeout de segurança
        const closePromise = window.hideLoadingModal();
        const timeoutPromise = new Promise(resolve => setTimeout(resolve, 800));
        Promise.race([closePromise, timeoutPromise]).then(() => {
            console.log('🔧 DEBUG: Loading modal fechado (ou timeout), aguardando para mostrar feedback...');
            setTimeout(() => {
                showFeedbackModalInternal(type, message, title, autoCloseDelay);
            }, 300);
        });
        return;
    }

    // Limpar ambiente e fechar quaisquer outros modais antes de exibir
    cleanupModalEnvironment();
    try {
        if (window.bootstrap) {
            document.querySelectorAll('.modal.show').forEach((m) => {
                try { const inst = window.bootstrap.Modal.getInstance(m) || new window.bootstrap.Modal(m); inst.hide(); } catch(_){}
            });
        }
    } catch(_){ }
    showFeedbackModalInternal(type, message, title, autoCloseDelay);
};

function showFeedbackModalInternal(type, message, title = '', autoCloseDelay = 3000) {
    // Sempre tentar re-inicializar os elementos para garantir que estão disponíveis
    console.log('INFO JS: Re-inicializando elementos do feedbackModal...');
    
    feedbackModalElement = document.getElementById('feedbackModal');
    if (feedbackModalElement) {
        feedbackModalInstance = new bootstrap.Modal(feedbackModalElement);
        feedbackModalLabel = document.getElementById('feedbackModalLabel');
        feedbackMessage = document.getElementById('feedbackMessage');
        feedbackIcon = document.getElementById('feedbackIcon');
        feedbackOkButton = document.getElementById('feedbackModalOkBtn');
        feedbackModalHeader = feedbackModalElement.querySelector('.modal-header');
        
        console.log('DEBUG JS: Elementos do feedbackModal re-inicializados:', {
            modalElement: !!feedbackModalElement,
            modalInstance: !!feedbackModalInstance,
            modalTitle: !!feedbackModalLabel,
            feedbackMessage: !!feedbackMessage,
            feedbackIcon: !!feedbackIcon,
            okButton: !!feedbackOkButton,
            modalHeader: !!feedbackModalHeader
        });
    }
    
    // Se ainda não funcionar, tentar criar o modal dinamicamente
    if (!feedbackModalElement || !feedbackModalInstance || !feedbackModalLabel || !feedbackMessage || !feedbackIcon || !feedbackOkButton || !feedbackModalHeader) {
        console.warn('AVISO JS: Elementos do feedbackModal não encontrados. Tentando criar modal dinamicamente...');
        
        // Criar modal dinamicamente
        const modalHTML = `
            <div class="modal fade modal-theme-login" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="feedbackModalLabel">Sucesso!</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <i class="fas fa-check-circle fa-3x text-success mb-3" id="feedbackIcon"></i>
                            <p id="feedbackMessage">${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="feedbackModalOkBtn" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Adicionar ao body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Re-inicializar elementos
        feedbackModalElement = document.getElementById('feedbackModal');
        if (feedbackModalElement) {
            // Verificar se Bootstrap está disponível
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                feedbackModalInstance = new bootstrap.Modal(feedbackModalElement);
            } else {
                console.warn('AVISO JS: Bootstrap não está disponível, usando modal nativo');
                // Usar modal nativo se Bootstrap não estiver disponível
                feedbackModalElement.style.display = 'block';
                feedbackModalElement.classList.add('show');
            }
            
            feedbackModalLabel = document.getElementById('feedbackModalLabel');
            feedbackMessage = document.getElementById('feedbackMessage');
            feedbackIcon = document.getElementById('feedbackIcon');
            feedbackOkButton = document.getElementById('feedbackModalOkBtn');
            feedbackModalHeader = feedbackModalElement.querySelector('.modal-header');
            
            console.log('INFO JS: Modal criado dinamicamente com sucesso');
        }
        
        // Se ainda não funcionar, usar alert
        if (!feedbackModalElement || !feedbackModalInstance || !feedbackModalLabel || !feedbackMessage || !feedbackIcon || !feedbackOkButton || !feedbackModalHeader) {
            console.error('ERRO JS: Não foi possível criar o modal dinamicamente. Usando alert como fallback.', {
                modalElement: !!feedbackModalElement,
                modalInstance: !!feedbackModalInstance,
                modalTitle: !!feedbackModalLabel,
                feedbackMessage: !!feedbackMessage,
                feedbackIcon: !!feedbackIcon,
                okButton: !!feedbackOkButton,
                modalHeader: !!feedbackModalHeader
            });
            // Este é um fallback seguro se o modal não for encontrado, garantindo que o usuário receba a mensagem.
            alert(`Mensagem do Sistema (${title || type}):\n\n${message}`);
            return;
        }
    }

    feedbackModalHeader.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning', 'bg-primary', 'text-white', 'text-dark');
    feedbackIcon.classList.remove('fa-check-circle', 'fa-times-circle', 'fa-info-circle', 'fa-exclamation-triangle', 'text-success', 'text-danger', 'text-info', 'text-warning', 'text-primary');
    feedbackOkButton.classList.remove('btn-success', 'btn-danger', 'btn-info', 'btn-warning', 'btn-primary');

    feedbackModalLabel.textContent = title || (type === 'success' ? 'Sucesso!' : (type === 'error' ? 'Erro!' : (type === 'info' ? 'Informação' : (type === 'warning' ? 'Aviso' : (type === 'primary' ? 'Detalhes' : 'Mensagem')))));
    feedbackMessage.textContent = message;

    let iconClass = '';
    let textColorClass = '';
    let headerBgClass = '';
    let footerBtnClass = '';
    let headerTextColorClass = 'text-white';

    switch (type) {
        case 'success':
            iconClass = 'fas fa-check-circle';
            textColorClass = 'text-success';
            headerBgClass = 'bg-success';
            footerBtnClass = 'btn-success';
            break;
        case 'error':
            iconClass = 'fas fa-times-circle';
            textColorClass = 'text-danger';
            headerBgClass = 'bg-danger';
            footerBtnClass = 'btn-danger';
            break;
        case 'info':
            iconClass = 'fas fa-info-circle';
            textColorClass = 'text-info';
            headerBgClass = 'bg-info';
            footerBtnClass = 'btn-info';
            headerTextColorClass = 'text-dark';
            break;
        case 'warning':
            iconClass = 'fas fa-exclamation-triangle';
            textColorClass = 'text-warning';
            headerBgClass = 'bg-warning';
            footerBtnClass = 'btn-warning';
            headerTextColorClass = 'text-white';
            break;
        case 'primary':
            iconClass = 'fas fa-info-circle';
            textColorClass = 'text-primary';
            headerBgClass = 'bg-primary';
            footerBtnClass = 'btn-primary';
            headerTextColorClass = 'text-white';
            break;
        default:
            iconClass = 'fas fa-info-circle';
            textColorClass = 'text-secondary';
            headerBgClass = 'bg-light';
            footerBtnClass = 'btn-secondary';
            headerTextColorClass = 'text-dark';
            break;
    }

    feedbackIcon.classList.add(...iconClass.split(' '), textColorClass);
    feedbackIcon.style.fontSize = '3rem';
    feedbackModalHeader.classList.add(headerBgClass, headerTextColorClass);
    feedbackOkButton.classList.add(footerBtnClass);

    // Exibir o modal
    if (feedbackModalInstance && typeof feedbackModalInstance.show === 'function') {
        try {
            if (feedbackModalElement && feedbackModalElement.parentNode !== document.body) {
                document.body.appendChild(feedbackModalElement);
            }
            feedbackModalElement.removeAttribute('aria-hidden');
            feedbackModalElement.setAttribute('tabindex', '-1');
            feedbackModalElement.style.display = '';
        } catch(_) {}
        feedbackModalInstance.show();
        try {
            // Ajustar z-index e foco quando o modal estiver visível
            feedbackModalElement.addEventListener('shown.bs.modal', function onShown() {
                try {
                    document.querySelectorAll('.modal-backdrop').forEach(el => { el.style.zIndex = '1060'; });
                    feedbackModalElement.style.zIndex = '1065';
                    const okBtn = document.getElementById('feedbackModalOkBtn');
                    if (okBtn && typeof okBtn.focus === 'function') okBtn.focus();
                } catch(_) {}
                feedbackModalElement.removeEventListener('shown.bs.modal', onShown);
            });
            // Cleanup garantido ao fechar
            feedbackModalElement.addEventListener('hidden.bs.modal', function onHidden() {
                cleanupModalEnvironment();
                feedbackModalElement.removeEventListener('hidden.bs.modal', onHidden);
            });
        } catch(_) {}
    } else {
        // Fallback para modal nativo
        feedbackModalElement.style.display = 'block';
        feedbackModalElement.classList.add('show');
        feedbackModalElement.setAttribute('aria-hidden', 'false');
        
        // Adicionar backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'feedbackModalBackdrop';
        document.body.appendChild(backdrop);
        
        // Fechar modal ao clicar no backdrop
        backdrop.addEventListener('click', () => {
            feedbackModalElement.style.display = 'none';
            feedbackModalElement.classList.remove('show');
            feedbackModalElement.setAttribute('aria-hidden', 'true');
            backdrop.remove();
            cleanupModalEnvironment();
        });
    }

    if (autoCloseDelay > 0) {
        setTimeout(() => {
            if (feedbackModalInstance && typeof feedbackModalInstance.hide === 'function') {
                feedbackModalInstance.hide();
            } else {
                // Fallback para modal nativo
                feedbackModalElement.style.display = 'none';
                feedbackModalElement.classList.remove('show');
                feedbackModalElement.setAttribute('aria-hidden', 'true');
                const backdrop = document.getElementById('feedbackModalBackdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
            cleanupModalEnvironment();
        }, autoCloseDelay);
    }
};

// =============================================
// 2. MODAL DE CONFIRMAÇÃO (Função Global)
// =============================================

/**
 * Exibe um modal de confirmação.
 * @param {string} message Conteúdo da mensagem.
 * @param {string} [title='Confirmação'] Título do modal.
 * @param {string} [type='info'] Tipo para estilização ('success', 'error', 'info', 'warning', 'primary', 'danger').
 * @param {string} [confirmButtonText='Confirmar'] Texto do botão de confirmação.
 * @param {string} [cancelButtonText='Cancelar'] Texto do botão de cancelar.
 * @returns {Promise<boolean>} Uma Promise que resolve com `true` se confirmado, `false` se cancelado.
 */
window.showConfirmModal = function(message, title = 'Confirmação', type = 'info', confirmButtonText = 'Confirmar', cancelButtonText = 'Cancelar') {
    console.log(`INFO JS: showConfirmModal chamado - Título: ${title}, Mensagem: ${message}, Tipo: ${type}`);

    // Retorne uma nova Promise
    return new Promise((resolve) => {
        // Limpeza preventiva global e fechar modais abertos antes de exibir
        cleanupModalEnvironment();
        try {
            if (window.bootstrap) {
                document.querySelectorAll('.modal.show').forEach((m) => {
                    if (m && m.id !== 'confirmModal') {
                        try { const inst = window.bootstrap.Modal.getInstance(m) || new window.bootstrap.Modal(m); inst.hide(); } catch(_){}
                    }
                });
            }
        } catch(_){ }

        // Inicializa elementos do confirm modal sob demanda
        try {
            if (!confirmModalElement) confirmModalElement = document.getElementById('confirmModal');
            if (confirmModalElement && !confirmModalInstance && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                confirmModalInstance = new bootstrap.Modal(confirmModalElement, { backdrop: true, keyboard: true, focus: true });
            }
            if (!confirmModalLabel) confirmModalLabel = document.getElementById('confirmModalLabel');
            if (!confirmModalBody) confirmModalBody = document.getElementById('confirmModalBody');
            if (!confirmModalConfirmBtn) confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');
            if (!confirmModalCancelBtn) confirmModalCancelBtn = document.getElementById('confirmModalCancelBtn');
            if (!confirmModalHeader && confirmModalElement) confirmModalHeader = confirmModalElement.querySelector('.modal-header');
        } catch (e) { console.warn('WARN JS: Falha ao inicializar elementos do confirm modal', e); }

        // Se ainda não existirem, criar modal dinamicamente
        if (!confirmModalElement || !confirmModalLabel || !confirmModalBody || !confirmModalConfirmBtn || !confirmModalCancelBtn) {
            console.warn('AVISO JS: confirmModal não encontrado. Criando dinamicamente...');
            const modalHTML = `
            <div class="modal fade modal-confirm-beautiful" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header modal-header-beautiful">
                            <h5 class="modal-title" id="confirmModalLabel"><span id="confirmModalLabelText">Confirmação</span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="confirmModalBody"><p></p></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="confirmModalCancelBtn" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="confirmModalConfirmBtn">Confirmar</button>
                        </div>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            // Recoletar referências
            confirmModalElement = document.getElementById('confirmModal');
            confirmModalLabel = document.getElementById('confirmModalLabel');
            confirmModalBody = document.getElementById('confirmModalBody');
            confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');
            confirmModalCancelBtn = document.getElementById('confirmModalCancelBtn');
            confirmModalHeader = confirmModalElement ? confirmModalElement.querySelector('.modal-header') : null;
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                confirmModalInstance = new bootstrap.Modal(confirmModalElement, { backdrop: true, keyboard: true, focus: true });
            }
        }

        if (!confirmModalElement || !confirmModalInstance || !confirmModalLabel || !confirmModalBody || !confirmModalConfirmBtn || !confirmModalCancelBtn || !confirmModalHeader) {
            console.error('ERRO JS: Ainda sem elementos do confirmModal, usando confirm nativo.');
            const userConfirmed = confirm(`${title}\n${message}`);
            resolve(userConfirmed);
            return;
        }

        // Log de entrada da função showConfirmModal
        try {
            console.info('INFO JS: showConfirmModal chamado', { title, message, type, confirmButtonText, cancelButtonText });
        } catch (e) { console.warn('WARN JS: Falha ao logar entrada do showConfirmModal', e); }

        // Limpa classes de cores anteriores
        const isBeautifulHeader = confirmModalHeader && confirmModalHeader.classList.contains('modal-header-beautiful');
        if (!isBeautifulHeader) {
            confirmModalHeader.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning', 'bg-primary', 'text-white', 'text-dark');
        }
        confirmModalConfirmBtn.classList.remove('btn-success', 'btn-danger', 'btn-info', 'btn-warning', 'btn-primary');

        let headerBgClass = '';
        let confirmBtnClass = '';
        let headerTextColorClass = 'text-white';
        let headerIconClass = 'fa-solid fa-circle-question';

        switch (type) {
            case 'success':
                headerBgClass = 'bg-success'; confirmBtnClass = 'btn-success'; break;
            case 'error':
            case 'danger': // Adicionado para estilização de remoção
                headerBgClass = 'bg-danger'; confirmBtnClass = 'btn-danger'; headerIconClass = 'fa-solid fa-trash'; break;
            case 'info':
                headerBgClass = 'bg-info'; confirmBtnClass = 'btn-info'; headerTextColorClass = 'text-dark'; headerIconClass = 'fa-solid fa-circle-info'; break;
            case 'warning':
                headerBgClass = 'bg-warning'; confirmBtnClass = 'btn-warning'; headerIconClass = 'fa-solid fa-triangle-exclamation'; break;
            case 'primary':
                headerBgClass = 'bg-primary'; confirmBtnClass = 'btn-primary'; headerIconClass = 'fa-solid fa-circle-info'; break;
            default:
                headerBgClass = 'bg-secondary'; confirmBtnClass = 'btn-secondary'; headerTextColorClass = 'text-white'; headerIconClass = 'fa-solid fa-circle-question'; break;
        }

        // Log de estilos escolhidos
        try {
            console.debug('DEBUG JS: Estilos escolhidos para confirm modal', { headerBgClass, confirmBtnClass, headerTextColorClass, headerIconClass, isBeautifulHeader });
        } catch (e) { console.warn('WARN JS: Falha ao logar estilos', e); }

        // Se for header bonito (azul), não sobrescrever o fundo/cor do cabeçalho
        if (!isBeautifulHeader) {
            confirmModalHeader.classList.add(headerBgClass, headerTextColorClass);
        }
        // Título: se existir um span dedicado, preenche apenas o texto para preservar ícone estático do HTML
        const labelTextSpan = document.getElementById('confirmModalLabelText');
        if (labelTextSpan) {
            // Atualiza apenas o texto deixando o ícone definido no HTML
            labelTextSpan.textContent = title;
        } else {
            // Fallback: injeta ícone + título direto no label
            confirmModalLabel.innerHTML = `<i class="${headerIconClass} me-2"></i>${title}`;
        }
        try {
            console.debug('DEBUG JS: Header após setar título', { labelInnerHTML: confirmModalLabel ? confirmModalLabel.innerHTML : null });
        } catch (e) { console.warn('WARN JS: Falha ao logar header após título', e); }
        confirmModalBody.innerHTML = `<p>${message}</p>`;
        try {
            console.debug('DEBUG JS: Body após setar mensagem', { bodyHTML: confirmModalBody ? confirmModalBody.innerHTML : null });
        } catch (e) { console.warn('WARN JS: Falha ao logar body após mensagem', e); }

        confirmModalConfirmBtn.textContent = confirmButtonText;
        confirmModalConfirmBtn.classList.add(confirmBtnClass); // Adiciona a classe de cor ao botão de confirmação
        confirmModalCancelBtn.textContent = cancelButtonText;
        try {
            console.debug('DEBUG JS: Botões após configurar', { confirmText: confirmModalConfirmBtn.textContent, cancelText: confirmModalCancelBtn.textContent, confirmClassList: [...confirmModalConfirmBtn.classList] });
        } catch (e) { console.warn('WARN JS: Falha ao logar botões', e); }

        // Limpa listeners antigos para evitar chamadas duplicadas
        confirmModalConfirmBtn.removeEventListener('click', confirmModalConfirmBtn._currentHandler);
        confirmModalCancelBtn.removeEventListener('click', confirmModalCancelBtn._currentHandler);

        // Remove listener do botão de fechar (X)
        const closeBtn = confirmModalElement.querySelector('.btn-close');
        if (closeBtn && closeBtn._currentHandler) {
            closeBtn.removeEventListener('click', closeBtn._currentHandler);
        }

        // Define e anexa novos listeners que resolvem a Promise
        const newConfirmHandler = () => {
            console.log("DEBUG JS: Botão 'Confirmar' clicado! (Dentro de general-utils.js)"); // NOVO LOG AQUI
            confirmModalInstance.hide();
            resolve(true); // Resolve a Promise com true
        };
        const newCancelHandler = () => {
            console.log("DEBUG JS: Botão 'Cancelar' clicado! (Dentro de general-utils.js)"); // NOVO LOG AQUI
            confirmModalInstance.hide();
            resolve(false); // Resolve a Promise com false
        };
        const newCloseHandler = () => {
            console.log("DEBUG JS: Botão 'Fechar (X)' do modal clicado! (Dentro de general-utils.js)"); // NOVO LOG AQUI
            confirmModalInstance.hide();
            resolve(false); // Resolve a Promise com false se o modal for fechado pelo 'X'
        };

        confirmModalConfirmBtn.addEventListener('click', newConfirmHandler);
        confirmModalCancelBtn.addEventListener('click', newCancelHandler);
        if (closeBtn) {
            closeBtn.addEventListener('click', newCloseHandler);
        }

        // Armazena os handlers para remoção futura
        confirmModalConfirmBtn._currentHandler = newConfirmHandler;
        confirmModalCancelBtn._currentHandler = newCancelHandler;
        if (closeBtn) {
            closeBtn._currentHandler = newCloseHandler;
        }

        // Adiciona um listener para o evento `hidden.bs.modal` para garantir que a Promise seja resolvida
        // mesmo se o modal for fechado por outros meios (ex: clique fora, tecla ESC)
        const onHiddenResolver = function() {
            // Verifica se a Promise já foi resolvida por um dos botões do modal
            // Usamos a presença do handler para saber se já foi tratado por um clique de botão
            if (confirmModalConfirmBtn._currentHandler === newConfirmHandler || confirmModalCancelBtn._currentHandler === newCancelHandler) {
                // Se já foi tratado por um clique de botão, não faz nada aqui
            } else {
                resolve(false); // Resolve como false se foi fechado sem confirmar/cancelar pelos botões
            }
            confirmModalElement.removeEventListener('hidden.bs.modal', onHiddenResolver); // Remove o listener
        };
        confirmModalElement.addEventListener('hidden.bs.modal', onHiddenResolver);


        // Limpeza preventiva de resíduos de modais antes de exibir o confirm
        try { cleanupModalEnvironment(); } catch(e) { console.warn('WARN JS: Falha na limpeza preventiva de backdrops/body', e); }

        try {
            if (confirmModalElement && confirmModalElement.parentNode !== document.body) {
                document.body.appendChild(confirmModalElement);
            }
            confirmModalElement.removeAttribute('aria-hidden');
            confirmModalElement.setAttribute('tabindex', '-1');
            confirmModalElement.style.display = '';
        } catch(_) {}
        confirmModalInstance.show();
        // Failsafe: garantir que o modal esteja acima de qualquer backdrop e com z-index correto
        setTimeout(() => {
            try {
                // Trazer o modal para o final do body (topo da pilha do DOM)
                if (confirmModalElement && confirmModalElement.parentNode === document.body) {
                    document.body.appendChild(confirmModalElement);
                }
                // Normalizar z-index de backdrops e do modal
                document.querySelectorAll('.modal-backdrop').forEach(el => { el.style.zIndex = '1060'; });
                if (confirmModalElement && confirmModalElement.style) {
                    confirmModalElement.style.zIndex = '1065';
                }
                // Foco no botão principal
                const primaryBtn = document.getElementById('confirmModalConfirmBtn');
                if (primaryBtn && typeof primaryBtn.focus === 'function') { primaryBtn.focus(); }
            } catch(e) { console.warn('WARN JS: Failsafe z-index/focus confirm modal', e); }
        }, 60);

        // Eventos para garantir foco e limpeza
        try {
            confirmModalElement.addEventListener('shown.bs.modal', function onShown() {
                try {
                    document.querySelectorAll('.modal-backdrop').forEach(el => { el.style.zIndex = '1060'; });
                    confirmModalElement.style.zIndex = '1065';
                    const primaryBtn = document.getElementById('confirmModalConfirmBtn');
                    if (primaryBtn && typeof primaryBtn.focus === 'function') { primaryBtn.focus(); }
                } catch(_) {}
                confirmModalElement.removeEventListener('shown.bs.modal', onShown);
            });
            confirmModalElement.addEventListener('hidden.bs.modal', function onHidden() {
                cleanupModalEnvironment();
                confirmModalElement.removeEventListener('hidden.bs.modal', onHidden);
            });
        } catch(_) {}
    });
};

// =============================================
// 3. MODAL DE CARREGAMENTO (Funções Globais)
// =============================================

/**
 * Exibe o modal de carregamento.
 */
window.showLoadingModal = function() {
    // Verifica se os elementos do modal estão inicializados
    if (!loadingModalElement || !loadingModalInstance) {
        console.warn('AVISO JS: Modal de carregamento não disponível. Não foi possível exibir.');
        return;
    }

    // Se o modal já estiver visível ou em transição para aparecer, não faz nada.
    if (loadingModalElement.classList.contains('show') || loadingModalElement.classList.contains('showing')) {
        console.log('INFO JS: Modal de carregamento já visível ou em transição. Não exibindo novamente.');
        return;
    }

    console.log('INFO JS: showLoadingModal chamado. Exibindo modal de carregamento.');
    loadingModalInstance.show();
    loadingModalElement.setAttribute('aria-hidden', 'false');
    // Armazena o timestamp de quando o modal foi exibido
    loadingModalElement.dataset.showTime = Date.now();
};

/**
 * Oculta o modal de carregamento e retorna uma Promise que resolve quando ele estiver completamente oculto.
 * @returns {Promise<void>} Uma Promise que resolve quando o modal está oculto.
 */
window.hideLoadingModal = function() {
    return new Promise(resolve => {
        if (!loadingModalElement || !loadingModalInstance) {
            console.warn('AVISO JS: Modal de carregamento não disponível. Não foi possível ocultar.');
            resolve(); // Resolve imediatamente se o modal não estiver disponível
            return;
        }
        console.log('INFO JS: hideLoadingModal chamado. Ocultando modal de carregamento.');

        const minDisplayTime = 300; // Tempo mínimo em milissegundos que o modal deve ficar visível (300ms)
        const showTime = parseInt(loadingModalElement.dataset.showTime || '0', 10);
        const timeElapsed = Date.now() - showTime;
        const remainingTime = Math.max(0, minDisplayTime - timeElapsed);

        let resolved = false;
        const finalize = function() {
            if (resolved) return;
            resolved = true;
            console.log('DEBUG JS: hidden.bs.modal event fired. Executando failsafe cleanup.');
            try {
                loadingModalElement.removeEventListener('hidden.bs.modal', onHiddenHandler);
            } catch(e) {}
            // Forçar ocultar o elemento do modal de loading (failsafe)
            try {
                if (loadingModalInstance && typeof loadingModalInstance.hide === 'function') {
                    try { loadingModalInstance.hide(); } catch(e){}
                }
                loadingModalElement.classList.remove('show', 'showing');
                loadingModalElement.style.display = 'none';
                loadingModalElement.setAttribute('aria-hidden', 'true');
            } catch(e) { console.warn('WARN JS: Falha ao forçar ocultar loadingModalElement', e); }
            // Failsafe: remover manualmente TODOS os backdrops e classes do body
            document.querySelectorAll('.modal-backdrop').forEach(el => {
                el.parentNode && el.parentNode.removeChild(el);
            });
            console.log('DEBUG JS: Failsafe: todos os modal-backdrop removidos.');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            console.log('DEBUG JS: loadingModalElement display style after failsafe:', loadingModalElement.style.display);
            console.log('DEBUG JS: loadingModalElement classList after failsafe:', loadingModalElement.classList.value);
            resolve(); // Resolve a Promise quando o modal estiver completamente oculto
        };

        const onHiddenHandler = function() { finalize(); };

        // Garante que o listener não seja adicionado várias vezes se hideLoadingModal for chamado rapidamente
        try { loadingModalElement.removeEventListener('hidden.bs.modal', onHiddenHandler); } catch(e) {}
        loadingModalElement.addEventListener('hidden.bs.modal', onHiddenHandler);

        // Fallback: se por algum motivo o evento não disparar, resolver após 900ms
        setTimeout(() => finalize(), remainingTime + 900);

        if (remainingTime > 0) {
            console.log(`DEBUG JS: Aguardando ${remainingTime}ms para garantir tempo mínimo de exibição do loading modal.`);
            setTimeout(() => {
                loadingModalInstance.hide();
                console.log('DEBUG JS: loadingModalInstance.hide() chamado (após delay).');
            }, remainingTime);
        } else {
            loadingModalInstance.hide();
            console.log('DEBUG JS: loadingModalInstance.hide() chamado (sem delay).');
        }
    });
};


// =============================================
// 4. TRATAMENTO DE ERROS DE CAMPO DE FORMULÁRIO (Funções Globais)
// =============================================
/**
 * Exibe uma mensagem de erro abaixo de um campo de entrada do formulário.
 * @param {HTMLElement} inputElement - O elemento de entrada.
 * @param {string} message - A mensagem de erro.
 */
window.showError = function(inputElement, message) {
    console.log(`INFO JS: Exibindo erro para o input ${inputElement.name || inputElement.id || inputElement.tagName}: ${message}`);

    // Remove qualquer erro anterior para evitar duplicatas
    window.removeError(inputElement);

    let parentContainer = inputElement.closest('.mb-4, .mb-3, .mb-2, .input-group, .form-group');
    if (!parentContainer) {
        parentContainer = inputElement.parentElement;
    }

    if (!parentContainer) {
        console.error('ERRO JS: Nenhum contêiner adequado encontrado para o input.', inputElement);
        return;
    }

    let errorDiv = document.createElement('div');
    errorDiv.classList.add('text-danger', 'mt-1', 'small', 'invalid-feedback-custom');
    errorDiv.textContent = message;

    // Adiciona a classe 'is-invalid' e o feedback no local correto
    inputElement.classList.add('is-invalid');
    inputElement.classList.remove('is-valid');

    // Insere o feedback após o elemento de entrada
    inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);

    // Adiciona classe de erro específica para o contêiner de upload de foto
    if (inputElement.closest('.photo-upload-box')) {
        inputElement.closest('.photo-upload-box').classList.add('is-invalid-media');
    }
};

/**
 * Remove a mensagem de erro de um campo de entrada do formulário.
 * @param {HTMLElement} inputElement - O elemento de entrada.
 * @param {boolean} [isValid=false] - Se o campo deve ser marcado como válido após a remoção do erro.
 */
window.removeError = function(inputElement, isValid = false) {
    console.log(`INFO JS: Removendo erro para o input ${inputElement.name || inputElement.id || inputElement.tagName}.`);

    let parentContainer = inputElement.closest('.mb-4, .mb-3, .mb-2, .input-group, .form-group');
    if (!parentContainer) {
        parentContainer = inputElement.parentElement;
    }

    if (!parentContainer) return;

    // Remove o feedback personalizado
    let errorDiv = inputElement.parentNode.querySelector('.invalid-feedback-custom');
    if (errorDiv) {
        errorDiv.remove();
    }

    // Remove as classes de validação do Bootstrap e do contêiner de upload
    inputElement.classList.remove('is-invalid', 'is-valid');
    if (inputElement.closest('.photo-upload-box')) {
        inputElement.closest('.photo-upload-box').classList.remove('is-invalid-media');
    }

    // Adiciona a classe 'is-valid' se a flag for verdadeira
    if (isValid) {
        inputElement.classList.add('is-valid');
    }
};

// =============================================
// 5. VALIDAÇÃO DE E-MAIL (Função Global)
// =============================================
/**
 * Valida o formato de um e-mail.
 * @param {string} email - A string de e-mail a ser validada.
 * @returns {boolean} - Verdadeiro se for válido, falso caso contrário.
 */
window.validateEmail = function(email) {
    const re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return re.test(String(email).toLowerCase());
};

// =============================================
// 6. ESTADO DE CARREGAMENTO DO BOTÃO (Funções Globais)
// =============================================
/**
 * Ativa um spinner de carregamento e desabilita um botão.
 * @param {HTMLElement} buttonElement - O elemento do botão.
 * @param {string} loadingText - O texto a ser exibido durante o carregamento.
 * @returns {string} - O conteúdo HTML original do botão.
 */
window.activateButtonLoading = function(buttonElement, loadingText) {
    const originalHTML = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.style.pointerEvents = 'none';
    buttonElement.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${loadingText}`;
    console.log(`INFO JS: Botão ${buttonElement.id || buttonElement.name || 'desconhecido'} ativado para carregamento.`);
    return originalHTML;
};

/**
 * Desativa o estado de carregamento e restaura o conteúdo original de um botão.
 * @param {HTMLElement} buttonElement - O elemento do botão.
 * @param {string} originalHTML - O conteúdo HTML original a ser restaurado.
 */
window.deactivateButtonLoading = function(buttonElement, originalHTML) {
    buttonElement.innerHTML = originalHTML;
    buttonElement.disabled = false;
    buttonElement.style.pointerEvents = 'auto';
    console.log(`INFO JS: Botão ${buttonElement.id || buttonElement.name || 'desconhecido'} desativado e restaurado.`);
};

// =============================================
// 7. ALERTAS COM DISPENSA AUTOMÁTICA (Função Global)
// =============================================
/**
 * Configura a dispensa automática para alertas do Bootstrap.
 * É executada na carga inicial e também pode ser chamada após carregamento SPA.
 */
window.setupAutoDismissAlerts = function() {
    document.querySelectorAll('.alert').forEach(alert => {
        if (alert.dataset.autodismissed) return;
        alert.dataset.autodismissed = 'true';

        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 4000);
    });
};

window.setupAutoDismissAlerts();

// =============================================
// 8. ALTERNAR VISIBILIDADE DA SENHA (Listener Global para elementos dinâmicos)
// =============================================
document.addEventListener('click', function(e) {
    const button = e.target.closest('.toggle-password');
    if (button) {
        console.log('INFO JS: Botão de alternar senha clicado (via delegação).');
        const input = button.closest('.input-group')?.querySelector('input[type="password"], input[type="text"]');
        const icon = button.querySelector('i');

        if (input && icon) {
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        } else {
            console.warn('AVISO JS: Não foi possível encontrar o input ou ícone associado para o botão toggle-password (via delegação).', button);
        }
    }
});

// =============================================
// 9. FUNÇÃO PARA AÇÕES DE ADMIN (NOVA FUNÇÃO)
// =============================================
/**
 * Executa uma ação administrativa (aprovar, reprovar, deletar) em um anúncio.
 * @param {string} actionType - O tipo de ação ('approve', 'reject', 'delete').
 * @param {number} anuncioId - O ID do anúncio.
 * @param {number} anuncianteUserId - O ID do usuário anunciante.
 */
window.performAdminAction = function(actionType, anuncioId, anuncianteUserId) {
    console.log(`DEBUG JS: performAdminAction chamado. Tipo: "${actionType}", Anuncio ID: ${anuncioId}, Anunciante User ID: ${anuncianteUserId}`);

    let endpoint = '';
    let successMessage = '';
    let errorMessage = 'Erro desconhecido ao processar a ação.';

    // Defina o endpoint e as mensagens com base no tipo de ação
    switch (actionType) {
        case 'approve':
            endpoint = `${URLADM}anuncio/approveAnuncio`;
            successMessage = 'Anúncio aprovado com sucesso!';
            errorMessage = 'Falha ao aprovar o anúncio.';
            break;
        case 'reject':
            endpoint = `${URLADM}anuncio/rejectAnuncio`;
            successMessage = 'Anúncio reprovado com sucesso!';
            errorMessage = 'Falha ao reprovar o anúncio.';
            break;
        case 'delete':
            endpoint = `${URLADM}anuncio/deleteAnuncio`; // Assumindo uma rota para exclusão
            successMessage = 'Anúncio excluído com sucesso!';
            errorMessage = 'Falha ao excluir o anúncio.';
            break;
        default:
            console.error(`ERRO JS: Tipo de ação "${actionType}" desconhecido para performAdminAction.`);
            showAlert('error', 'Tipo de ação inválido.');
            return;
    }

    console.log(`DEBUG JS: Endpoint AJAX para ${actionType}: ${endpoint}`);

    // Exibe o modal de carregamento
    showLoadingModal();

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
        },
        body: JSON.stringify({ anuncioId: anuncioId, anuncianteUserId: anuncianteUserId })
    })
    .then(response => {
        console.log("DEBUG JS: Resposta bruta da requisição AJAX recebida.");
        // Verifica se a resposta HTTP é OK (status 200-299)
        if (!response.ok) {
            console.error(`ERRO JS: Resposta de rede não ok. Status: ${response.status} (${response.statusText})`);
            // Tenta ler a resposta como texto para incluir na mensagem de erro
            return response.text().then(text => {
                throw new Error(`Erro HTTP ${response.status}: ${text}`);
            });
        }
        return response.json(); // Tenta parsear a resposta como JSON
    })
    .then(data => {
        console.log("DEBUG JS: Dados JSON recebidos:", data);
        // Oculta o modal de carregamento
        hideLoadingModal();
        if (data.success) {
            console.log(`SUCESSO JS: ${successMessage}`);
            showAlert('success', data.message || successMessage);
            // Recarregar a lista de anúncios ou atualizar a UI, se necessário
            // Por exemplo, recarregar a página do dashboard:
            loadContent(`${URLADM}dashboard`); // Adapte para sua função de carregamento de conteúdo
        } else {
            console.error(`ERRO JS: ${errorMessage}. Detalhes: ${data.message || 'Nenhuma mensagem específica.'}`);
            showAlert('error', data.message || errorMessage);
        }
    })
    .catch(error => {
        // Oculta o modal de carregamento em caso de erro na rede ou no JSON parsing
        hideLoadingModal();
        console.error(`ERRO JS: Falha na requisição AJAX para ${actionType}:`, error);
        showAlert('error', errorMessage + ' Detalhes: ' + (error.message || 'Verifique o console para mais informações.'));
    });
};

// =============================================
// 10. LÓGICA DO MODAL DE EXCLUSÃO DE CONTA (REMOVIDA - CONFLITO COM PERFIL.JS)
// =============================================
// REMOVIDO: Código duplicado que causava conflito com perfil.js
// A lógica de exclusão de conta agora está centralizada em perfil.js
// =============================================
// INICIALIZAÇÃO DOS MODAIS NO DOMContentLoaded
// ... (o restante do seu código permanece igual)
// =============================================
document.addEventListener('DOMContentLoaded', async () => {
    console.log('INFO JS: DOMContentLoaded disparado em general-utils.js. Inicializando elementos dos modais.');

    feedbackModalElement = document.getElementById('feedbackModal');
    if (feedbackModalElement) {
        if (!feedbackModalInstance) {
            feedbackModalInstance = new bootstrap.Modal(feedbackModalElement);
        }
        feedbackModalLabel = document.getElementById('feedbackModalLabel');
        feedbackMessage = document.getElementById('feedbackMessage');
        feedbackIcon = document.getElementById('feedbackIcon');
        feedbackOkButton = document.getElementById('feedbackModalOkBtn');
        feedbackModalHeader = feedbackModalElement.querySelector('.modal-header');
        console.log('DEBUG JS: feedbackModal elementos inicializados.', {
            modalElement: !!feedbackModalElement,
            modalInstance: !!feedbackModalInstance,
            modalTitle: !!feedbackModalLabel,
            feedbackIcon: !!feedbackIcon,
            feedbackMessage: !!feedbackMessage,
            okButton: !!feedbackOkButton,
            modalHeader: !!feedbackModalHeader
        });
    } else {
        console.warn('AVISO JS: Elemento #feedbackModal não encontrado no DOM. A função showFeedbackModal usará alert como fallback.');
        window.showFeedbackModal = function(type, message, title = '', autoCloseDelay = 0) {
            alert(`Mensagem do Sistema (${title || type}):\n\n${message}`);
        };
    }

    confirmModalElement = document.getElementById('confirmModal');
    if (confirmModalElement) {
        if (!confirmModalInstance) {
            confirmModalInstance = new bootstrap.Modal(confirmModalElement);
        }
        confirmModalLabel = document.getElementById('confirmModalLabel');
        confirmModalBody = document.getElementById('confirmModalBody');
        confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn'); // IMPORTANTE: VERIFIQUE SE ESTE É ENCONTRADO
        confirmModalCancelBtn = document.getElementById('confirmModalCancelBtn');
        confirmModalHeader = confirmModalElement.querySelector('.modal-header');
        // Upgrade dinâmico do header: garantir ícone + span de texto
        try {
            console.debug('DEBUG JS: Antes do upgrade do header', {
                labelHTML: confirmModalLabel ? confirmModalLabel.innerHTML : null,
                hasSpan: !!(confirmModalLabel && confirmModalLabel.querySelector('#confirmModalLabelText'))
            });
        } catch (e) { console.warn('WARN JS: Falha ao logar header antes do upgrade', e); }
        if (confirmModalLabel && !confirmModalLabel.querySelector('#confirmModalLabelText')) {
            const currentText = confirmModalLabel.textContent.trim();
            confirmModalLabel.innerHTML = `<i class="fa-solid fa-trash me-2"></i><span id="confirmModalLabelText"></span>`;
            const span = document.getElementById('confirmModalLabelText');
            if (span) span.textContent = currentText || '';
        }
        try {
            console.debug('DEBUG JS: Depois do upgrade do header', {
                labelHTML: confirmModalLabel ? confirmModalLabel.innerHTML : null,
                hasSpan: !!(confirmModalLabel && confirmModalLabel.querySelector('#confirmModalLabelText'))
            });
        } catch (e) { console.warn('WARN JS: Falha ao logar header depois do upgrade', e); }
        console.log('DEBUG JS: confirmModal elementos inicializados.', {
            confirmModalElement: !!confirmModalElement,
            confirmModalInstance: !!confirmModalInstance,
            confirmModalLabel: !!confirmModalLabel,
            confirmModalBody: !!confirmModalBody,
            confirmModalConfirmBtn: !!confirmModalConfirmBtn, // NOVO LOG AQUI
            confirmModalCancelBtn: !!confirmModalCancelBtn,
            confirmModalHeader: !!confirmModalHeader
        });
        // Log adicional para garantir que o elemento foi encontrado e o evento pode ser anexado
        if (!confirmModalConfirmBtn) {
            console.error('ERRO CRÍTICO JS: O botão #confirmModalConfirmBtn NÃO FOI ENCONTRADO no DOM!');
        }
    } else {
        console.warn('AVISO JS: Elemento #confirmModal não encontrado. Criando modal de confirmação dinamicamente...');
        const confirmHTML = `
            <div class="modal fade modal-theme-login" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body" id="confirmModalBody"></div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="confirmModalCancelBtn" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmModalConfirmBtn">Confirmar</button>
                  </div>
                </div>
              </div>
            </div>`;
        document.body.insertAdjacentHTML('beforeend', confirmHTML);
        // Re-inicializar referências
        confirmModalElement = document.getElementById('confirmModal');
        if (confirmModalElement) {
            confirmModalInstance = new bootstrap.Modal(confirmModalElement);
            confirmModalLabel = document.getElementById('confirmModalLabel');
            confirmModalBody = document.getElementById('confirmModalBody');
            confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');
            confirmModalCancelBtn = document.getElementById('confirmModalCancelBtn');
            confirmModalHeader = confirmModalElement.querySelector('.modal-header');
            console.log('INFO JS: confirmModal criado dinamicamente e inicializado.');
        } else {
            console.error('ERRO JS: Falha ao criar dinamicamente o confirmModal. Fallback para confirm nativo.');
            window.showConfirmModal = function(message, title = '', type = 'info') {
                return new Promise(resolve => {
                    const userConfirmed = confirm(`${title}\n${message}`);
                    resolve(userConfirmed);
                });
            };
        }
    }

    loadingModalElement = document.getElementById('loadingModal');
    if (loadingModalElement) {
        if (!loadingModalInstance) {
            loadingModalInstance = new bootstrap.Modal(loadingModalElement, {
                backdrop: 'static',
                keyboard: false
            });
        }
        console.log('DEBUG JS: loadingModal elementos inicializados.', {
            loadingModalElement: !!loadingModalElement,
            loadingModalInstance: !!loadingModalInstance
        });
    } else {
        console.warn('AVISO JS: Elemento #loadingModal não encontrado no DOM. As funções showLoadingModal/hideLoadingModal não farão nada.');
        window.showLoadingModal = function() { console.warn('AVISO JS: Modal de carregamento não disponível.'); };
        window.hideLoadingModal = function() { console.warn('AVISO JS: Modal de carregamento não disponível.'); };
    }
});

// =====================================================
// 10. HELPER UNIFICADO PARA MODAIS (Tamanhos/Scroll)
// =====================================================
window.Modals = window.Modals || {
    /**
     * Abre um modal com opções padronizadas de tamanho e scroll.
     * @param {string} selector CSS selector do modal (ex: '#deleteAccountModal')
     * @param {object} [opts]
     * @param {'sm'|'lg'|'xl'|'xxl'|'fluid'|number} [opts.size] Tamanho do dialog (número em px define --bs-modal-width)
     * @param {'body'|'none'} [opts.scroll] 'body' para usar modal-dialog-scrollable
     * @param {string} [opts.fullscreenBreakpoint] ex: 'sm-down','md-down','lg-down','xl-down'
     * @param {boolean|string} [opts.backdrop] true|false|'static'
     * @param {boolean} [opts.keyboard]
     */
    open(selector, opts = {}) {
        const el = document.querySelector(selector);
        if (!el) {
            console.warn('Modals.open: Modal não encontrado:', selector);
            return;
        }
        const dialog = el.querySelector('.modal-dialog');
        if (!dialog) {
            console.warn('Modals.open: .modal-dialog não encontrado em', selector);
            return;
        }

        // Limpar classes de tamanho conhecidas
        dialog.classList.remove('modal-sm','modal-lg','modal-xl','modal-xxl','modal-fluid','modal-dialog-scrollable',
            'modal-fullscreen-sm-down','modal-fullscreen-md-down','modal-fullscreen-lg-down','modal-fullscreen-xl-down');

        // Tamanho
        if (typeof opts.size === 'number') {
            dialog.style.setProperty('--bs-modal-width', opts.size + 'px');
        } else if (typeof opts.size === 'string') {
            const sizeClass = opts.size === 'fluid' ? 'modal-fluid' : `modal-${opts.size}`;
            dialog.classList.add(sizeClass);
        }

        // Scroll
        if (opts.scroll === 'body') dialog.classList.add('modal-dialog-scrollable');

        // Fullscreen conforme breakpoint
        if (opts.fullscreenBreakpoint) dialog.classList.add('modal-fullscreen-' + opts.fullscreenBreakpoint);

        // Instância Bootstrap
        const instance = bootstrap.Modal.getOrCreateInstance(el, {
            backdrop: (typeof opts.backdrop === 'undefined') ? true : opts.backdrop,
            keyboard: (typeof opts.keyboard === 'undefined') ? true : !!opts.keyboard
        });
        instance.show();
        return instance;
    }
};
