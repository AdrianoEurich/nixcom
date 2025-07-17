// app/adms/assets/js/cadastro.js - Versão 7

document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: cadastro.js carregado. Tentando configurar o formulário de cadastro.');

    function setupCadastroForm() {
        const form = document.getElementById('cadastroForm');
        if (!form) {
            return;
        }
        console.log('INFO JS: CadastroForm encontrado. Iniciando setup.');

        const nomeInput = form.querySelector('input[name="cadastro[nome]"]');
        const emailInput = form.querySelector('input[name="cadastro[email]"]');
        const senhaInput = form.querySelector('input[name="cadastro[senha]"]');
        const confirmarSenhaInput = form.querySelector('input[name="cadastro[confirmar_senha]"]');
        const submitButton = form.querySelector('button[type="submit"]');

        // Adiciona listeners para validação em tempo real enquanto o usuário digita nos campos.
        nomeInput?.addEventListener('input', () => {
            if (nomeInput.value.trim() === '') {
                window.showError(nomeInput, 'Por favor, insira seu nome completo.');
            } else {
                window.removeError(nomeInput);
            }
        });
        emailInput?.addEventListener('input', () => {
            if (emailInput.value.trim() === '') {
                window.showError(emailInput, 'Por favor, insira seu e-mail.');
                return;
            } else if (!window.validateEmail(emailInput.value.trim())) {
                window.showError(emailInput, 'Por favor, insira um e-mail válido.');
                return;
            } else {
                window.removeError(emailInput);
            }
        });
        senhaInput?.addEventListener('input', () => {
            if (senhaInput.value.trim() === '') {
                window.showError(senhaInput, 'Por favor, insira uma senha.');
                return;
            } else if (senhaInput.value.length < 6) {
                window.showError(senhaInput, 'A senha deve ter pelo menos 6 caracteres.');
                return;
            } else {
                window.removeError(senhaInput);
            }
            if (confirmarSenhaInput && confirmarSenhaInput.value.trim() !== '') {
                if (confirmarSenhaInput.value !== senhaInput.value) {
                    window.showError(confirmarSenhaInput, 'As senhas não coincidem.');
                } else {
                    window.removeError(confirmarSenhaInput);
                }
            }
        });
        confirmarSenhaInput?.addEventListener('input', () => {
            if (confirmarSenhaInput.value.trim() === '') {
                window.showError(confirmarSenhaInput, 'Por favor, confirme sua senha.');
                return;
            } else if (confirmarSenhaInput.value !== senhaInput.value) {
                window.showError(confirmarSenhaInput, 'As senhas não coincidem.');
                return;
            } else {
                window.removeError(confirmarSenhaInput);
            }
        });

        // Adiciona um listener para o evento de 'submit' do formulário.
        form.addEventListener('submit', async function(e) {
            console.log('INFO JS: Evento de submit do CadastroForm capturado.');
            e.preventDefault(); // IMPEDE O SUBMIT PADRÃO DO NAVEGADOR

            if (!nomeInput || !emailInput || !senhaInput || !confirmarSenhaInput || !submitButton) {
                console.error('ERRO JS: Um ou mais campos/botão do formulário de cadastro não foram encontrados. Verifique os "name", "type" e ID dos inputs/botão no HTML.');
                return;
            }

            // Realiza a validação final de todos os campos antes de permitir o envio.
            if (validateForm(nomeInput, emailInput, senhaInput, confirmarSenhaInput)) {
                console.log('INFO JS: Validação do formulário de cadastro passou! Preparando para envio AJAX.');

                // Ativa o spinner no botão e desabilita
                const originalHTML = window.activateButtonLoading(submitButton, 'Cadastrando...');
                
                // MOSTRAR MODAL DE CARREGAMENTO AQUI
                window.showLoadingModal();

                const formData = new FormData(this); // Pega todos os dados do formulário
                const actionUrl = form.getAttribute('action'); // Pega a URL de ação do formulário

                try {
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ message: 'Erro de rede ou resposta não JSON.' }));
                        throw new Error(errorData.message || 'Erro no servidor.');
                    }

                    const data = await response.json();

                    // ATRASO PARA O SPINNER, DEPOIS ESCONDE CARREGAMENTO E MOSTRA FEEDBACK
                    setTimeout(() => { // Atraso de 2 segundos para o spinner
                        window.hideLoadingModal(); // Esconde o modal de carregamento
                        console.log('INFO JS: Spinner ocultado. Mostrando modal de feedback.'); // Log para depuração
                        if (data.success) {
                            window.showFeedbackModal('success', data.message, 'Cadastro Efetuado!', 4000); 
                            form.reset(); // Limpa o formulário
                            // Redireciona se a URL de redirecionamento for fornecida pelo backend
                            if (data.redirect) {
                                setTimeout(() => { window.location.href = data.redirect; }, 4000); 
                            }
                        } else {
                            window.showFeedbackModal('error', data.message, 'Falha no Cadastro'); 
                        }
                    }, 2000); // 2 segundos de atraso para o spinner

                } catch (error) {
                    console.error('ERRO JS: Erro na requisição AJAX de cadastro:', error);
                    // Garante que o modal de carregamento seja escondido mesmo em caso de erro
                    setTimeout(() => { // Atraso de 2 segundos para o spinner
                        window.hideLoadingModal(); // Esconde o modal de carregamento
                        console.log('INFO JS: Spinner ocultado. Mostrando modal de feedback (erro).'); // Log para depuração
                        window.showFeedbackModal('error', 'Erro ao processar o cadastro. Por favor, tente novamente mais tarde.', 'Erro de Comunicação');
                    }, 2000); // 2 segundos de atraso para o spinner
                } finally {
                    // Restaura o botão de submit IMEDIATAMENTE após o feedback ou tentativa de redirecionamento
                    // O atraso do modal de feedback é separado do atraso do spinner
                    window.deactivateButtonLoading(submitButton, originalHTML); 
                }

            } else {
                console.error('ERRO JS: Validação do formulário de cadastro falhou. Não enviando.');
            }
        });

        // =============================================
        // FUNÇÕES DE VALIDAÇÃO ESPECÍFICAS DO CADASTRO
        // =============================================
        function validateNome(input) {
            if (input.value.trim() === '') {
                window.showError(input, 'Por favor, insira seu nome completo.');
                return false;
            }
            window.removeError(input);
            return true;
        }

        function validateEmailField(input) {
            if (input.value.trim() === '') {
                window.showError(input, 'Por favor, insira seu e-mail.');
                return false;
            } else if (!window.validateEmail(input.value.trim())) {
                window.showError(input, 'Por favor, insira um e-mail válido.');
                return false;
            }
            window.removeError(input);
            return true;
        }

        function validateSenha(input) {
            if (input.value.trim() === '') {
                window.showError(input, 'Por favor, insira uma senha.');
                return false;
            } else if (input.value.length < 6) {
                window.showError(input, 'A senha deve ter pelo menos 6 caracteres.');
                return false;
            }
            window.removeError(input);
            return true;
        }

        function validateConfirmacaoSenha(senhaInput, confirmInput) {
            if (confirmInput.value.trim() === '') {
                window.showError(confirmInput, 'Por favor, confirme sua senha.');
                return false;
            } else if (confirmInput.value !== senhaInput.value) {
                window.showError(confirmInput, 'As senhas não coincidem.');
                return false;
            }
            window.removeError(confirmInput);
            return true;
        }

        function validateForm(nomeInput, emailInput, senhaInput, confirmarSenhaInput) {
            console.log('INFO JS: Iniciando validação final do formulário de cadastro...');
            const isNomeValid = validateNome(nomeInput);
            const isEmailValid = validateEmailField(emailInput);
            const isSenhaValid = validateSenha(senhaInput);
            const isConfirmValid = validateConfirmacaoSenha(senhaInput, confirmarSenhaInput);

            const allValid = isNomeValid && isEmailValid && isSenhaValid && isConfirmValid;
            console.log('INFO JS: Resultado da validação final do cadastro:', allValid);
            return allValid;
        }
    }

    setupCadastroForm();
});
