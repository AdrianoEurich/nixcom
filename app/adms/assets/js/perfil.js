// assets/js/perfil.js - Versão Atualizada
console.log("perfil.js carregado! Timestamp:", Date.now());

// Controle de instância do modal
let loadingModalInstance = null;

// Funções de controle do modal de loading
function showLoadingModal() {
    console.log("Mostrando modal de loading...");
    const modalElement = document.getElementById('loadingModal');
    if (modalElement) {
        // Fecha qualquer instância existente
        if (loadingModalInstance) {
            loadingModalInstance.hide();
        }

        loadingModalInstance = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false,
            focus: true
        });

        // Mostra o modal
        loadingModalInstance.show();

        // Força o display block caso necessário
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
    }
}

function hideLoadingModal() {
    console.log("Escondendo modal de loading...");
    if (loadingModalInstance) {
        // Adiciona um pequeno delay para garantir a animação
        setTimeout(() => {
            loadingModalInstance.hide();

            // Limpa a instância
            loadingModalInstance = null;

            // Garante o fechamento completo
            const modalElement = document.getElementById('loadingModal');
            if (modalElement) {
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
            }

            // Remove o backdrop se existir
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
        }, 100);
    }
}

// Funções auxiliares para modais
async function showAlertModal(title, message) {
    return new Promise((resolve) => {
        const modalElement = document.getElementById('alertModal');
        if (!modalElement) {
            console.error('Modal de alerta não encontrado!');
            alert(`${title}\n${message}`);
            return resolve();
        }

        document.getElementById('alertModalLabel').textContent = title;
        document.getElementById('alertModalBody').innerHTML = `<p class="fs-5">${message}</p>`;

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        // Configura o botão de confirmação
        const btn = document.getElementById('alertModalBtn');
        const handler = () => {
            modal.hide();
            resolve();
        };

        btn.onclick = handler;

        // Mostra o modal
        modal.show();
    });
}

async function showConfirmModal(title, message) {
    return new Promise((resolve) => {
        const modalElement = document.getElementById('confirmModal');
        if (!modalElement) {
            console.error('Modal de confirmação não encontrado!');
            return resolve(confirm(`${title}\n${message}`));
        }

        document.getElementById('confirmModalLabel').textContent = title;
        document.getElementById('confirmModalBody').innerHTML = `<p class="fs-5">${message}</p>`;

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        // Configura os botões
        const confirmBtn = document.getElementById('confirmModalBtn');
        const cancelBtn = document.getElementById('confirmModalCancelBtn');

        // Remove event listeners antigos
        const newConfirmBtn = confirmBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);

        confirmBtn.replaceWith(newConfirmBtn);
        cancelBtn.replaceWith(newCancelBtn);

        // Configura os novos handlers
        newConfirmBtn.onclick = () => {
            modal.hide();
            resolve(true);
        };

        newCancelBtn.onclick = () => {
            modal.hide();
            resolve(false);
        };

        // Mostra o modal
        modal.show();
    });
}

// Handler do formulário de foto
async function handleFormFotoSubmit(event) {
    event.preventDefault();
    showLoadingModal();

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

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erro ao atualizar foto');
        }

        hideLoadingModal();
        await showAlertModal('Sucesso', data.message || 'Foto atualizada com sucesso!');
        window.location.reload();

    } catch (error) {
        hideLoadingModal();
        await showAlertModal('Erro', error.message || 'Ocorreu um erro ao atualizar a foto');
    }
}

// Handler do formulário de nome
async function handleFormNomeSubmit(e) {
    e.preventDefault();
    showLoadingModal();

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

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erro ao atualizar nome');
        }

        hideLoadingModal();
        await showAlertModal('Sucesso', data.message || 'Nome atualizado com sucesso!');

        // Atualiza o nome na interface
        window.currentUserName = novoNome;
        const userNameDisplay = document.querySelector('.user-name');
        if (userNameDisplay) userNameDisplay.textContent = novoNome;

        if (data.changed) {
            window.location.reload();
        }

    } catch (error) {
        hideLoadingModal();
        await showAlertModal('Erro', error.message || 'Ocorreu um erro ao atualizar o nome');
    }
}

// Handler do formulário de senha
async function handleFormSenhaSubmit(e) {
    e.preventDefault();
    showLoadingModal();

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

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erro ao atualizar senha');
        }

        hideLoadingModal();
        await showAlertModal('Sucesso', data.message || 'Senha atualizada com sucesso!');
        e.target.reset();

    } catch (error) {
        hideLoadingModal();
        await showAlertModal('Erro', error.message || 'Ocorreu um erro ao atualizar a senha');
    }
}

// Preview da foto
function setupFotoPreview() {
    const fotoInput = document.getElementById('fotoInput');
    const fotoPreview = document.getElementById('fotoPreview');
    const fileNameDisplay = document.getElementById('fileName');

    if (fotoInput && fotoPreview && fileNameDisplay) {
        fotoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // ***** ADICIONE ESTE BLOCO DE CÓDIGO ABAIXO *****
                const MAX_FILE_SIZE_BYTES = 4 * 1024 * 1024; // 4MB
                const MAX_FILE_SIZE_MB = MAX_FILE_SIZE_BYTES / (1024 * 1024);

                if (file.size > MAX_FILE_SIZE_BYTES) {
                    showAlertModal('Erro de Arquivo', `A imagem selecionada excede o limite de ${MAX_FILE_SIZE_MB}MB.`);
                    e.target.value = ''; // Limpa o input para que o mesmo arquivo não seja enviado novamente
                    fotoPreview.src = 'URL_DA_SUA_IMAGEM_PADRAO_AQUI'; // Opcional: define a imagem padrão se houver erro
                    fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                    return; // Interrompe a execução para não prosseguir com o arquivo inválido
                }
                // *************************************************

                fileNameDisplay.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function (event) {
                    fotoPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                fotoPreview.src = 'URL_DA_SUA_IMAGEM_PADRAO_AQUI'; // Opcional: define a imagem padrão quando não há arquivo
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

    if (formFoto) formFoto.addEventListener('submit', handleFormFotoSubmit);
    if (formNome) formNome.addEventListener('submit', handleFormNomeSubmit);
    if (formSenha) formSenha.addEventListener('submit', handleFormSenhaSubmit);

    // Configura o preview da foto
    setupFotoPreview();
}

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', initializePerfilPage);

// Torna a função disponível globalmente para SPA
window.initializePerfilPage = initializePerfilPage;