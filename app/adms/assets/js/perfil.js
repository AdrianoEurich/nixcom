// assets/js/perfil.js - Versão 5 (com funcionalidade de remoção de foto e debug log)
console.log("perfil.js carregado! Timestamp:", Date.now());

// As funções showLoadingModal, hideLoadingModal, showFeedbackModal e showConfirmModal
// são fornecidas globalmente por general-utils.js.

// Handler do formulário de foto
async function handleFormFotoSubmit(event) {
    event.preventDefault();
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

        setTimeout(() => {
            window.hideLoadingModal();
            console.log('INFO JS: Spinner ocultado (Foto). Mostrando modal de feedback.');

            if (!response.ok || !data.success) {
                window.showFeedbackModal('error', data.message || 'Erro ao atualizar foto', 'Erro na Foto de Perfil');
                return;
            }

            window.showFeedbackModal('success', data.message || 'Foto atualizada com sucesso!', 'Sucesso na Foto de Perfil');
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);

        }, 2000);

    } catch (error) {
        setTimeout(() => {
            window.hideLoadingModal();
            console.log('INFO JS: Spinner ocultado (Foto - Erro). Mostrando modal de feedback (erro).');
            window.showFeedbackModal('error', error.message || 'Ocorreu um erro ao atualizar a foto', 'Erro na Foto de Perfil');
        }, 2000);
    }
}

// Handler para o botão de remover foto
async function handleRemoveFotoClick() {
    const removeUrl = window.URLADM + 'perfil/removerFoto';

    window.showConfirmModal(
        'Deseja realmente remover sua foto de perfil?',
        'Confirmar Remoção',
        'danger'
    ).then(async (confirmed) => {
        console.log("DEBUG: Retorno do showConfirmModal - confirmed:", confirmed);
        if (confirmed) {
            console.log("INFO: Usuário confirmou a remoção da foto. Prosseguindo com a requisição.");
            window.showLoadingModal();
            try {
                const response = await fetch(removeUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                });

                const data = await response.json();

                setTimeout(() => {
                    window.hideLoadingModal();
                    if (!response.ok || !data.success) {
                        window.showFeedbackModal('error', data.message || 'Erro ao remover foto', 'Erro na Remoção de Foto');
                    } else {
                        window.showFeedbackModal('success', data.message || 'Foto removida com sucesso!', 'Sucesso na Remoção de Foto');
                        const fotoPreview = document.getElementById('fotoPreview');
                        if (fotoPreview) {
                            fotoPreview.src = window.URLADM + 'assets/images/users/usuario.png?t=' + Date.now();
                        }
                        const removeFotoBtn = document.getElementById('removeFotoBtn');
                        if (removeFotoBtn) {
                            removeFotoBtn.style.display = 'none';
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                }, 2000);
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
    console.log('🔍 DEBUG: handleFormNomeSubmit chamada');
    e.preventDefault();
    console.log('🔍 DEBUG: PreventDefault executado');
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

        setTimeout(() => {
            window.hideLoadingModal();
            console.log('INFO JS: Spinner ocultado (Nome). Mostrando modal de feedback.');

            if (!response.ok || !data.success) {
                window.showFeedbackModal('error', data.message || 'Erro ao atualizar nome', 'Erro no Nome de Perfil');
                return;
            }

            window.showFeedbackModal('success', data.message || 'Nome atualizado com sucesso!', 'Sucesso no Nome de Perfil');

            // Atualizar o campo de input do nome
            const nomeInput = document.getElementById('nome');
            if (nomeInput) {
                nomeInput.value = novoNome;
            }

            // Atualizar imediatamente o Topbar e datasets
            window.currentUserName = novoNome;
            const topbarNameEl = document.getElementById('topbar-user-name') || document.querySelector('.user-name');
            if (topbarNameEl) topbarNameEl.textContent = novoNome;
            const topbarPhotoEl = document.getElementById('topbar-user-photo');
            if (topbarPhotoEl) topbarPhotoEl.alt = novoNome;
            if (document && document.body && document.body.dataset) {
                document.body.dataset.userName = novoNome;
            }

            if (data.changed) {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        }, 2000);

    } catch (error) {
        setTimeout(() => {
            window.hideLoadingModal();
            console.log('INFO JS: Spinner ocultado (Nome - Erro). Mostrando modal de feedback (erro).');
            window.showFeedbackModal('error', error.message || 'Ocorreu um erro ao atualizar o nome', 'Erro no Nome de Perfil');
        }, 2000);
    }
}

// Handler do formulário de senha
async function handleFormSenhaSubmit(e) {
    e.preventDefault();
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

        setTimeout(() => {
            window.hideLoadingModal();
            console.log('INFO JS: Spinner ocultado (Senha). Mostrando modal de feedback.');

            if (!response.ok || !data.success) {
                window.showFeedbackModal('error', data.message || 'Erro ao atualizar senha', 'Erro na Senha de Perfil');
                return;
            }

            window.showFeedbackModal('success', data.message || 'Senha atualizada com sucesso!', 'Sucesso na Senha de Perfil');
            e.target.reset();

        }, 2000);

    } catch (error) {
        setTimeout(() => {
            window.hideLoadingModal();
            console.log('INFO JS: Spinner ocultado (Senha - Erro). Mostrando modal de feedback (erro).');
            window.showFeedbackModal('error', error.message || 'Ocorreu um erro ao atualizar a senha', 'Erro na Senha de Perfil');
        }, 2000);
    }
}

// Preview da foto (mantido como está, pois não lida com modais)
function setupFotoPreview() {
    console.log('🔍 DEBUG: setupFotoPreview chamada');
    const fotoInput = document.getElementById('fotoInput');
    const fotoPreview = document.getElementById('fotoPreview');
    const fileNameDisplay = document.getElementById('fileName');

    console.log('🔍 DEBUG: Elementos encontrados:');
    console.log('- fotoInput:', fotoInput);
    console.log('- fotoPreview:', fotoPreview);
    console.log('- fileNameDisplay:', fileNameDisplay);

    if (fotoInput && fotoPreview && fileNameDisplay) {
        fotoInput.addEventListener('change', function (e) {
            console.log('🔍 DEBUG: Arquivo selecionado:', e.target.files[0]);
            const file = e.target.files[0];
            if (file) {
                const MAX_FILE_SIZE_BYTES = 32 * 1024 * 1024;
                const MAX_FILE_SIZE_MB = MAX_FILE_SIZE_BYTES / (1024 * 1024);

                if (file.size > MAX_FILE_SIZE_BYTES) {
                    window.showFeedbackModal('error', `A imagem selecionada excede o limite de ${MAX_FILE_SIZE_MB}MB.`, 'Erro de Arquivo');
                    e.target.value = '';
                    fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                    return;
                }

                fileNameDisplay.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function (event) {
                    console.log('🔍 DEBUG: Preview carregado, atualizando imagem...');
                    fotoPreview.src = event.target.result;
                    console.log('🔍 DEBUG: Nova src da imagem:', fotoPreview.src);
                };
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
            }
        });
    }
}

// ** FUNÇÃO DE EXCLUIR CONTA REMOVIDA - USANDO A VERSÃO GLOBAL DO dashboard_custom.js **

// Inicialização da página
function initializePerfilPage() {
    console.log("Inicializando página de perfil...");

    const formFoto = document.getElementById('formFoto');
    const formNome = document.getElementById('formNome');
    const formSenha = document.getElementById('formSenha');
    const removeFotoBtn = document.getElementById('removeFotoBtn');
    
    console.log('🔍 DEBUG: Elementos encontrados:', {
        formFoto: !!formFoto,
        formNome: !!formNome,
        formSenha: !!formSenha,
        removeFotoBtn: !!removeFotoBtn
    });
    
    // Fallback: Interceptar todos os formulários da página para garantir AJAX
    const allForms = document.querySelectorAll('form');
    console.log('🔍 DEBUG: Total de formulários encontrados:', allForms.length);
    
    allForms.forEach((form, index) => {
        console.log(`🔍 DEBUG: Formulário ${index}:`, {
            id: form.id,
            action: form.action,
            method: form.method
        });
        
        // Interceptar formulário de nome especificamente
        if (form.id === 'formNome') {
            console.log('🔍 DEBUG: Interceptando formNome para garantir AJAX');
            form.addEventListener('submit', function(e) {
                console.log('🔍 DEBUG: Submit interceptado no formNome');
                e.preventDefault();
                handleFormNomeSubmit(e);
            });
        }
    });
    
    // ** NOVO: Pega o link de exclusão de conta na barra superior **
    const deleteAccountLink = document.getElementById('deleteAccountLink');

    if (formFoto) {
        console.log('🔍 DEBUG: Adicionando listener ao formFoto');
        formFoto.addEventListener('submit', handleFormFotoSubmit);
    }
    // Removido - agora usando interceptação global acima
    if (formSenha) {
        console.log('🔍 DEBUG: Adicionando listener ao formSenha');
        formSenha.addEventListener('submit', handleFormSenhaSubmit);
    }
    
    if (removeFotoBtn) {
        removeFotoBtn.addEventListener('click', handleRemoveFotoClick);
    }
    
    // ** NOVO: Adiciona o event listener para o link de exclusão **
    if (deleteAccountLink) {
        deleteAccountLink.addEventListener('click', window.handleDeleteAccountClick);
    }

    // ** NOVO: Adiciona o event listener para o botão de exclusão de conta na página de perfil **
    const btnDeleteAccount = document.getElementById('btnDeleteAccount');
    if (btnDeleteAccount) {
        btnDeleteAccount.addEventListener('click', window.handleDeleteAccountClick);
    }

    console.log('🔍 DEBUG: Chamando setupFotoPreview...');
    setupFotoPreview();
}

document.addEventListener('DOMContentLoaded', initializePerfilPage);
window.initializePerfilPage = initializePerfilPage;

// Garantir que a função seja chamada mesmo quando carregada via SPA
console.log('🔍 DEBUG: perfil.js carregado, função initializePerfilPage disponível:', typeof window.initializePerfilPage);

// Verificar se já estamos na página de perfil e chamar a inicialização
if (document.getElementById('fotoPreview') && document.getElementById('fotoInput')) {
    console.log('🔍 DEBUG: Elementos de foto encontrados, chamando setupFotoPreview diretamente...');
    setupFotoPreview();
}

// Tornar as funções globais para acesso via SPA
window.setupFotoPreview = setupFotoPreview;
window.handleFormNomeSubmit = handleFormNomeSubmit;
window.handleFormFotoSubmit = handleFormFotoSubmit;
window.handleFormSenhaSubmit = handleFormSenhaSubmit;
window.handleRemoveFotoClick = handleRemoveFotoClick;