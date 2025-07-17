// app/adms/assets/js/login.js - Versão 8

document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: DOMContentLoaded. Configurando a página de login.');
    setupLoginForm(); // Chama a função de configuração do formulário de login

    // =============================================
    // 1. FUNÇÕES DO FORMULÁRIO DE LOGIN
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
            window.removeError(emailInput); // Usando a função global
            validateLoginEmail(emailInput);
        });
        senhaInput?.addEventListener('input', () => {
            console.log('INFO JS: Input Senha alterado.');
            window.removeError(senhaInput); // Usando a função global
            validateLoginSenha(senhaInput);
        });

        // Event listener para submissão do formulário - AGORA COM AJAX
        form.addEventListener('submit', async function(e) {
            console.log('INFO JS: Evento de submit do LoginForm capturado.');
            e.preventDefault(); // Previne o envio padrão do formulário (que recarregaria a página)

            if (!emailInput || !senhaInput || !submitButton) {
                console.error('ERRO JS: Um ou mais campos/botão do formulário de login não foram encontrados na submissão. Verifique os "name", "type" e ID dos inputs/botão no HTML.');
                window.showFeedbackModal('error', 'Erro interno do formulário. Por favor, recarregue a página.', 'Erro de Login');
                return;
            }

            // Validação final antes da submissão AJAX
            if (validateLoginForm(emailInput, senhaInput)) {
                console.log('INFO JS: Validação do formulário de login passou! Preparando para submissão AJAX.');

                // Ativar o spinner no botão e desabilitá-lo, usando a função global
                const originalHTML = window.activateButtonLoading(submitButton, 'Entrando...');
                
                // MOSTRAR MODAL DE CARREGAMENTO AQUI
                window.showLoadingModal(); 

                const formData = new FormData(this); // Coleta todos os dados do formulário
                const actionUrl = form.getAttribute('action'); // Pega a URL de ação do formulário

                try {
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ message: 'Erro de rede ou resposta não JSON.' }));
                        throw new Error(errorData.message || 'Erro no servidor. Código HTTP: ' + response.status);
                    }

                    const data = await response.json();

                    // ATRASO PARA O SPINNER, DEPOIS ESCONDE CARREGAMENTO E MOSTRA FEEDBACK
                    setTimeout(() => { // Atraso de 2 segundos para o spinner
                        window.hideLoadingModal(); // Esconde o modal de carregamento
                        console.log('INFO JS: Spinner ocultado. Mostrando modal de feedback.'); // Log para depuração
                        if (data.success) {
                            window.showFeedbackModal('success', data.message, 'Login Efetuado!', 4000); 
                            form.reset(); // Limpa o formulário após login bem-sucedido

                            // Redireciona se a URL de redirecionamento for fornecida pelo backend
                            if (data.redirect) {
                                console.log(`INFO JS: Login bem-sucedido. Redirecionando para: ${data.redirect}`);
                                setTimeout(() => {
                                    window.location.href = data.redirect;
                                }, 4000); // Redireciona após o tempo do modal de feedback
                            }
                        } else {
                            window.showFeedbackModal('error', data.message, 'Falha no Login'); 
                        }
                    }, 2000); // 2 segundos de atraso para o spinner

                } catch (error) {
                    console.error('ERRO JS: Erro na requisição AJAX de login:', error);
                    // Garante que o modal de carregamento seja escondido mesmo em caso de erro
                    setTimeout(() => { // Atraso de 2 segundos para o spinner
                        window.hideLoadingModal(); // Esconde o modal de carregamento
                        console.log('INFO JS: Spinner ocultado. Mostrando modal de feedback (erro).'); // Log para depuração
                        window.showFeedbackModal('error', 'Ocorreu um erro ao comunicar com o servidor. Por favor, tente novamente mais tarde.', 'Erro de Comunicação');
                    }, 2000); // 2 segundos de atraso para o spinner
                } finally {
                    // Restaura o botão de submit IMEDIATAMENTE após o feedback ou tentativa de redirecionamento
                    // O atraso do modal de feedback é separado do atraso do spinner
                    window.deactivateButtonLoading(submitButton, originalHTML); 
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
