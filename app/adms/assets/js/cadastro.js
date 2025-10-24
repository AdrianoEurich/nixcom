// Cadastro JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado - iniciando JavaScript');
    
    // Elementos principais
    const elements = {
        planCards: document.querySelectorAll('.plan-card'),
        btnContinue: document.getElementById('btnContinue'),
        btnCreateAccount: document.getElementById('btnCreateAccount'),
        planCardsContainer: document.querySelector('.plan-cards-container'),
        signupHeader: document.querySelector('.signup-header'),
        stepElements: {
            step1: document.getElementById('step1'),
            step2: document.getElementById('step2'),
            step3: document.getElementById('step3'),
            form: document.querySelector('.form-container')
        },
        navbarToggler: document.querySelector('.navbar-toggler'),
        navbarCollapse: document.querySelector('.navbar-collapse')
    };
    
    console.log('Elementos encontrados:', elements);
    
    let selectedPlan = null;
    let currentStep = 1;
    let isSubmitting = false; // Flag para evitar múltiplas submissões
    let requestWatchdog = null; // Timeout para evitar travamento do loading
    
    // Função para mostrar step
    function showStep(step) {
        console.log('Mostrando passo:', step);
        currentStep = step;
        
        // Atualizar indicadores de step
        if (elements.stepElements.step1) {
            elements.stepElements.step1.classList.toggle('active', step >= 1);
            elements.stepElements.step1.classList.toggle('completed', step > 1);
        }
        
        if (elements.stepElements.step2) {
            elements.stepElements.step2.classList.toggle('active', step >= 2);
            elements.stepElements.step2.classList.toggle('completed', step > 2);
            elements.stepElements.step2.classList.toggle('disabled', step < 2);
        }
        
        if (elements.stepElements.step3) {
            elements.stepElements.step3.classList.toggle('active', step >= 3);
            elements.stepElements.step3.classList.toggle('completed', step > 3);
            elements.stepElements.step3.classList.toggle('disabled', step < 3);
        }
        
        // Mostrar/esconder conteúdo baseado no step
        if (step === 2 && elements.planCardsContainer && elements.stepElements.form) {
            // Esconder cards dos planos e mostrar formulário
            elements.planCardsContainer.style.opacity = '0';
            setTimeout(() => {
                elements.planCardsContainer.style.display = 'none';
                elements.stepElements.form.style.display = 'block';
                elements.stepElements.form.style.opacity = '1';
                
                // Scroll suave para o formulário
                elements.stepElements.form.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }, 300);
        }
        
        // Atualizar título baseado no step
        updateStepTitle(step);
    }
    
    // Função para atualizar o título baseado no step
    function updateStepTitle(step) {
        const titleElement = document.querySelector('.signup-title');
        const subtitleElement = document.querySelector('.signup-subtitle');
        
        if (!titleElement || !subtitleElement) return;
        
        switch(step) {
            case 1:
                titleElement.textContent = 'Escolha Seu Plano';
                subtitleElement.textContent = 'Selecione o plano que melhor atende às suas necessidades';
                break;
            case 2:
                titleElement.textContent = 'Seus Dados';
                subtitleElement.textContent = 'Preencha suas informações para continuar';
                break;
            case 3:
                titleElement.textContent = 'Quase Pronto!';
                subtitleElement.textContent = 'Sua conta está sendo criada...';
                break;
        }
    }
    
    // Função para verificar se formulário está completo (versão melhorada)
    function checkFormComplete() {
        const requiredFields = ['nome', 'email', 'telefone', 'cpf', 'senha', 'confirmar_senha'];
        let allValid = true;
        
        requiredFields.forEach(fieldName => {
            if (!validateField(fieldName)) {
                allValid = false;
            }
        });
        
        // Habilitar/desabilitar botão continuar
        if (elements.btnCreateAccount) {
            elements.btnCreateAccount.disabled = !allValid;
        }
        
        // Atualizar step 2
        if (elements.stepElements.step2) {
            if (allValid) {
                elements.stepElements.step2.classList.add('completed');
                elements.stepElements.step2.classList.remove('disabled');
            } else {
                elements.stepElements.step2.classList.remove('completed');
                elements.stepElements.step2.classList.add('disabled');
            }
        }
        
        return allValid;
    }
    
    // Função para adicionar listeners do formulário
    function addFormListeners() {
        const formFields = ['nome', 'email', 'telefone', 'senha', 'confirmar_senha', 'cpf'];
        
        formFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', checkFormComplete);
                field.addEventListener('blur', checkFormComplete);
            }
        });
    }
    
    // Função para salvar dados do usuário
    function saveUserData(planType, callback) {
        const formData = {
            cadastro: {
                nome: document.getElementById('nome').value,
                email: document.getElementById('email').value,
                telefone: document.getElementById('telefone').value,
                senha: document.getElementById('senha').value,
                confirmar_senha: document.getElementById('confirmar_senha').value,
                cpf: document.getElementById('cpf').value,
                plan_type: planType
            }
        };
        
        // Failsafe: limpar possíveis resíduos de modal/backdrop antes de iniciar
        try {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        } catch(e) {}

        // Iniciar requisição
        fetch(window.URLADM + 'cadastro/salvar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            console.log('Resposta recebida:', response);
            console.log('Status da resposta:', response.status);
            console.log('Headers da resposta:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                console.log('Resposta em texto:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.error('Texto recebido:', text);
                    throw new Error('Resposta não é um JSON válido');
                }
            });
        })
        .then(data => {
            // Limpar watchdog de travamento
            if (requestWatchdog) { clearTimeout(requestWatchdog); requestWatchdog = null; }
            if (data.success) {
                console.log('Usuário criado com sucesso:', data);
                if (callback) callback(data);
            } else {
                console.error('Erro ao criar usuário:', data.message);
                isSubmitting = false; // Reset flag em caso de erro

                const messageRaw = String(data.message || '');
                const message = messageRaw.toLowerCase();

                // Tratamento especial para email duplicado com modal bonito de confirmação
                if (message.includes('e-mail') && message.includes('cadastrado')) {
                    // marcar campo como inválido
                    const emailInput = document.getElementById('email');
                    const emailFb = document.getElementById('email-feedback');
                    if (emailInput) emailInput.classList.add('is-invalid');
                    if (emailFb) emailFb.textContent = data.message;

                    if (typeof window.showConfirmModal === 'function') {
                        window.showConfirmModal(
                            'O e-mail informado já está sendo usado por outro usuário. Deseja ir para a página de login?',
                            'E-mail já cadastrado',
                            'warning'
                        ).then(goLogin => {
                            if (goLogin) {
                                window.location.href = window.URLADM + 'login';
                            } else {
                                // Garantir fechamento do loading e voltar para Step 2
                                if (typeof window.hideLoadingModal === 'function') { window.hideLoadingModal(); }
                                try { showStep(2); } catch(e){}
                                isSubmitting = false;
                                if (emailInput) {
                                    emailInput.focus();
                                    emailInput.select && emailInput.select();
                                }
                            }
                        });
                    } else if (typeof window.showFeedbackModal === 'function') {
                        window.showFeedbackModal('warning', 'O e-mail informado já está sendo usado por outro usuário.', 'E-mail já cadastrado', 4500);
                        // Garantir retorno para Step 2
                        try { showStep(2); } catch(e){}
                        isSubmitting = false;
                    } else {
                        alert('E-mail já cadastrado.');
                        try { showStep(2); } catch(e){}
                        isSubmitting = false;
                    }
                    return;
                }

                // Tratamento especial para CPF duplicado
                if (message.includes('cpf') && (message.includes('cadastr') || message.includes('existe'))) {
                    const cpfInput = document.getElementById('cpf');
                    const cpfFb = document.getElementById('cpf-feedback');
                    if (cpfInput) cpfInput.classList.add('is-invalid');
                    if (cpfFb) cpfFb.textContent = messageRaw;
                    if (typeof window.showFeedbackModal === 'function') {
                        // Deixe o general-utils fechar o loading e então mostrar o feedback
                        window.showFeedbackModal('error', messageRaw, 'Erro ao criar conta', 4500);
                    } else {
                        alert('Erro ao criar conta: ' + messageRaw);
                    }
                    try { showStep(2); } catch(e){}
                    isSubmitting = false;
                    return;
                }

                // Caso geral: mostrar modal de erro bonito (mensagem como corpo, título genérico)
                if (typeof window.showFeedbackModal === 'function') {
                    // Deixe o general-utils fechar o loading e então mostrar o feedback
                    window.showFeedbackModal('error', messageRaw || 'Não foi possível criar sua conta.', 'Erro ao criar conta', 4500);
                } else {
                    alert('Erro ao criar conta: ' + (messageRaw || 'Tente novamente.'));
                }
                try { showStep(2); } catch(e){}
                isSubmitting = false;
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            console.error('Detalhes do erro:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            if (requestWatchdog) { clearTimeout(requestWatchdog); requestWatchdog = null; }
            isSubmitting = false; // Reset flag em caso de erro
            
            // Esconder modal de loading se estiver aberto
            if (typeof window.hideLoadingModal === 'function') {
                window.hideLoadingModal();
            }
            
            // Mostrar modal de erro
            if (typeof window.showFeedbackModal === 'function') {
                window.showFeedbackModal('error', 'Erro de conexão', 'Não foi possível criar sua conta. Tente novamente.', 4500);
            } else {
                alert('Erro ao criar conta. Tente novamente.');
            }
        });
    }
    
    // Função de gerar email removida para modo de produção
    
    // ========================================
    // FUNÇÕES DE VALIDAÇÃO
    // ========================================
    
    function validateField(fieldName) {
        const field = document.getElementById(fieldName);
        const feedback = document.getElementById(fieldName + '-feedback');
        
        if (!field || !feedback) return false;
        
        let isValid = true;
        let message = '';
        
        switch (fieldName) {
            case 'nome':
                const nome = field.value.trim();
                if (nome.length < 2) {
                    isValid = false;
                    message = 'Nome deve ter pelo menos 2 caracteres.';
                } else if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(nome)) {
                    isValid = false;
                    message = 'Nome deve conter apenas letras e espaços.';
                }
                break;
                
            case 'email':
                const email = field.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    isValid = false;
                    message = 'Digite um e-mail válido.';
                }
                break;
                
            case 'telefone':
                const telefone = field.value.replace(/\D/g, '');
                if (telefone.length < 10) {
                    isValid = false;
                    message = 'Telefone deve ter pelo menos 10 dígitos.';
                }
                break;
                
            case 'cpf':
                const cpf = field.value.replace(/\D/g, '');
                if (cpf.length !== 11) {
                    isValid = false;
                    message = 'CPF deve ter 11 dígitos.';
                } else if (!validateCPF(cpf)) {
                    isValid = false;
                    message = 'CPF inválido.';
                }
                break;
                
            case 'senha':
                const senha = field.value;
                if (senha.length < 6) {
                    isValid = false;
                    message = 'Senha deve ter pelo menos 6 caracteres.';
                }
                break;
                
            case 'confirmar_senha':
                const confirmarSenha = field.value;
                const senhaOriginal = document.getElementById('senha').value;
                if (confirmarSenha !== senhaOriginal) {
                    isValid = false;
                    message = 'As senhas não coincidem.';
                }
                break;
        }
        
        // Aplicar validação visual
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            feedback.textContent = '';
            feedback.style.display = 'none';
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
        
        return isValid;
    }
    
    function validateCPF(cpf) {
        // Remove caracteres não numéricos
        cpf = cpf.replace(/\D/g, '');
        
        // Verifica se tem 11 dígitos
        if (cpf.length !== 11) return false;
        
        // Verifica se todos os dígitos são iguais
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        
        // Validação do primeiro dígito verificador
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(9))) return false;
        
        // Validação do segundo dígito verificador
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(10))) return false;
        
        return true;
    }
    
    function togglePasswordVisibility(fieldId, button) {
        const field = document.getElementById(fieldId);
        const icon = button.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Função checkFormComplete já definida acima
    
    // Event listeners para cards de planos
    if (elements.planCards && elements.planCards.length > 0) {
        console.log('Cards encontrados:', elements.planCards.length);
        
        elements.planCards.forEach((card, index) => {
            console.log('Adicionando listener ao card', index, card);
            
            // Verificar se o card tem o atributo data-plan
            const planType = card.getAttribute('data-plan');
            console.log('Tipo do plano:', planType);
            
            if (!planType) {
                console.error('Card sem atributo data-plan:', card);
                return;
            }
            
            card.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Card clicado:', this);
                console.log('Plano do card:', this.getAttribute('data-plan'));
                
                // Remover seleção anterior
                elements.planCards.forEach(c => {
                    c.classList.remove('selected');
                    console.log('Removendo seleção do card:', c);
                });
                
                // Selecionar card atual
                this.classList.add('selected');
                selectedPlan = this.getAttribute('data-plan');
                
                console.log('Card selecionado:', this);
                console.log('Plano selecionado:', selectedPlan);
                
                // Habilitar botão continuar
                if (elements.btnContinue) {
                    elements.btnContinue.disabled = false;
                    elements.btnContinue.classList.remove('disabled');
                    console.log('Botão continuar habilitado');
                }
                
                // Scroll para o topo
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            
            // Adicionar cursor pointer
            card.style.cursor = 'pointer';
        });
    } else {
        console.error('Nenhum card de plano encontrado!');
        console.log('Elementos:', elements);
    }
    
    // Event listener para botão continuar
    if (elements.btnContinue) {
        console.log('Botão continuar encontrado');
        
        elements.btnContinue.addEventListener('click', function() {
            console.log('Botão continuar clicado');
            console.log('Plano selecionado:', selectedPlan);
            
            if (selectedPlan) {
                showStep(2);
                addFormListeners();
            }
        });
    }
    
    // Event listener para botão criar conta
    if (elements.btnCreateAccount) {
        elements.btnCreateAccount.addEventListener('click', function() {
            if (isSubmitting) {
                console.log('Submissão já em andamento, ignorando clique');
                return;
            }

            console.log('Criar conta clicado');
            console.log('Plano selecionado:', selectedPlan);

            if (selectedPlan && checkFormComplete()) {
                isSubmitting = true;
                console.log('Iniciando submissão do formulário');

                // Mostrar modal de loading
                if (typeof window.showLoadingModal === 'function') {
                    window.showLoadingModal('Criando sua conta...', 'Aguarde enquanto processamos seus dados.');
                }

                // Iniciar watchdog (fail-safe) para não travar no loading (8s)
                try { if (requestWatchdog) clearTimeout(requestWatchdog); } catch(e){}
                requestWatchdog = setTimeout(() => {
                    console.warn('WARN JS: Watchdog disparado. Fechando loading e exibindo erro genérico.');
                    if (typeof window.hideLoadingModal === 'function') { window.hideLoadingModal(); }
                    if (typeof window.showFeedbackModal === 'function') {
                        setTimeout(() => {
                            window.showFeedbackModal('error', 'A solicitação demorou mais do que o esperado. Verifique sua conexão e tente novamente.', 'Tempo excedido', 5000);
                        }, 200);
                    }
                    try { showStep(2); } catch(e){}
                    isSubmitting = false;
                }, 8000);

                // Mostrar step 3 durante o processo
                showStep(3);

                saveUserData(selectedPlan, function(data) {
                    console.log('Callback executado com dados:', data);
                    
                    // Limpar watchdog
                    try { if (requestWatchdog) { clearTimeout(requestWatchdog); requestWatchdog = null; } } catch(e){}

                    if (data.redirect) {
                        console.log('Redirecionando para:', data.redirect);
                        
                        // Manter modal de loading por mais tempo para melhor UX
                        setTimeout(() => {
                            // Esconder modal de loading
                            if (typeof window.hideLoadingModal === 'function') {
                                window.hideLoadingModal();
                            }
                            
                            // Mostrar modal de sucesso
                            if (typeof window.showFeedbackModal === 'function') {
                                window.showFeedbackModal('success', 'Conta criada com sucesso!', 'Redirecionando para criação de anúncio...');
                            }
                            
                            // Adicionar um delay para mostrar o modal de sucesso antes de redirecionar
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 2000);
                        }, 1500); // Manter loading por 1.5 segundos
                    } else {
                        console.warn('Nenhuma URL de redirecionamento fornecida');
                        isSubmitting = false; // Reset flag se não houver redirecionamento
                        
                        // Esconder modal de loading
                        if (typeof window.hideLoadingModal === 'function') {
                            window.hideLoadingModal();
                        }
                    }
                });
            }
        });
    }
    
    // Botão de gerar email removido para modo de produção
    
    // ========================================
    // VALIDAÇÕES E MÁSCARAS ROBUSTAS
    // ========================================
    
    // Máscara e validação de CPF
    const cpfField = document.getElementById('cpf');
    if (cpfField) {
        cpfField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            // Limitar a 11 dígitos
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
            
            // Validar CPF em tempo real
            validateField('cpf');
        });
        
        cpfField.addEventListener('blur', function() {
            validateField('cpf');
        });
    }
    
    // Máscara e validação de telefone
    const telefoneField = document.getElementById('telefone');
    if (telefoneField) {
        telefoneField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            // Limitar a 11 dígitos
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            value = value.replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3');
            e.target.value = value;
            
            // Validar telefone em tempo real
            validateField('telefone');
        });
        
        telefoneField.addEventListener('blur', function() {
            validateField('telefone');
        });
    }
    
    // Validação de nome
    const nomeField = document.getElementById('nome');
    if (nomeField) {
        nomeField.addEventListener('input', function() {
            validateField('nome');
        });
        
        nomeField.addEventListener('blur', function() {
            validateField('nome');
        });
    }
    
    // Validação de email
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('input', function() {
            validateField('email');
        });
        
        emailField.addEventListener('blur', function() {
            validateField('email');
        });
    }
    
    // Validação de senha
    const senhaField = document.getElementById('senha');
    if (senhaField) {
        senhaField.addEventListener('input', function() {
            validateField('senha');
            // Validar confirmação de senha também
            validateField('confirmar_senha');
        });
        
        senhaField.addEventListener('blur', function() {
            validateField('senha');
            validateField('confirmar_senha');
        });
    }
    
    // Validação de confirmação de senha
    const confirmarSenhaField = document.getElementById('confirmar_senha');
    if (confirmarSenhaField) {
        confirmarSenhaField.addEventListener('input', function() {
            validateField('confirmar_senha');
        });
        
        confirmarSenhaField.addEventListener('blur', function() {
            validateField('confirmar_senha');
        });
    }
    
    // Toggle de visibilidade das senhas
    const toggleSenha = document.getElementById('toggleSenha');
    const toggleConfirmarSenha = document.getElementById('toggleConfirmarSenha');
    
    if (toggleSenha) {
        toggleSenha.addEventListener('click', function() {
            togglePasswordVisibility('senha', this);
        });
    }
    
    if (toggleConfirmarSenha) {
        toggleConfirmarSenha.addEventListener('click', function() {
            togglePasswordVisibility('confirmar_senha', this);
        });
    }
    
    // Hamburger menu toggle
    if (elements.navbarToggler && elements.navbarCollapse) {
        console.log('Botão hambúrguer encontrado');
        
        elements.navbarToggler.addEventListener('click', function() {
            console.log('Hamburger clicado');
            
            // Limpar listeners antigos
            const newToggler = elements.navbarToggler.cloneNode(true);
            elements.navbarToggler.parentNode.replaceChild(newToggler, elements.navbarToggler);
            
            // Atualizar referência
            elements.navbarToggler = newToggler;
            
            // Toggle do collapse
            if (elements.navbarCollapse.classList.contains('show')) {
                elements.navbarCollapse.classList.remove('show');
            } else {
                elements.navbarCollapse.classList.add('show');
            }
            
            // Adicionar listener novamente
            elements.navbarToggler.addEventListener('click', arguments.callee);
        });
        
        // Fechar menu ao clicar fora
        document.addEventListener('click', function(e) {
            if (!elements.navbarToggler.contains(e.target) && 
                !elements.navbarCollapse.contains(e.target) &&
                elements.navbarCollapse.classList.contains('show')) {
                elements.navbarCollapse.classList.remove('show');
            }
        });
    }
    
    // Inicializar step 1
    showStep(1);
});