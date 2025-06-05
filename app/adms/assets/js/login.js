// app/adms/assets/js/login.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: DOMContentLoaded. Configurando a página de login.');
    setupLoginForm(); // Chama a função de configuração do formulário de login

    // =============================================
    // 1. TOGGLE VISIBILIDADE DA SENHA
    // =============================================
    // Este bloco foi movido para general-utils.js para ser global.
    // Se você DESEJA mantê-lo apenas aqui no login.js, descomente-o e remova-o de general-utils.js.
    /*
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            console.log('INFO JS: Botão de alternar senha clicado.');
            // Usar closest('.input-group') para encontrar o input corretamente
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
    */

    // =============================================
    // 2. FUNÇÕES DO FORMULÁRIO DE LOGIN
    // =============================================
    function setupLoginForm() {
        const form = document.getElementById('loginForm');
        if (!form) {
            console.log('INFO JS: LoginForm não encontrado (ID "loginForm" ausente no HTML). Ignorando setup.');
            return;
        }
        console.log('INFO JS: LoginForm encontrado. Iniciando setup.');

        const emailInput = form.querySelector('input[name="login[email]"]');
        const senhaInput = form.querySelector('input[name="login[senha]"]');
        const submitButton = form.querySelector('button[type="submit"]');

        // Adicionar console.log para verificar se os inputs e o botão são encontrados
        if (!emailInput) console.error('ERRO JS: emailInput não encontrado. Verifique o "name" do input.');
        if (!senhaInput) console.error('ERRO JS: senhaInput não encontrado. Verifique o "name" do input.');
        if (!submitButton) console.error('ERRO JS: submitButton não encontrado. Verifique o "type" e se é um botão de submit.');


        emailInput?.addEventListener('input', () => {
            console.log('INFO JS: Input Email alterado.');
            // removeError será chamado aqui implicitamente se a validação passar
            validateLoginEmail(emailInput);
        });
        senhaInput?.addEventListener('input', () => {
            console.log('INFO JS: Input Senha alterado.');
            // removeError será chamado aqui implicitamente se a validação passar
            validateLoginSenha(senhaInput);
        });

        // Event listener para submissão do formulário - AGORA COM AJAX
        form.addEventListener('submit', async function(e) { // Adicionado 'async' para usar await
            console.log('INFO JS: Evento de submit do LoginForm capturado.');
            e.preventDefault(); // Previne o envio padrão do formulário (que recarregaria a página)

            if (!emailInput || !senhaInput || !submitButton) {
                console.error('ERRO JS: Um ou mais campos/botão do formulário de login não foram encontrados na submissão. Verifique os "name", "type" e ID dos inputs/botão no HTML.');
                // Exibir um feedback de erro ao usuário, se possível
                window.showFeedbackModal('error', 'Erro interno do formulário. Por favor, recarregue a página.');
                return;
            }

            // Validação final antes da submissão AJAX
            if (validateLoginForm(emailInput, senhaInput)) {
                console.log('INFO JS: Validação do formulário de login passou! Preparando para submissão AJAX.');

                // Ativar o spinner no botão e desabilitá-lo, usando a função global
                const originalHTML = window.activateButtonLoading(submitButton, 'Entrando...');

                const formData = new FormData(this); // Coleta todos os dados do formulário
                const actionUrl = form.getAttribute('action'); // Pega a URL de ação do formulário

                try {
                    const response = await fetch(actionUrl, { // Usa fetch para enviar via AJAX
                        method: 'POST',
                        body: formData
                    });

                    // Verifica se a resposta HTTP foi bem-sucedida (ex: status 200 OK)
                    if (!response.ok) {
                        // Tenta analisar a mensagem de erro JSON, ou fornece uma genérica
                        const errorData = await response.json().catch(() => ({ message: 'Erro de rede ou resposta não JSON.' }));
                        throw new Error(errorData.message || 'Erro no servidor. Código HTTP: ' + response.status);
                    }

                    const data = await response.json(); // Analisa a resposta JSON do servidor

                    // Adiciona um delay antes de exibir o feedback/redirecionar
                    // Isso dá tempo para o servidor processar e o modal aparecer
                    setTimeout(() => {
                        if (data.success) {
                            window.showFeedbackModal('success', data.message);
                            form.reset(); // Limpa o formulário após login bem-sucedido

                            // Redireciona se a URL de redirecionamento for fornecida pelo backend
                            if (data.redirect) {
                                console.log(`INFO JS: Login bem-sucedido. Redirecionando para: ${data.redirect}`);
                                // CRÍTICO: Redireciona após exibir a mensagem de sucesso por um tempo
                                setTimeout(() => {
                                    window.location.href = data.redirect;
                                }, 4000); // Redireciona após 2 segundos
                            }
                        } else {
                            // Se a operação NÃO foi bem-sucedida (data.success é false)
                            window.showFeedbackModal('error', data.message);
                            // Mantenha os dados do formulário para tentativas de login inválidas
                            // (o backend não deve limpar isso, já que não estamos recarregando a página completa)
                        }
                        // Restaura o botão de submit após o feedback ou tentativa de redirecionamento
                        window.deactivateButtonLoading(submitButton, originalHTML); 
                    }, 2000); // Delay de 2 segundos para o modal aparecer antes do botão ser restaurado

                } catch (error) {
                    console.error('ERRO JS: Erro na requisição AJAX de login:', error);
                    // Adiciona um delay aqui para erros na requisição (rede, servidor)
                    setTimeout(() => {
                        window.showFeedbackModal('error', 'Erro ao processar o login. Por favor, tente novamente mais tarde.');
                        window.deactivateButtonLoading(submitButton, originalHTML); // Restaura o botão
                    }, 2000); // Delay de 2 segundos
                }
            } else {
                console.error('ERRO JS: Validação do formulário de login falhou. Não enviando via AJAX.');
                // O botão não precisa ser restaurado aqui, pois a validação local falhou
            }
        });

        // =============================================
        // FUNÇÕES DE VALIDAÇÃO ESPECÍFICAS DO LOGIN (usando funções globais)
        // =============================================

        function validateLoginEmail(input) {
            console.log('INFO JS: Validando Email. Valor:', input.value);
            if (input.value.trim() === '') {
                window.showError(input, 'Por favor, insira seu e-mail.');
                return false;
            } else if (!window.validateEmail(input.value.trim())) {
                window.showError(input, 'Por favor, insira um e-mail válido.');
                return false;
            }
            window.removeError(input); // Remove o erro se a validação for bem-sucedida
            return true;
        }

        function validateLoginSenha(input) {
            console.log('INFO JS: Validando Senha. Valor:', input.value);
            if (input.value.trim() === '') {
                window.showError(input, 'Por favor, insira sua senha.');
                return false;
            }
            // Você pode adicionar validação de comprimento mínimo aqui, se não for feita pelo backend
            // Exemplo: if (input.value.length < 6) { window.showError(input, 'A senha deve ter no mínimo 6 caracteres.'); return false; }
            window.removeError(input); // Remove o erro se a validação for bem-sucedida
            return true;
        }

        function validateLoginForm(emailInput, senhaInput) {
            console.log('INFO JS: Iniciando validação final do formulário de login...');
            // Garante que todas as validações sejam executadas para mostrar todos os erros
            const isEmailValid = validateLoginEmail(emailInput);
            const isSenhaValid = validateLoginSenha(senhaInput);
            const allValid = isEmailValid && isSenhaValid;
            console.log('INFO JS: Resultado da validação final do login:', allValid);
            return allValid;
        }
    }
});