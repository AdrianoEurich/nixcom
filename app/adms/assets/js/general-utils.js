// general-utils.js
// Versão 41 - Ajuste para showConfirmModal retornar uma Promise
console.info('INFO JS: general-utils.js (Versão 41 - showConfirmModal com Promise) carregado. Configurando funcionalidades gerais.');

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
    console.log(`INFO JS: showFeedbackModal chamado - Tipo: ${type}, Mensagem: ${message}, Título: ${title}`);
    
    if (!feedbackModalElement || !feedbackModalInstance || !feedbackModalLabel || !feedbackMessage || !feedbackIcon || !feedbackOkButton || !feedbackModalHeader) {
        console.error('ERRO JS: Elementos do feedbackModal não inicializados. Verifique o HTML no main.php/footer.php e a inicialização em general-utils.js. Usando alert como fallback.', {
            modalElement: !!feedbackModalElement,
            modalInstance: !!feedbackModalInstance,
            modalTitle: !!feedbackModalLabel,
            feedbackIcon: !!feedbackIcon,
            feedbackMessage: !!feedbackMessage,
            okButton: !!feedbackOkButton, 
            modalHeader: !!feedbackModalHeader
        });
        // Este é um fallback seguro se o modal não for encontrado, garantindo que o usuário receba a mensagem.
        alert(`Mensagem do Sistema (${title || type}):\n\n${message}`);
        return;
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

    feedbackModalInstance.show();

    if (autoCloseDelay > 0) {
        setTimeout(() => {
            feedbackModalInstance.hide();
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
        if (!confirmModalElement || !confirmModalInstance || !confirmModalLabel || !confirmModalBody || !confirmModalConfirmBtn || !confirmModalCancelBtn || !confirmModalHeader) {
            console.error('ERRO JS: Elementos do confirmModal não encontrados ou não inicializados. Fallback para confirm.', {
                confirmModalElement: !!confirmModalElement,
                confirmModalInstance: !!confirmModalInstance,
                confirmModalLabel: !!confirmModalLabel,
                confirmModalBody: !!confirmModalBody,
                confirmModalConfirmBtn: !!confirmModalConfirmBtn,
                confirmModalCancelBtn: !!confirmModalCancelBtn,
                confirmModalHeader: !!confirmModalHeader
            });
            // Este é um fallback seguro para quando o modal não está disponível.
            const userConfirmed = confirm(`${title}\n${message}`);
            resolve(userConfirmed); // Resolve a Promise com o resultado do confirm nativo
            return;
        }

        // Limpa classes de cores anteriores
        confirmModalHeader.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning', 'bg-primary', 'text-white', 'text-dark');
        confirmModalConfirmBtn.classList.remove('btn-success', 'btn-danger', 'btn-info', 'btn-warning', 'btn-primary');

        let headerBgClass = '';
        let confirmBtnClass = '';
        let headerTextColorClass = 'text-white';

        switch (type) {
            case 'success':
                headerBgClass = 'bg-success'; confirmBtnClass = 'btn-success'; break;
            case 'error': 
            case 'danger': // Adicionado para estilização de remoção
                headerBgClass = 'bg-danger'; confirmBtnClass = 'btn-danger'; break;
            case 'info':
                headerBgClass = 'bg-info'; confirmBtnClass = 'btn-info'; headerTextColorClass = 'text-dark'; break;
            case 'warning':
                headerBgClass = 'bg-warning'; confirmBtnClass = 'btn-warning'; break;
            case 'primary':
                headerBgClass = 'bg-primary'; confirmBtnClass = 'btn-primary'; break;
            default:
                headerBgClass = 'bg-secondary'; confirmBtnClass = 'btn-secondary'; headerTextColorClass = 'text-white'; break;
        }

        confirmModalHeader.classList.add(headerBgClass, headerTextColorClass);
        confirmModalLabel.textContent = title;
        confirmModalBody.innerHTML = `<p>${message}</p>`;

        confirmModalConfirmBtn.textContent = confirmButtonText;
        confirmModalConfirmBtn.classList.add(confirmBtnClass); // Adiciona a classe de cor ao botão de confirmação
        confirmModalCancelBtn.textContent = cancelButtonText;

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
            console.log("DEBUG: Botão 'Confirmar' clicado! (Dentro de general-utils.js)"); // NOVO LOG AQUI
            confirmModalInstance.hide();
            resolve(true); // Resolve a Promise com true
        };
        const newCancelHandler = () => {
            console.log("DEBUG: Botão 'Cancelar' clicado! (Dentro de general-utils.js)"); // NOVO LOG AQUI
            confirmModalInstance.hide();
            resolve(false); // Resolve a Promise com false
        };
        const newCloseHandler = () => {
            console.log("DEBUG: Botão 'Fechar (X)' do modal clicado! (Dentro de general-utils.js)"); // NOVO LOG AQUI
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


        confirmModalInstance.show();
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
        
        const minDisplayTime = 2000; // Tempo mínimo em milissegundos que o modal deve ficar visível (2 segundos)
        const showTime = parseInt(loadingModalElement.dataset.showTime || '0', 10);
        const timeElapsed = Date.now() - showTime;
        const remainingTime = minDisplayTime - timeElapsed;

        const onHiddenHandler = function() {
            console.log('DEBUG JS: hidden.bs.modal event fired. Executando failsafe cleanup.');
            
            // Remove o listener para evitar execuções múltiplas
            loadingModalElement.removeEventListener('hidden.bs.modal', onHiddenHandler);

            // Failsafe: Remove manualmente o backdrop e a classe do body, caso o Bootstrap não o faça
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.parentNode.removeChild(backdrop);
                console.log('DEBUG JS: Failsafe: modal-backdrop removido.');
            }
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            console.log('DEBUG JS: loadingModalElement display style after failsafe:', loadingModalElement.style.display);
            console.log('DEBUG JS: loadingModalElement classList after failsafe:', loadingModalElement.classList.value);
            resolve(); // Resolve a Promise quando o modal estiver completamente oculto
        };

        // Garante que o listener não seja adicionado várias vezes se hideLoadingModal for chamado rapidamente
        loadingModalElement.removeEventListener('hidden.bs.modal', onHiddenHandler); // Remove qualquer listener anterior
        loadingModalElement.addEventListener('hidden.bs.modal', onHiddenHandler); // Adiciona o novo listener

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
// INICIALIZAÇÃO DOS MODAIS NO DOMContentLoaded
// =============================================
document.addEventListener('DOMContentLoaded', function() {
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
        console.warn('AVISO JS: Elemento #confirmModal não encontrado no DOM. A função showConfirmModal usará confirm como fallback.');
        // Fallback da função showConfirmModal caso os elementos do modal não sejam encontrados
        window.showConfirmModal = function(message, title = '', type = 'info') { // Ajuste na assinatura para o fallback também
            return new Promise(resolve => {
                const userConfirmed = confirm(`${title}\n${message}`);
                resolve(userConfirmed);
            });
        };
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