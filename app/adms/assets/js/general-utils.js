/**
 * general-utils.js
 * Versão 29 - Ajustes para auto-hide do feedbackModal e uso consistente da API Bootstrap Modal.
 */
console.info('INFO JS: general-utils.js (Versão 29 - Ajustes para modais) carregado. Configurando funcionalidades gerais.');

// Variáveis globais para armazenar referências aos elementos e instâncias dos modais Bootstrap
let feedbackModalElement;
let feedbackModalInstance; // Instância do Bootstrap Modal
let feedbackModalLabel;
let feedbackMessage;
let feedbackIcon;
let feedbackOkButton; 
let feedbackModalHeader;

let confirmModalElement;
let confirmModalInstance; // Instância do Bootstrap Modal
let confirmModalLabel;
let confirmModalBody;
let confirmModalConfirmBtn;
let confirmModalCancelBtn;
let confirmModalHeader;

let loadingModalElement;
let loadingModalInstance; // Instância do Bootstrap Modal

// Função para remover a classe 'is-invalid' e o feedback de erro de um elemento
window.removeError = function(element) {
    if (element) {
        element.classList.remove('is-invalid');
        if (element.closest('.photo-upload-box')) {
            element.closest('.photo-upload-box').classList.remove('is-invalid-media');
        }
        const feedbackDiv = document.getElementById(element.id + '-feedback') || element.nextElementSibling;
        if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
            feedbackDiv.textContent = '';
        }
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
window.showFeedbackModal = function(type, message, title = '', autoCloseDelay = 3000) { // Padrão 3 segundos
    console.log(`INFO JS: showFeedbackModal chamado - Tipo: ${type}, Mensagem: ${message}, Título: ${title}`);
    
    // Verifica se os elementos e a instância foram inicializados
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
        alert(`Mensagem do Sistema (${title || type}):\n\n${message}`);
        return;
    }

    // Limpa as classes e conteúdos anteriores
    feedbackModalHeader.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning', 'bg-primary', 'text-white', 'text-dark');
    feedbackIcon.classList.remove('fa-check-circle', 'fa-times-circle', 'fa-info-circle', 'fa-exclamation-triangle', 'text-success', 'text-danger', 'text-info', 'text-warning', 'text-primary');
    feedbackOkButton.classList.remove('btn-success', 'btn-danger', 'btn-info', 'btn-warning', 'btn-primary');

    // Define o conteúdo do modal
    feedbackModalLabel.textContent = title || (type === 'success' ? 'Sucesso!' : (type === 'error' ? 'Erro!' : (type === 'info' ? 'Informação' : (type === 'warning' ? 'Aviso' : (type === 'primary' ? 'Detalhes' : 'Mensagem')))));
    feedbackMessage.textContent = message;

    let iconClass = '';
    let textColorClass = '';
    let headerBgClass = '';
    let footerBtnClass = '';
    let headerTextColorClass = 'text-white'; // Padrão para texto branco no cabeçalho

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
            headerTextColorClass = 'text-dark'; // Texto escuro para cabeçalho info (azul claro)
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

    // Mostra o modal usando a instância do Bootstrap
    feedbackModalInstance.show();

    // Oculta automaticamente o modal após o atraso especificado
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
 * @param {string} title Título do modal.
 * @param {string} message Conteúdo da mensagem.
 * @param {Function} onConfirm Callback a ser executado se o usuário confirmar.
 * @param {Function} [onCancel] Callback a ser executado se o usuário cancelar.
 * @param {string} [confirmButtonText='Confirmar'] Texto do botão de confirmação.
 * @param {string} [cancelButtonText='Cancelar'] Texto do botão de cancelar.
 */
window.showConfirmModal = function(title, message, onConfirm, onCancel = null, confirmButtonText = 'Confirmar', cancelButtonText = 'Cancelar') {
    console.log(`INFO JS: showConfirmModal chamado - Título: ${title}, Mensagem: ${message}`);
    
    // Verifica se os elementos e a instância foram inicializados
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
        const userConfirmed = confirm(`${title}\n${message}`);
        if (userConfirmed) {
            onConfirm();
        } else if (onCancel) {
            onCancel();
        }
        return;
    }

    // Limpa as classes de cabeçalho anteriores e define as novas (padrão para confirmação é warning)
    confirmModalHeader.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning', 'bg-primary', 'text-white', 'text-dark');
    confirmModalHeader.classList.add('bg-warning', 'text-white');

    confirmModalLabel.textContent = title;
    confirmModalBody.innerHTML = `<p>${message}</p>`;

    confirmModalConfirmBtn.textContent = confirmButtonText;
    confirmModalCancelBtn.textContent = cancelButtonText;

    // Remove listeners antigos para evitar múltiplas execuções
    // É mais seguro remover e adicionar listeners para evitar vazamentos de memória ou múltiplos disparos
    confirmModalConfirmBtn.removeEventListener('click', confirmModalConfirmBtn._currentHandler);
    confirmModalCancelBtn.removeEventListener('click', confirmModalCancelBtn._currentHandler);

    const newConfirmHandler = () => {
        onConfirm();
        confirmModalInstance.hide();
    };
    const newCancelHandler = () => {
        if (onCancel) {
            onCancel();
        }
        confirmModalInstance.hide();
    };

    confirmModalConfirmBtn.addEventListener('click', newConfirmHandler);
    confirmModalCancelBtn.addEventListener('click', newCancelHandler);

    // Armazena os novos handlers para poder removê-los depois
    confirmModalConfirmBtn._currentHandler = newConfirmHandler;
    confirmModalCancelBtn._currentHandler = newCancelHandler;

    // Mostra o modal usando a instância do Bootstrap
    confirmModalInstance.show();
};

// =============================================
// 3. MODAL DE CARREGAMENTO (Funções Globais)
// =============================================

/**
 * Exibe o modal de carregamento.
 */
window.showLoadingModal = function() {
    console.log('INFO JS: showLoadingModal chamado. Exibindo modal de carregamento.');
    if (loadingModalInstance) {
        loadingModalInstance.show();
    } else {
        console.warn('AVISO JS: loadingModalInstance não inicializado. Não foi possível exibir o modal de carregamento.');
    }
};

/**
 * Oculta o modal de carregamento.
 */
window.hideLoadingModal = function() {
    console.log('INFO JS: hideLoadingModal chamado. Ocultando modal de carregamento.');
    if (loadingModalInstance) {
        loadingModalInstance.hide();
    } else {
        console.warn('AVISO JS: loadingModalInstance não inicializado. Não foi possível ocultar o modal de carregamento.');
    }
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
    let parentContainer = inputElement.closest('.mb-4, .mb-3, .mb-2, .input-group, .form-group'); 
    
    if (!parentContainer) {
        parentContainer = inputElement.parentElement; 
    }

    if (!parentContainer) {
        console.error('ERRO JS: Nenhum contêiner adequado encontrado para o input.', inputElement);
        return;
    }

    window.removeError(inputElement); 

    let errorDiv = document.createElement('div');
    errorDiv.classList.add('text-danger', 'mt-1', 'small', 'invalid-feedback-custom');
    errorDiv.textContent = message;
    
    if (inputElement.tagName === 'SELECT' || inputElement.tagName === 'INPUT' || inputElement.tagName === 'TEXTAREA') {
        inputElement.classList.add('is-invalid');
        inputElement.classList.remove('is-valid');
        inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
    } else { 
        parentContainer.appendChild(errorDiv);
    }
};

/**
 * Remove a mensagem de erro de um campo de entrada do formulário.
 * @param {HTMLElement} inputElement - O elemento de entrada.
 */
window.removeError = function(inputElement) {
    console.log(`INFO JS: Removendo erro para o input ${inputElement.name || inputElement.id || inputElement.tagName}.`);
    
    let parentContainer = inputElement.closest('.mb-4, .mb-3, .mb-2, .input-group, .form-group');
    if (!parentContainer) {
        parentContainer = inputElement.parentElement;
    }

    if (!parentContainer) return; 

    let errorDiv = parentContainer.querySelector('.text-danger.invalid-feedback-custom');
    if (!errorDiv && (inputElement.tagName === 'SELECT' || inputElement.tagName === 'INPUT' || inputElement.tagName === 'TEXTAREA')) {
        if (inputElement.nextElementSibling && inputElement.nextElementSibling.classList.contains('invalid-feedback-custom')) {
            errorDiv = inputElement.nextElementSibling;
        }
    }

    if (errorDiv) {
        errorDiv.remove();
    }
    inputElement.classList.remove('is-invalid');
    inputElement.classList.remove('is-valid');
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

    // Inicializa elementos do feedbackModal
    feedbackModalElement = document.getElementById('feedbackModal');
    if (feedbackModalElement) {
        // Garante que a instância do modal é criada apenas uma vez
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

    // Inicializa elementos do confirmModal
    confirmModalElement = document.getElementById('confirmModal');
    if (confirmModalElement) {
        if (!confirmModalInstance) {
            confirmModalInstance = new bootstrap.Modal(confirmModalElement);
        }
        confirmModalLabel = document.getElementById('confirmModalLabel');
        confirmModalBody = document.getElementById('confirmModalBody');
        confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');
        confirmModalCancelBtn = document.getElementById('confirmModalCancelBtn');
        confirmModalHeader = confirmModalElement.querySelector('.modal-header');
        console.log('DEBUG JS: confirmModal elementos inicializados.', {
            confirmModalElement: !!confirmModalElement,
            confirmModalInstance: !!confirmModalInstance,
            confirmModalLabel: !!confirmModalLabel,
            confirmModalBody: !!confirmModalBody,
            confirmModalConfirmBtn: !!confirmModalConfirmBtn,
            confirmModalCancelBtn: !!confirmModalCancelBtn,
            confirmModalHeader: !!confirmModalHeader
        });
    } else {
        console.warn('AVISO JS: Elemento #confirmModal não encontrado no DOM. A função showConfirmModal usará confirm como fallback.');
        window.showConfirmModal = function(title, message, onConfirm, onCancel = null) {
            const userConfirmed = confirm(`${title}\n${message}`);
            if (userConfirmed) {
                onConfirm();
            } else if (onCancel) {
                onCancel();
            }
        };
    }

    // Inicializa elementos do loadingModal
    loadingModalElement = document.getElementById('loadingModal');
    if (loadingModalElement) {
        if (!loadingModalInstance) {
            loadingModalInstance = new bootstrap.Modal(loadingModalElement, {
                backdrop: 'static', // Impede que o modal seja fechado clicando fora
                keyboard: false     // Impede que o modal seja fechado com a tecla ESC
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
