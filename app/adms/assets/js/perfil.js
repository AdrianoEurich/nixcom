/**
 * Script JavaScript para a página de perfil do usuário.
 * Gerencia a atualização de informações do perfil (nome, foto, senha)
 * e o soft delete da conta.
 *
 * @version 1.0.23 - Atualiza nome e foto na topbar e sidebar.
 * @author Seu Nome
 */

console.log('perfil.js: Script carregado e executando globalmente. Versão 1.0.23.'); // Log para confirmar o carregamento do arquivo

// Define a função de inicialização da página de perfil globalmente.
// Esta função será chamada explicitamente pelo dashboard_custom.js.
window.initializePerfilPage = function() {
    console.log('perfil.js: initializePerfilPage() chamado. Timestamp:', Date.now());
    console.log('DEBUG PERFIL JS: Dataset do Body ao inicializar:', document.body.dataset);

    // Configura o preview da foto
    setupFotoPreview();
    
    // Configura a delegação de eventos para os formulários de perfil
    // Esta função só precisa ser chamada UMA VEZ para o document.body
    // para evitar múltiplos listeners.
    if (!window._perfilFormDelegationSetup) { // Flag para garantir que só é configurado uma vez
        setupPerfilFormDelegation();
        window._perfilFormDelegationSetup = true;
        console.log('DEBUG PERFIL JS: Flag _perfilFormDelegationSetup definida como true.');
    } else {
        console.log('DEBUG PERFIL JS: Delegação de eventos de formulário já configurada. Pulando re-configuração.');
    }

    // Configura o botão de soft delete da conta
    setupSoftDeleteAccount();
    
    // Preenche os dados do perfil ao carregar a página
    populateProfileData();
};


/**
 * Preenche os campos do formulário de perfil com os dados do usuário.
 * Os dados são lidos dos atributos 'data-*' do elemento body, que são definidos pelo PHP.
 */
function populateProfileData() {
    console.log('DEBUG PERFIL JS: populateProfileData() chamado.');
    const userNameElement = document.getElementById('profile-user-name'); // Nome exibido no topo do perfil
    const userEmailElement = document.getElementById('profile-user-email');
    const userRoleElement = document.getElementById('profile-user-role');
    const userLastLoginElement = document.getElementById('profile-user-last-login');
    const userAvatarImg = document.getElementById('fotoPreview'); // ID da imagem de preview da foto
    const nameInput = document.getElementById('nome'); // ID do input de nome no formulário

    const userData = document.body.dataset; // Acessa os data-attributes do body
    console.log('DEBUG PERFIL JS: Dados lidos do Body Dataset em populateProfileData:', userData);

    if (userNameElement) userNameElement.textContent = userData.userName || 'Nome Usuário';
    if (userEmailElement) userEmailElement.textContent = userData.userEmail || 'email@example.com';
    if (userRoleElement) userRoleElement.textContent = userData.userRole || 'Usuário';
    if (userLastLoginElement) userLastLoginElement.textContent = userData.userLastLogin || 'N/A';
    
    // Atualiza a foto do perfil
    if (userAvatarImg) {
        // Usa o caminho da foto do data-attribute do body, se disponível, caso contrário, usa o default
        const photoPath = userData.userPhoto ? `${window.URLADM}assets/images/users/${userData.userPhoto}` : `${window.URLADM}assets/images/users/usuario.png`;
        console.log('DEBUG PERFIL JS: Caminho da foto para avatarImg.src:', photoPath);
        userAvatarImg.src = photoPath + '?t=' + new Date().getTime(); // Adiciona timestamp para forçar recarga
    }

    // Preenche o campo de input do nome
    if (nameInput) {
        nameInput.value = userData.userName || '';
    }
}

/**
 * Configura o preview da foto (mantido como está, pois não lida com submissão de formulário).
 */
function setupFotoPreview() {
    const fotoInput = document.getElementById('fotoInput');
    const fotoPreview = document.getElementById('fotoPreview');
    const fileNameDisplay = document.getElementById('fileName');

    if (fotoInput && fotoPreview && fileNameDisplay) {
        fotoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const MAX_FILE_SIZE_BYTES = 4 * 1024 * 1024; // 4MB
                const MAX_FILE_SIZE_MB = MAX_FILE_SIZE_BYTES / (1024 * 1024);

                if (file.size > MAX_FILE_SIZE_BYTES) {
                    window.showFeedbackModal('error', `A imagem selecionada excede o limite de ${MAX_FILE_SIZE_MB}MB.`, 'Erro de Arquivo');
                    e.target.value = ''; // Limpa o input
                    fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
                    return;
                }

                fileNameDisplay.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function (event) {
                    fotoPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = 'Nenhum arquivo selecionado';
            }
        });
        console.log('DEBUG PERFIL JS: Listener de "change" para fotoInput configurado.');
    } else {
        console.warn('DEBUG PERFIL JS: Elementos para preview de foto (fotoInput, fotoPreview, fileName) não encontrados.');
    }
}

/**
 * Configura a delegação de eventos para os formulários de perfil.
 * Isso garante que os listeners funcionem mesmo com conteúdo dinâmico.
 */
function setupPerfilFormDelegation() {
    console.log('DEBUG PERFIL JS: setupPerfilFormDelegation() sendo executado.');
    // Anexa um único listener de 'submit' ao corpo do documento
    // Este listener irá "escutar" submissões de formulários em qualquer lugar do DOM.
    document.body.addEventListener('submit', async function(event) {
        const form = event.target; // O formulário que disparou o evento

        // Verifica se o formulário é um dos nossos formulários de perfil
        if (form.id === 'formFoto' || form.id === 'formNome' || form.id === 'formSenha') {
            console.log(`DEBUG PERFIL JS: Submissão de formulário detectada via delegação: #${form.id}`);
            event.preventDefault(); // Previne o comportamento padrão do formulário
            console.log('DEBUG PERFIL JS: event.preventDefault() CHAMADO para formulário de perfil.');

            switch (form.id) {
                case 'formFoto':
                    await handleUpdatePhoto(event);
                    break;
                case 'formNome':
                    await handleUpdateName(event);
                    break;
                case 'formSenha':
                    await handleUpdatePassword(event);
                    break;
            }
        }
    });
    console.log('DEBUG PERFIL JS: Delegação de eventos de submissão de formulário configurada no document.body.');
}


/**
 * Lida com a atualização da foto de perfil via AJAX.
 */
async function handleUpdatePhoto(event) {
    console.log('DEBUG PERFIL JS: handleUpdatePhoto() INICIADO.');
    // event.preventDefault() já foi chamado na delegação

    const form = event.target; // O formulário é o target do evento
    const photoInput = document.getElementById('fotoInput'); // ID do input de foto
    const avatarImg = document.getElementById('fotoPreview'); // ID da imagem de preview da foto

    if (!photoInput || photoInput.files.length === 0) {
        window.showFeedbackModal('warning', 'Por favor, selecione uma nova foto para fazer upload.', 'Atenção!');
        return;
    }

    const formData = new FormData(form); // Cria FormData a partir do formulário

    window.showLoadingModal('Atualizando foto...');

    try {
        const response = await fetch(form.action, { // Usa form.action
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        await window.hideLoadingModal(); // Usa await para garantir que o modal feche após o tempo mínimo
        console.log('DEBUG PERFIL JS: Resposta da atualização de foto:', result);

        if (result.success) {
            window.showFeedbackModal('success', result.message, 'Sucesso!');
            if (result.new_photo_url && avatarImg) {
                const newPhotoFileName = result.new_photo_url.split('/').pop().split('?')[0];
                avatarImg.src = result.new_photo_url + '?t=' + new Date().getTime(); // Adiciona timestamp para forçar recarga
                console.log('DEBUG PERFIL JS: Atualizando avatarImg.src para:', avatarImg.src);
                // Atualiza o data-attribute no body para refletir a nova foto na sessão
                document.body.dataset.userPhoto = newPhotoFileName;
                console.log('DEBUG PERFIL JS: Body data-user-photo atualizado para:', document.body.dataset.userPhoto);
            }
            // Chamada para a nova função que atualiza todos os displays de foto
            if (typeof window.updateProfilePhotoDisplays === 'function') {
                window.updateProfilePhotoDisplays(avatarImg.src); // Passa a URL completa com timestamp
            } else {
                console.warn('AVISO PERFIL JS: window.updateProfilePhotoDisplays não está definida. A foto na sidebar/topbar pode não ser atualizada.');
            }

            // Limpa o input de arquivo para permitir novo upload do mesmo arquivo
            photoInput.value = '';
            const fileNameDisplay = document.getElementById('fileName');
            if (fileNameDisplay) fileNameDisplay.textContent = 'Nenhum arquivo selecionado';

        } else {
            window.showFeedbackModal('error', result.message || 'Erro desconhecido ao atualizar foto.', 'Erro na Foto de Perfil');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX de atualização de foto:', error);
        await window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão ou resposta inválida. Por favor, tente novamente.', 'Erro de Rede');
    }
}

/**
 * Lida com a atualização do nome via AJAX.
 */
async function handleUpdateName(event) {
    debugger; 

    console.log('DEBUG PERFIL JS: handleUpdateName() INICIADO.');

    const form = event.target; 
    const nameInput = document.getElementById('nome'); 
    const profileUserNameDisplay = document.getElementById('profile-user-name'); 

    if (!nameInput || nameInput.value.trim() === '') {
        window.showFeedbackModal('warning', 'O nome não pode ser vazio.', 'Atenção!');
        await window.hideLoadingModal(); 
        return;
    }

    const newName = nameInput.value.trim();
    const formData = new FormData(form); 

    console.log('DEBUG PERFIL JS: Conteúdo do FormData para atualização de nome:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    window.showLoadingModal('Atualizando nome...');

    try {
        const response = await fetch(form.action, { 
            method: 'POST',
            body: formData, 
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        await window.hideLoadingModal(); 
        console.log('DEBUG PERFIL JS: Resposta da atualização de nome:', result);

        if (result.success) {
            window.showFeedbackModal('success', result.message, 'Sucesso!');
            if (result.changed) {
                if (profileUserNameDisplay) {
                    profileUserNameDisplay.textContent = newName; 
                    console.log('DEBUG PERFIL JS: Atualizando profileUserNameDisplay.textContent para:', newName);
                }
                if (nameInput) {
                    nameInput.value = newName;
                }
                document.body.dataset.userName = newName;
                console.log('DEBUG PERFIL JS: Body data-user-name atualizado para:', document.body.dataset.userName);
                
                // Chamada para a nova função que atualiza todos os displays de nome
                if (typeof window.updateUserNameDisplays === 'function') {
                    window.updateUserNameDisplays(newName);
                } else {
                    console.warn('AVISO PERFIL JS: window.updateUserNameDisplays não está definida. A sidebar/topbar pode não ser atualizada.');
                }
            }
        } else {
            window.showFeedbackModal('error', result.message || 'Erro desconhecido ao atualizar nome.', 'Erro no Nome de Perfil');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX de atualização de nome:', error);
        await window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão ou resposta inválida. Por favor, tente novamente.', 'Erro de Rede');
    }
}

/**
 * Lida com a atualização da senha via AJAX.
 */
async function handleUpdatePassword(event) {
    debugger; 
    console.log('DEBUG PERFIL JS: handleUpdatePassword() INICIADO.');
    
    const form = event.target; 
    const currentPasswordInput = document.getElementById('senha_atual'); 
    const newPasswordInput = document.getElementById('nova_senha'); 
    const confirmPasswordInput = document.getElementById('confirma_senha'); 
    
    if (!currentPasswordInput || !newPasswordInput || !confirmPasswordInput) {
        window.showFeedbackModal('error', 'Campos de senha não encontrados.', 'Erro');
        await window.hideLoadingModal();
        return;
    }

    const currentPassword = currentPasswordInput.value;
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (newPassword !== confirmPassword) {
        window.showFeedbackModal('warning', 'As novas senhas não coincidem!', 'Atenção!');
        await window.hideLoadingModal();
        return;
    }

    if (newPassword.length < 6) {
        window.showFeedbackModal('warning', 'A nova senha deve ter pelo menos 6 caracteres!', 'Atenção!');
        await window.hideLoadingModal();
        return;
    }

    const formData = new FormData(form); 

    window.showLoadingModal('Atualizando senha...');

    try {
        const response = await fetch(form.action, { 
            method: 'POST',
            body: formData, 
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        await window.hideLoadingModal(); 
        console.log('DEBUG PERFIL JS: Resposta da atualização de senha:', result);

        if (result.success) {
            window.showFeedbackModal('success', result.message, 'Sucesso!');
            // Limpa os campos de senha após o sucesso
            currentPasswordInput.value = '';
            newPasswordInput.value = '';
            confirmPasswordInput.value = '';
        } else {
            window.showFeedbackModal('error', result.message || 'Erro desconhecido ao atualizar senha.', 'Erro na Senha');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX de atualização de senha:', error);
        await window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão ou resposta inválida. Por favor, tente novamente.', 'Erro de Rede');
    }
}

/**
 * Configura o botão de soft delete da conta.
 */
function setupSoftDeleteAccount() {
    const softDeleteBtn = document.getElementById('soft-delete-account-btn'); 
    console.log('DEBUG PERFIL JS: Tentando encontrar o botão de soft delete (#soft-delete-account-btn):', softDeleteBtn);
    if (softDeleteBtn) {
        softDeleteBtn.addEventListener('click', function() {
            window.showConfirmModal(
                'Desativar Conta',
                'Tem certeza que deseja desativar sua conta? Você poderá reativá-la entrando em contato com o suporte. Esta ação irá desativar também seu anúncio, se houver.',
                async () => {
                    await performSoftDeleteAccount();
                }
            );
        });
        console.log('DEBUG PERFIL JS: Listener de clique para softDeleteBtn configurado.');
    } else {
        console.warn('DEBUG PERFIL JS: Botão #soft-delete-account-btn NÃO ENCONTRADO.');
    }
}

/**
 * Realiza o soft delete da conta via AJAX.
 */
async function performSoftDeleteAccount() {
    console.log('DEBUG PERFIL JS: performSoftDeleteAccount() chamado.');
    window.showLoadingModal('Desativando conta...');

    try {
        const response = await fetch(`${window.URLADM}perfil/softDeleteAccount`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        await window.hideLoadingModal();
        console.log('DEBUG PERFIL JS: Resposta do soft delete:', result);

        if (result.success) {
            window.showFeedbackModal('success', result.message, 'Sucesso!');
            // Redireciona para a página de login após um pequeno atraso
            setTimeout(() => {
                window.location.href = `${window.URLADM}login`;
            }, 2000);
        } else {
            window.showFeedbackModal('error', result.message || 'Erro desconhecido ao desativar conta.', 'Erro');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX de soft delete:', error);
        await window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
    }
}

/**
 * Atualiza o nome do usuário em todos os locais relevantes (sidebar, topbar, etc.).
 * Esta função é definida globalmente para ser acessível de qualquer script.
 * @param {string} newName - O novo nome do usuário.
 */
if (typeof window.updateUserNameDisplays === 'undefined') {
    window.updateUserNameDisplays = function(newName) {
        console.log('DEBUG PERFIL JS: updateUserNameDisplays() chamado com nome:', newName);
        
        // Atualiza o nome na sidebar (se existir)
        const sidebarUserName = document.getElementById('sidebar-user-name');
        if (sidebarUserName) {
            sidebarUserName.textContent = newName;
            console.log('DEBUG PERFIL JS: Nome na sidebar atualizado.');
        } else {
            console.warn('AVISO PERFIL JS: Elemento #sidebar-user-name não encontrado.');
        }

        // Atualiza o nome na topbar (se existir)
        const topbarUserName = document.getElementById('topbar-user-name');
        if (topbarUserName) {
            topbarUserName.textContent = newName;
            console.log('DEBUG PERFIL JS: Nome na topbar atualizado.');
        } else {
            console.warn('AVISO PERFIL JS: Elemento #topbar-user-name não encontrado.');
        }
    };
}

/**
 * Atualiza a foto do usuário em todos os locais relevantes (sidebar, topbar, etc.).
 * Esta função é definida globalmente para ser acessível de qualquer script.
 * @param {string} newPhotoUrl - A nova URL completa da foto do usuário.
 */
if (typeof window.updateProfilePhotoDisplays === 'undefined') {
    window.updateProfilePhotoDisplays = function(newPhotoUrl) {
        console.log('DEBUG PERFIL JS: updateProfilePhotoDisplays() chamado com URL:', newPhotoUrl);
        
        // Atualiza a foto na sidebar (se existir)
        const sidebarPhoto = document.getElementById('sidebar-user-photo');
        if (sidebarPhoto) {
            sidebarPhoto.src = newPhotoUrl;
            console.log('DEBUG PERFIL JS: Foto na sidebar atualizada.');
        } else {
            console.warn('AVISO PERFIL JS: Elemento #sidebar-user-photo não encontrado.');
        }

        // Atualiza a foto na topbar (se existir)
        const topbarPhoto = document.getElementById('topbar-user-photo');
        if (topbarPhoto) {
            topbarPhoto.src = newPhotoUrl;
            console.log('DEBUG PERFIL JS: Foto na topbar atualizada.');
        } else {
            console.warn('AVISO PERFIL JS: Elemento #topbar-user-photo não encontrado.');
        }
    };
}
