// assets/js/perfil.js - Versão 5 (com funcionalidade de remoção de foto e debug log)
console.log("perfil.js carregado! Timestamp:", Date.now());

// As funções showLoadingModal, hideLoadingModal, showFeedbackModal e showConfirmModal
// agora são fornecidas globalmente por general-utils.js.
// Não precisamos reimplementá-las aqui.

// Handler do formulário de foto
async function handleFormFotoSubmit(event) {
    event.preventDefault();
    // Usa a função global de carregamento
    window.showLoadingModal(); 

    try {
        const form = event.target;
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        // ATRASO PARA O SPINNER, DEPOIS ESCONDE CARREGAMENTO E MOSTRA FEEDBACK
        setTimeout(() => { // Atraso de 2 segundos para o spinner
            window.hideLoadingModal(); // Esconde o modal de carregamento
            console.log('INFO JS: Spinner ocultado (Foto). Mostrando modal de feedback.'); // Log para depuração

            if (!response.ok || !data.success) {
                // Usa a função global de feedback para erro
                window.showFeedbackModal('error', data.message || 'Erro ao atualizar foto', 'Erro na Foto de Perfil');
                return; 
            }

            // Usa a função global de feedback para sucesso
            window.showFeedbackModal('success', data.message || 'Foto atualizada com sucesso!', 'Sucesso na Foto de Perfil');
            
            // Recarrega a página após o modal de sucesso ser exibido e o usuário clicar em OK
            // ou após o tempo de autoCloseDelay do modal de feedback.
            // Adiciona um pequeno atraso para garantir que o modal seja visto
            setTimeout(() => {
                window.location.reload();
            }, 1500); // Ajuste o tempo conforme a necessidade

        }, 2000); // 2 segundos de atraso para o spinner

    } catch (error) {
        // Garante que o modal de loading seja escondido mesmo em caso de erro
        setTimeout(() => { // Atraso de 2 segundos para o spinner
            window.hideLoadingModal(); // Esconde o modal de carregamento
            console.log('INFO JS: Spinner ocultado (Foto - Erro). Mostrando modal de feedback (erro).'); // Log para depuração
            // Usa a função global de feedback para erro
            window.showFeedbackModal('error', error.message || 'Ocorreu um erro ao atualizar a foto', 'Erro na Foto de Perfil');
        }, 2000); // 2 segundos de atraso para o spinner
    }
}

// Handler para o botão de remover foto
async function handleRemoveFotoClick() {
    // URL para a ação de remoção no seu controlador Perfil.php
    const removeUrl = window.URLADM + 'perfil/removerFoto'; 

    window.showConfirmModal(
        'Deseja realmente remover sua foto de perfil?',
        'Confirmar Remoção',
        'danger' // Cor do cabeçalho do modal de confirmação
    ).then(async (confirmed) => {
        // --- LINHA DE DEBUG ADICIONADA AQUI ---
        console.log("DEBUG: Retorno do showConfirmModal - confirmed:", confirmed); 
        // -------------------------------------
        if (confirmed) {
            console.log("INFO: Usuário confirmou a remoção da foto. Prosseguindo com a requisição.");
            window.showLoadingModal();
            try {
                const response = await fetch(removeUrl, {
                    method: 'POST', // Usamos POST para alterar dados no servidor
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json' // Indicando que estamos enviando JSON (opcional, mas boa prática)
                    },
                    // Não precisamos de body se o userId já está na sessão do PHP
                    // body: JSON.stringify({ userId: window.userId }) // Se o userId não estivesse na sessão
                });

                const data = await response.json();

                setTimeout(() => {
                    window.hideLoadingModal();
                    if (!response.ok || !data.success) {
                        window.showFeedbackModal('error', data.message || 'Erro ao remover foto', 'Erro na Remoção de Foto');
                    } else {
                        window.showFeedbackModal('success', data.message || 'Foto removida com sucesso!', 'Sucesso na Remoção de Foto');
                        // Atualiza a imagem de preview para a foto padrão imediatamente
                        const fotoPreview = document.getElementById('fotoPreview');
                        if (fotoPreview) {
                            fotoPreview.src = window.URLADM + 'assets/images/users/usuario.png?t=' + Date.now();
                        }
                        // Esconde o botão de remover foto
                        const removeFotoBtn = document.getElementById('removeFotoBtn');
                        if (removeFotoBtn) {
                            removeFotoBtn.style.display = 'none';
                        }
                        // Recarrega a página após um curto atraso para refletir a sessão atualizada
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                }, 2000); // Atraso do spinner
            } catch (error) {
                setTimeout(() => {
                    window.hideLoadingModal();
                    window.showFeedbackModal('error', error.message || 'Ocorreu um erro ao remover a foto', 'Erro de Conexão');
                }, 2000);
            }
        } else {
            console.log("INFO: Remoção de foto cancelada pelo usuário (ou modal fechado sem confirmação).");
        }
    });
}

// Handler do formulário de nome
async function handleFormNomeSubmit(e) {
    e.preventDefault();
    // Usa a função global de carregamento
    window.showLoadingModal(); 

    try {
        const nomeInput = document.getElementById('nome');
        const novoNome = nomeInput.value.trim();
        const response = await fetch(e.target.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({ nome: novoNome })
        });

        const data = await response.json();

        // ATRASO PARA O SPINNER, DEPOIS ESCONDE CARREGAMENTO E MOSTRA FEEDBACK
        setTimeout(() => { // Atraso de 2 segundos para o spinner
            window.hideLoadingModal(); // Esconde o modal de carregamento
            console.log('INFO JS: Spinner ocultado (Nome). Mostrando modal de feedback.'); // Log para depuração

            if (!response.ok || !data.success) {
                // Usa a função global de feedback para erro
                window.showFeedbackModal('error', data.message || 'Erro ao atualizar nome', 'Erro no Nome de Perfil');
                return; 
            }

            // Usa a função global de feedback para sucesso
            window.showFeedbackModal('success', data.message || 'Nome atualizado com sucesso!', 'Sucesso no Nome de Perfil');

            // Atualiza o nome na interface
            window.currentUserName = novoNome;
            const userNameDisplay = document.querySelector('.user-name');
            if (userNameDisplay) userNameDisplay.textContent = novoNome;

            // Recarrega a página se o backend indicar que houve mudança e for necessário
            if (data.changed) {
                setTimeout(() => {
                    window.location.reload();
                }, 1500); // Ajuste o tempo conforme a necessidade
            }
        }, 2000); // 2 segundos de atraso para o spinner

    } catch (error) {
        // Garante que o modal de loading seja escondido mesmo em caso de erro
        setTimeout(() => { // Atraso de 2 segundos para o spinner
            window.hideLoadingModal(); // Esconde o modal de carregamento
            console.log('INFO JS: Spinner ocultado (Nome - Erro). Mostrando modal de feedback (erro).'); // Log para depuração
            // Usa a função global de feedback para erro
            window.showFeedbackModal('error', error.message || 'Ocorreu um erro ao atualizar o nome', 'Erro no Nome de Perfil');
        }, 2000); // 2 segundos de atraso para o spinner
    }
}

// Handler do formulário de senha
async function handleFormSenhaSubmit(e) {
    e.preventDefault();
    // Usa a função global de carregamento
    window.showLoadingModal(); 

    try {
        const response = await fetch(e.target.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(new FormData(e.target))
        });

        const data = await response.json();

        // ATRASO PARA O SPINNER, DEPOIS ESCONDE CARREGAMENTO E MOSTRA FEEDBACK
        setTimeout(() => { // Atraso de 2 segundos para o spinner
            window.hideLoadingModal(); // Esconde o modal de carregamento
            console.log('INFO JS: Spinner ocultado (Senha). Mostrando modal de feedback.'); // Log para depuração

            if (!response.ok || !data.success) {
                // Usa a função global de feedback para erro
                window.showFeedbackModal('error', data.message || 'Erro ao atualizar senha', 'Erro na Senha de Perfil');
                return; 
            }

            // Usa a função global de feedback para sucesso
            window.showFeedbackModal('success', data.message || 'Senha atualizada com sucesso!', 'Sucesso na Senha de Perfil');
            e.target.reset(); // Limpa o formulário de senha

        }, 2000); // 2 segundos de atraso para o spinner

    } catch (error) {
        // Garante que o modal de loading seja escondido mesmo em caso de erro
        setTimeout(() => { // Atraso de 2 segundos para o spinner
            window.hideLoadingModal(); // Esconde o modal de carregamento
            console.log('INFO JS: Spinner ocultado (Senha - Erro). Mostrando modal de feedback (erro).'); // Log para depuração
            // Usa a função global de feedback para erro
            window.showFeedbackModal('error', error.message || 'Ocorreu um erro ao atualizar a senha', 'Erro na Senha de Perfil');
        }, 2000); // 2 segundos de atraso para o spinner
    }
}

// Preview da foto (mantido como está, pois não lida com modais)
function setupFotoPreview() {
    const fotoInput = document.getElementById('fotoInput');
    const fotoPreview = document.getElementById('fotoPreview');
    const fileNameDisplay = document.getElementById('fileName');

    if (fotoInput && fotoPreview && fileNameDisplay) {
        fotoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // ALTERADO: Aumentado o limite para 16MB
                const MAX_FILE_SIZE_BYTES = 16 * 1024 * 1024; // 16MB
                const MAX_FILE_SIZE_MB = MAX_FILE_SIZE_BYTES / (1024 * 1024);

                if (file.size > MAX_FILE_SIZE_BYTES) {
                    // Usa a função global de feedback para erro
                    window.showFeedbackModal('error', `A imagem selecionada excede o limite de ${MAX_FILE_SIZE_MB}MB.`, 'Erro de Arquivo');
                    e.target.value = ''; // Limpa o input para que o mesmo arquivo não seja enviado novamente
                    // fotoPreview.src = 'URL_DA_SUA_IMAGEM_PADRAO_AQUI'; // Opcional: define a imagem padrão se houver erro
                    fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                    return; // Interrompe a execução para não prosseguir com o arquivo inválido
                }

                fileNameDisplay.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function (event) {
                    fotoPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                // fotoPreview.src = 'URL_DA_SUA_IMAGEM_PADRAO_AQUI'; // Opcional: define a imagem padrão quando não há arquivo
            }
        });
    }
}

// Inicialização da página
function initializePerfilPage() {
    console.log("Inicializando página de perfil...");

    // Configura os formulários
    const formFoto = document.getElementById('formFoto');
    const formNome = document.getElementById('formNome');
    const formSenha = document.getElementById('formSenha');
    const removeFotoBtn = document.getElementById('removeFotoBtn'); // Pega o botão de remover foto

    if (formFoto) formFoto.addEventListener('submit', handleFormFotoSubmit);
    if (formNome) formNome.addEventListener('submit', handleFormNomeSubmit);
    if (formSenha) formSenha.addEventListener('submit', handleFormSenhaSubmit);
    
    // Adiciona o event listener para o botão de remover foto
    if (removeFotoBtn) {
        removeFotoBtn.addEventListener('click', handleRemoveFotoClick);
    }

    // Configura o preview da foto
    setupFotoPreview();
}

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', initializePerfilPage);

// Torna a função disponível globalmente para SPA
window.initializePerfilPage = initializePerfilPage;

// Adiciona URLADM ao escopo global (assumindo que já está definido no PHP antes de carregar este script)
// Caso contrário, você pode precisar passá-lo via um data attribute ou uma variável JS inline no perfil.php
// Ex: <script>window.URLADM = "<?php echo URLADM; ?>";</script> ANTES de carregar perfil.js
// Pelo que vejo na perfil.php, você já tem o script assim:
// <script src="<?= URLADM ?>assets/js/perfil.js?v=<?= time() ?>"></script>
// Isso significa que URLADM está disponível via PHP para a URL do script,
// mas para ser usada DENTRO do JS, é melhor expô-la como uma variável JS.
// Adicione a seguinte linha na sua perfil.php ANTES de incluir perfil.js:
// <script>const URLADM = "<?php echo URLADM; ?>";</script>
// Ou, como você já tem uma variável "currentUserName" sendo passada:
// <script>
//    const currentUserName = "<?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Nome do Usuário', ENT_QUOTES, 'UTF-8') ?>";
//    const URLADM = "<?= URLADM ?>"; // <-- Adicione esta linha
// </script>
// Eu vou assumir que você fará esta adição ou que URLADM já está disponível globalmente de alguma outra forma.
// Caso contrário, o fetch para "perfil/removerFoto" pode falhar.