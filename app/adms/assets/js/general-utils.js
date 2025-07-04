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
            if (!feedbackModalLabel || !feedbackMessage || !feedbackIcon) {
                console.error("ERRO JS: Elementos do modal de feedback não encontrados. Usando alert como fallback.");
                alert(`Mensagem do Sistema (${type}):\n\n${message}`);
                return;
            }

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
        console.log(`INFO JS: Exibindo erro para o input ${inputElement.name || inputElement.id || inputElement.tagName}: ${message}`);
        // Tenta encontrar o form-group mais próximo ou o contêiner direto para o erro
        let parentContainer = inputElement.closest('.mb-4, .mb-3, .mb-2, .input-group, .form-group'); // Adicionado .mb-4, .mb-3, .form-group para maior compatibilidade
        
        // Se o elemento pai não for um form-group, tenta o próprio elemento inputElement
        // ou um elemento pai comum se o erro for para um grupo de checkboxes, por exemplo.
        // O crucial é ter um lugar para anexar a div de erro.
        if (!parentContainer) {
            parentContainer = inputElement.parentElement; // Último recurso: apenas o pai direto
        }

        if (!parentContainer) {
            console.error('ERRO JS: Nenhum contêiner adequado encontrado para o input.', inputElement);
            return;
        }

        // Remove qualquer erro existente para este elemento
        window.removeError(inputElement); 

        let errorDiv = document.createElement('div');
        errorDiv.classList.add('text-danger', 'mt-1', 'small', 'invalid-feedback-custom');
        errorDiv.textContent = message;
        
        // Insere a div de erro após o elemento ou dentro do formGroup
        // Para selects e inputs, é melhor após. Para grupos, pode ser no final do container.
        if (inputElement.tagName === 'SELECT' || inputElement.tagName === 'INPUT' || inputElement.tagName === 'TEXTAREA') {
            inputElement.classList.add('is-invalid');
            inputElement.classList.remove('is-valid');
            inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
        } else { // Para outros elementos como checkboxes/radios groups ou photo-upload-box
            parentContainer.appendChild(errorDiv);
        }
    };

    /**
     * Remove a mensagem de erro de um campo de entrada do formulário.
     * @param {HTMLElement} inputElement - O elemento de entrada.
     */
    window.removeError = function(inputElement) {
        console.log(`INFO JS: Removendo erro para o input ${inputElement.name || inputElement.id || inputElement.tagName}.`);
        
        // Encontra o contêiner pai ou o elemento diretamente
        let parentContainer = inputElement.closest('.mb-4, .mb-3, .mb-2, .input-group, .form-group');
        if (!parentContainer) {
            parentContainer = inputElement.parentElement;
        }

        if (!parentContainer) return; // Não há contêiner para remover o erro

        // Procura a div de erro dentro do contêiner ou adjacente ao input
        let errorDiv = parentContainer.querySelector('.text-danger.invalid-feedback-custom');
        if (!errorDiv && (inputElement.tagName === 'SELECT' || inputElement.tagName === 'INPUT' || inputElement.tagName === 'TEXTAREA')) {
            // Se o erro foi inserido logo após o input, ele não estará dentro do parentContainer.
            // Precisamos ser mais flexíveis.
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
    // 5. ALERTAS COM DISPENSA AUTOMÁTICA (Função Global)
    // =============================================
    /**
     * Configura a dispensa automática para alertas do Bootstrap.
     * É executada na carga inicial e também pode ser chamada após carregamento SPA.
     */
    window.setupAutoDismissAlerts = function() {
        document.querySelectorAll('.alert').forEach(alert => {
            if (alert.dataset.autodismissed) return; // Evita configurar o mesmo alerta múltiplas vezes
            alert.dataset.autodismissed = 'true';

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
    // 6. ALTERNAR VISIBILIDADE DA SENHA (Listener Global para elementos dinâmicos)
    // =============================================
    // Usa um event listener delegado no document para capturar cliques em botões .toggle-password,
    // garantindo que funcione para elementos carregados via AJAX também.
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
});
