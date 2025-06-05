// app/adms/assets/js/general-utils.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: general-utils.js carregado. Configurando funcionalidades gerais.');

    // =============================================
    // 1. MODAL DE FEEDBACK (Função Global)
    // =============================================
    const feedbackModalElement = document.getElementById('feedbackModal');
    let feedbackModal;
    let feedbackModalLabel;
    let feedbackMessage;
    let feedbackIcon;

    if (feedbackModalElement) {
        feedbackModal = new bootstrap.Modal(feedbackModalElement);
        feedbackModalLabel = document.getElementById('feedbackModalLabel');
        feedbackMessage = document.getElementById('feedbackMessage');
        feedbackIcon = document.getElementById('feedbackIcon');

        /**
         * Exibe um modal de feedback com um tipo específico (sucesso/erro) e mensagem.
         * @param {string} type - 'success' ou 'error'.
         * @param {string} message - A mensagem a ser exibida.
         */
        window.showFeedbackModal = function(type, message) {
            console.log(`INFO JS: Exibindo modal de feedback - Tipo: ${type}, Mensagem: ${message}`);
            feedbackModalLabel.textContent = type === 'success' ? 'Sucesso!' : 'Erro!';
            feedbackMessage.textContent = message;

            // Limpa as classes de ícones anteriores e define a nova
            feedbackIcon.classList.remove('fa-check-circle', 'fa-times-circle', 'text-success', 'text-danger');
            if (type === 'success') {
                feedbackIcon.classList.add('fas', 'fa-check-circle', 'text-success');
            } else {
                feedbackIcon.classList.add('fas', 'fa-times-circle', 'text-danger');
            }
            feedbackIcon.style.fontSize = '3rem'; // Garante o tamanho do ícone

            feedbackModal.show();
            // Opcional: Oculta automaticamente o modal após alguns segundos
            setTimeout(() => {
                feedbackModal.hide();
            }, 4000);
        };
    } else {
        console.warn('AVISO JS: Elemento #feedbackModal não encontrado. A função showFeedbackModal usará alert como fallback.');
        window.showFeedbackModal = function(type, message) {
            alert(`Mensagem do Sistema (${type}):\n\n${message}`);
        };
    }

    // =============================================
    // 2. TRATAMENTO DE ERROS DE CAMPO DE FORMULÁRIO (Funções Globais)
    // =============================================
    /**
     * Exibe uma mensagem de erro abaixo de um campo de entrada do formulário.
     * @param {HTMLElement} inputElement - O elemento de entrada.
     * @param {string} message - A mensagem de erro.
     */
    window.showError = function(inputElement, message) {
        console.log(`INFO JS: Exibindo erro para o input ${inputElement.name || inputElement.id}: ${message}`);
        const formGroup = inputElement.closest('.mb-2, .input-group'); // Verifica o pai .mb-2 ou .input-group
        if (!formGroup) {
            console.error('ERRO JS: Pai .mb-2 ou .input-group não encontrado para o input.', inputElement);
            return;
        }

        let errorDiv = formGroup.querySelector('.text-danger.invalid-feedback-custom');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.classList.add('text-danger', 'mt-1', 'small', 'invalid-feedback-custom');
            formGroup.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
        inputElement.classList.add('is-invalid');
        inputElement.classList.remove('is-valid'); // Remove o estado válido se o erro for exibido
    };

    /**
     * Remove a mensagem de erro de um campo de entrada do formulário.
     * @param {HTMLElement} inputElement - O elemento de entrada.
     */
    window.removeError = function(inputElement) {
        console.log(`INFO JS: Removendo erro para o input ${inputElement.name || inputElement.id}.`);
        const formGroup = inputElement.closest('.mb-2, .input-group');
        if (!formGroup) return;

        const errorDiv = formGroup.querySelector('.text-danger.invalid-feedback-custom');
        if (errorDiv) {
            errorDiv.remove();
        }
        inputElement.classList.remove('is-invalid');
        inputElement.classList.remove('is-valid'); // Garante que não haja estado válido persistente
    };

    // =============================================
    // 3. VALIDAÇÃO DE E-MAIL (Função Global)
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
    // 4. ESTADO DE CARREGAMENTO DO BOTÃO (Funções Globais)
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
        buttonElement.style.pointerEvents = 'none'; // Impede cliques duplos
        buttonElement.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${loadingText}`;
        console.log(`INFO JS: Botão ${buttonElement.id || buttonElement.name} ativado para carregamento.`);
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
        console.log(`INFO JS: Botão ${buttonElement.id || buttonElement.name} desativado e restaurado.`);
    };

    // =============================================
    // 5. ALERTAS COM DISPENSA AUTOMÁTICA (Função Global)
    // =============================================
    /**
     * Configura a dispensa automática para alertas do Bootstrap.
     */
    window.setupAutoDismissAlerts = function() {
        console.log('INFO JS: Configurando dispensa automática para alertas.');
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500); // Aguarda a transição terminar
            }, 4000); // Alerta visível por 4 segundos
        });
    };

    // Chama a função de alertas automáticos quando general-utils.js é carregado
    window.setupAutoDismissAlerts();

    // =============================================
    // 6. ALTERNAR VISIBILIDADE DA SENHA (Função Global)
    // =============================================
    // Anexa um listener de evento a todos os elementos com a classe 'toggle-password'
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            console.log('INFO JS: Botão de alternar senha clicado.');
            const input = this.closest('.input-group')?.querySelector('input[type="password"], input[type="text"]');
            const icon = this.querySelector('i');

            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            } else {
                console.warn('AVISO JS: Não foi possível encontrar o input ou ícone associado para o botão toggle-password.', this);
            }
        });
    });
});