// anuncio.js (Versão 51 - com Excluir Anúncio e Correção de Escopo)

console.info("anuncio.js (Versão 51 - com Excluir Anúncio e Correção de Escopo) carregado.");

// Assegura que URLADM e projectBaseURL (base do projeto) estejam disponíveis globalmente
// Elas devem ser definidas em main.php ou em um script global carregado antes.
if (typeof window.URLADM === 'undefined') {
    console.warn('AVISO JS: window.URLADM não está definida. Certifique-se de que main.php a define.');
    window.URLADM = 'http://localhost/nixcom/adms/'; // Fallback
} else {
    console.log('INFO JS: URLADM (global, vinda de main.php) em anuncio.js:', window.URLADM);
}
if (typeof window.projectBaseURL === 'undefined') {
    console.warn('AVISO JS: window.projectBaseURL não está definida. Certifique-se de que main.php a define.');
    window.projectBaseURL = 'http://localhost/nixcom/'; // Fallback
} else {
    console.log('INFO JS: projectBaseURL (global, URL base do projeto) em anuncio.js:', window.projectBaseURL);
}

// =================================================================================================
// FUNÇÕES AUXILIARES GERAIS (GLOBALMENTE DISPONÍVEIS, USADAS POR MÚLTIPLAS FUNÇÕES)
// =================================================================================================

/**
 * Helper para habilitar/desabilitar botões.
 * Movido para o escopo global para ser acessível por window.updateAnuncioSidebarLinks e setupAdminActionButtons.
 * @param {HTMLElement} button O elemento do botão.
 * @param {boolean} enable True para habilitar, false para desabilitar.
 */
window.toggleButtonState = (button, enable) => {
    if (button) {
        button.classList.toggle('disabled', !enable);
        button.style.opacity = enable ? '1' : '0.5';
        button.style.pointerEvents = enable ? 'auto' : 'none';
        button.style.cursor = enable ? 'pointer' : 'not-allowed'; // Adicionado para controle de cursor
    }
};


// =================================================================================================
// FUNÇÕES DE ATUALIZAÇÃO DA SIDEBAR (GLOBALMENTE DISPONÍVEIS)
// Estas funções são definidas no escopo global para serem acessíveis imediatamente após o carregamento do script.
// =================================================================================================

/**
 * Atualiza o estado dos links da sidebar relacionados a anúncios (Criar, Editar, Visualizar, Excluir, Pausar/Ativar).
 * Baseia-se nos atributos `data-has-anuncio`, `data-anuncio-status` e `data-user-role` do `body`.
 * Esta função é chamada na carga inicial da página e após operações SPA que alteram o estado do anúncio.
 */
window.updateAnuncioSidebarLinks = async function() {
    console.log('DEBUG JS: updateAnuncioSidebarLinks - Iniciado.');
    const bodyDataset = document.body.dataset;
    const hasAnuncio = bodyDataset.hasAnuncio === 'true';
    let anuncioStatus = bodyDataset.anuncioStatus || 'not_found'; // Default para 'not_found'
    const userRole = bodyDataset.userRole || 'normal'; // Pega o papel do usuário
    const userId = bodyDataset.userId; // Pega o ID do usuário logado

    console.log('DEBUG JS: updateAnuncioSidebarLinks - Body Dataset:', bodyDataset);

    // Se o status for 'error_fetching', tenta buscar novamente
    if (anuncioStatus === 'error_fetching' || (anuncioStatus === 'not_found' && hasAnuncio)) {
        console.log('DEBUG JS: updateAnuncioSidebarLinks - Tentando buscar status do anúncio novamente.');
        const fetchedStatus = await window.fetchAndApplyAnuncioStatus();
        if (fetchedStatus) {
            anuncioStatus = fetchedStatus;
            document.body.dataset.anuncioStatus = anuncioStatus; // Atualiza o dataset do body
        } else {
            console.warn('AVISO JS: Não foi possível buscar o status do anúncio. Mantendo o estado atual.');
        }
    }

    const navCriarAnuncioLink = document.getElementById('navCriarAnuncio'); // O link <a>
    const navEditarAnuncio = document.getElementById('navEditarAnuncio');
    const navVisualizarAnuncio = document.getElementById('navVisualizarAnuncio');
    const navExcluirAnuncio = document.getElementById('navExcluirAnuncio');
    const navPausarAnuncio = document.getElementById('navPausarAnuncio'); // Botão de Pausar/Ativar
    const navFinanceiro = document.getElementById('navFinanceiro'); // Assumindo que você tem um link Financeiro

    // Lógica para o link "Criar Anúncio"
    if (navCriarAnuncioLink) {
        const parentLi = navCriarAnuncioLink.closest('li.nav-item'); // Encontra o <li> pai
        // NOVO: Oculta o link "Criar Anúncio" se o usuário for admin
        const shouldHideForAdmin = (userRole === 'admin');
        const shouldHideForNormalUserWithAnuncio = (userRole === 'normal' && hasAnuncio);
        const shouldHide = shouldHideForAdmin || shouldHideForNormalUserWithAnuncio;
        
        console.log(`DEBUG JS: navCriarAnuncio - userRole: ${userRole}, hasAnuncio: ${hasAnuncio}, shouldHide: ${shouldHide}`);

        if (parentLi) {
            parentLi.style.display = shouldHide ? 'none' : 'block'; // Oculta/mostra o item da lista completo
            console.log('DEBUG JS: navCriarAnuncio (parent <li>) display (via JS):', shouldHide ? 'none' : 'block');
        } else {
            // Fallback: se o <li> pai não for encontrado, oculta/mostra o próprio link
            navCriarAnuncioLink.style.display = shouldHide ? 'none' : 'block';
            console.log('DEBUG JS: navCriarAnuncio (link) display (via JS):', shouldHide ? 'none' : 'block');
        }
        // Remove estilos antigos de opacidade/pointerEvents que não são mais necessários
        navCriarAnuncioLink.style.opacity = ''; 
        navCriarAnuncioLink.style.pointerEvents = '';
    }

    // Lógica para "Editar Anúncio", "Visualizar Anúncio", "Excluir Anúncio"
    const enableExistingAnuncioLinksForNormalUser = hasAnuncio;

    [navEditarAnuncio, navVisualizarAnuncio, navExcluirAnuncio].forEach(link => {
        if (link) {
            // NOVO: Oculta esses links se o usuário for admin
            const shouldHideForAdmin = (userRole === 'admin');
            const isDisabledForNormalUser = (userRole === 'normal' && !enableExistingAnuncioLinksForNormalUser);
            
            // Se for admin, oculta. Caso contrário, aplica a lógica de desabilitar para usuário normal.
            const shouldHide = shouldHideForAdmin;
            const isDisabled = isDisabledForNormalUser && !shouldHideForAdmin; // Aplica disabled apenas se não estiver oculto pelo admin

            const parentLi = link.closest('li.nav-item'); // Encontra o <li> pai

            if (parentLi) {
                parentLi.style.display = shouldHide ? 'none' : 'block';
                console.log(`DEBUG JS: ${link.id} (parent <li>) display (via JS):`, shouldHide ? 'none' : 'block');
            } else {
                link.style.display = shouldHide ? 'none' : 'block';
                console.log(`DEBUG JS: ${link.id} (link) display (via JS):`, shouldHide ? 'none' : 'block');
            }

            // Usa a função global toggleButtonState
            window.toggleButtonState(link, !isDisabled);
            console.log(`DEBUG JS: ${link.id} disabled (via JS):`, isDisabled);
        }
    });

    // Lógica específica para o botão "Pausar/Ativar Anúncio" (texto e cor)
    if (navPausarAnuncio) {
        let canInteract = hasAnuncio && (anuncioStatus === 'active' || anuncioStatus === 'inactive');
        let iconClass = 'fas fa-pause-circle';
        let buttonText = 'Pausar Anúncio';
        // Removido: let buttonColorClass = 'btn-info';

        if (userRole === 'admin') {
            iconClass = 'fas fa-tasks';
            buttonText = 'Gerenciar Anúncios';
            canInteract = true; // Admin sempre pode acessar o gerenciamento
            navPausarAnuncio.href = `${URLADM}dashboard`;
            navPausarAnuncio.dataset.spa = 'true';
        } else { // Usuário normal
            switch (anuncioStatus) {
                case 'active':
                    iconClass = 'fas fa-pause-circle';
                    buttonText = 'Pausar Anúncio';
                    break;
                case 'inactive':
                    iconClass = 'fas fa-play-circle';
                    buttonText = 'Ativar Anúncio';
                    break;
                case 'pending':
                    iconClass = 'fas fa-clock';
                    buttonText = 'Anúncio Pendente';
                    canInteract = false; // Garante que não é clicável
                    break;
                case 'rejected':
                    iconClass = 'fas fa-times-circle';
                    buttonText = 'Anúncio Rejeitado';
                    canInteract = false; // Garante que não é clicável
                    break;
                case 'not_found':
                case 'error_fetching':
                default:
                    iconClass = 'fas fa-exclamation-circle';
                    buttonText = 'Status Desconhecido';
                    canInteract = false;
                    break;
            }
            navPausarAnuncio.href = '#'; // Mantém o href para # para usuários normais
            navPausarAnuncio.dataset.spa = 'false'; // Garante que não é SPA para o toggle
        }

        const iconElement = navPausarAnuncio.querySelector('i');
        if (iconElement) {
            iconElement.className = iconClass + ' me-2';
        }
        let textNode = Array.from(navPausarAnuncio.childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.nodeValue.trim() !== '');
        if (textNode) {
            textNode.nodeValue = buttonText;
        } else {
            navPausarAnuncio.innerHTML = `<i class="${iconClass} me-2"></i>${buttonText}`;
        }

        // Remove todas as classes de cor Bootstrap para garantir estilo neutro
        navPausarAnuncio.classList.remove('btn-primary', 'btn-success', 'btn-warning', 'btn-danger', 'btn-info', 'btn-secondary');
        
        // Usa a função global toggleButtonState
        window.toggleButtonState(navPausarAnuncio, canInteract);
        
        console.log(`DEBUG JS: navPausarAnuncio status: ${anuncioStatus} canInteract: ${canInteract}`);

        // NOVO: Adicionar event listener para o botão Pausar/Ativar Anúncio (apenas para usuários normais)
        if (userRole === 'normal') {
            // Remove listener antigo para evitar duplicação em navegações SPA
            if (navPausarAnuncio._clickHandler) {
                navPausarAnuncio.removeEventListener('click', navPausarAnuncio._clickHandler);
            }

            if (canInteract) { // Apenas adiciona o listener se for clicável
                const toggleHandler = function(e) {
                    e.preventDefault(); // Impede o comportamento padrão do link
                    const userId = document.body.dataset.userId; // Pega o ID do usuário logado
                    if (!userId) {
                        console.error('ERRO JS: User ID não encontrado para toggleAnuncioStatus.');
                        window.showFeedbackModal('error', 'Não foi possível identificar o usuário para esta ação.', 'Erro!');
                        return;
                    }

                    let confirmTitle = 'Confirmar Ação';
                    let confirmMessage = '';
                    let actionType = '';

                    if (anuncioStatus === 'active') {
                        confirmMessage = 'Tem certeza que deseja PAUSAR seu anúncio? Ele não ficará visível publicamente.';
                        actionType = 'deactivate'; // Ação para o backend (PAUSAR)
                    } else if (anuncioStatus === 'inactive') {
                        confirmMessage = 'Tem certeza que deseja ATIVAR seu anúncio? Ele voltará a ficar visível publicamente.';
                        actionType = 'activate'; // Ação para o backend (ATIVAR)
                    }

                    if (actionType) {
                        window.showConfirmModal(confirmTitle, confirmMessage, async () => {
                            window.showLoadingModal('Processando...');
                            try {
                                const formData = new FormData();
                                formData.append('user_id', userId);
                                formData.append('action', actionType);

                                // ALTERADO AQUI: Chamando o método PHP correto
                                const response = await fetch(`${window.URLADM}anuncio/pausarAnuncio`, { 
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: formData
                                });

                                const responseText = await response.text();
                                console.log('DEBUG JS: Resposta bruta do pausarAnuncio:', responseText); // Log alterado
                                
                                let result;
                                try {
                                    result = JSON.parse(responseText);
                                } catch (jsonError) {
                                    console.error('ERRO JS: Erro ao parsear JSON da resposta:', jsonError, 'Resposta:', responseText);
                                    throw new Error('Resposta inválida do servidor. Verifique os logs do PHP.');
                                }
                                
                                await window.hideLoadingModal();

                                if (result.success) {
                                    window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
                                    document.body.dataset.anuncioStatus = result.new_anuncio_status || anuncioStatus;
                                    document.body.dataset.hasAnuncio = result.has_anuncio !== undefined ? (result.has_anuncio ? 'true' : 'false') : document.body.dataset.hasAnuncio;
                                    window.updateAnuncioSidebarLinks();
                                } else {
                                    window.showFeedbackModal('error', result.message || 'Erro ao realizar a ação.', 'Erro!');
                                }
                            } catch (error) {
                                console.error('ERRO JS: Erro na requisição AJAX de pausarAnuncio:', error); // Log alterado
                                await window.hideLoadingModal();
                                window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
                            }
                        });
                    }
                };
                navPausarAnuncio.addEventListener('click', toggleHandler);
                navPausarAnuncio._clickHandler = toggleHandler; // Armazena a referência
            }
        }
    }

    // NOVO: Lógica para o botão "Excluir Anúncio" (apenas para usuários normais)
    if (navExcluirAnuncio && userRole === 'normal') {
        // Remove listener antigo para evitar duplicação em navegações SPA
        if (navExcluirAnuncio._clickHandler) {
            navExcluirAnuncio.removeEventListener('click', navExcluirAnuncio._clickHandler);
        }

        const canDelete = hasAnuncio; // Só pode excluir se tiver um anúncio
        window.toggleButtonState(navExcluirAnuncio, canDelete); // Usa a função auxiliar para habilitar/desabilitar

        if (canDelete) {
            const deleteHandler = function(e) {
                e.preventDefault(); // Impede o comportamento padrão do link
                const userId = document.body.dataset.userId; // Pega o ID do usuário logado
                if (!userId) {
                    console.error('ERRO JS: User ID não encontrado para deleteMyAnuncio.');
                    window.showFeedbackModal('error', 'Não foi possível identificar o usuário para esta ação.', 'Erro!');
                    return;
                }

                window.showConfirmModal('Excluir Anúncio', 'Tem certeza que deseja EXCLUIR seu anúncio? Esta ação é irreversível e removerá todas as suas mídias e dados do anúncio.', async () => {
                    window.showLoadingModal('Excluindo Anúncio...');
                    try {
                        const formData = new FormData();
                        formData.append('user_id', userId); // Envia o user_id para o backend

                        const response = await fetch(`${window.URLADM}anuncio/deleteMyAnuncio`, { 
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });

                        const result = await response.json();
                        await window.hideLoadingModal();

                        if (result.success) {
                            window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
                            document.body.dataset.hasAnuncio = result.has_anuncio ? 'true' : 'false'; // Deve ser 'false'
                            document.body.dataset.anuncioStatus = result.anuncio_status || 'not_found'; // Deve ser 'not_found'
                            document.body.dataset.anuncioId = ''; // Limpa o ID do anúncio
                            window.updateAnuncioSidebarLinks(); // Atualiza a sidebar
                            
                            // Redireciona para a página de criação de anúncio após a exclusão
                            if (result.redirect) {
                                setTimeout(() => {
                                    window.loadContent(result.redirect, 'anuncio/index'); // Redireciona via SPA
                                }, 1500);
                            }
                        } else {
                            window.showFeedbackModal('error', result.message || 'Erro ao excluir o anúncio.', 'Erro!');
                        }
                    } catch (error) {
                        console.error('ERRO JS: Erro na requisição AJAX de deleteMyAnuncio:', error);
                        await window.hideLoadingModal();
                        window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
                    }
                });
            };
            navExcluirAnuncio.addEventListener('click', deleteHandler);
            navExcluirAnuncio._clickHandler = deleteHandler; // Armazena a referência
        }
    }


    if (navFinanceiro) {
        const isDisabled = (userRole === 'admin');
        window.toggleButtonState(navFinanceiro, !isDisabled); // Usa a função global toggleButtonState
        console.log('DEBUG JS: navFinanceiro disabled (via JS):', isDisabled);
    }

    console.log(`INFO JS: Sidebar links atualizados. Has Anuncio: ${hasAnuncio} Anuncio Status: ${anuncioStatus} User Role: ${userRole}`);
};


/**
 * Busca o status do anúncio do servidor e atualiza o dataset do body.
 * @returns {Promise<string|null>} O status do anúncio ou null em caso de erro.
 */
window.fetchAndApplyAnuncioStatus = async function() {
    const userId = document.body.dataset.userId;
    if (!userId) {
        console.error('ERRO JS: fetchAndApplyAnuncioStatus - User ID não encontrado no dataset do body.');
        return null;
    }

    console.log(`DEBUG JS: fetchAndApplyAnuncioStatus - Buscando status atual do anúncio ID: ${userId}. Requisição com ajax_data_only=true.`);
    try {
        const response = await fetch(`${window.URLADM}anuncio/visualizarAnuncio?ajax_data_only=true`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('ERRO JS: fetchAndApplyAnuncioStatus - Resposta de rede não OK:', response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success && data.anuncio) {
            document.body.dataset.anuncioStatus = data.anuncio.status;
            document.body.dataset.hasAnuncio = 'true';
            document.body.dataset.anuncioId = data.anuncio.id;
            console.log('INFO JS: fetchAndApplyAnuncioStatus - Status do anúncio atualizado:', data.anuncio.status);
            return data.anuncio.status;
        } else {
            document.body.dataset.anuncioStatus = 'not_found';
            document.body.dataset.hasAnuncio = 'false';
            document.body.dataset.anuncioId = '';
            console.warn('AVISO JS: fetchAndApplyAnuncioStatus - Anúncio não encontrado ou dados incompletos:', data.message);
            return 'not_found';
        }
    } catch (error) {
        console.error('ERRO JS: fetchAndApplyAnuncioStatus - Erro ao buscar status do anúncio:', error);
        document.body.dataset.anuncioStatus = 'error_fetching';
        document.body.dataset.hasAnuncio = 'false';
        document.body.dataset.anuncioId = '';
        return null;
    }
};

// =================================================================================================
// FUNÇÕES DE INICIALIZAÇÃO DE PÁGINAS ESPECÍFICAS (GLOBALMENTE DISPONÍVEIS)
// Estas funções são definidas no escopo global para serem acessíveis imediatamente após o carregamento do script.
// =================================================================================================

/**
 * Inicializa a página de perfil.
 * Esta função é chamada pelo dashboard_custom.js quando a rota 'perfil' é detectada.
 */
window.initializePerfilPage = function() {
    console.log('INFO JS: initializePerfilPage - Iniciando inicialização da página de perfil.');
    const perfilForm = document.getElementById('formPerfil');
    if (perfilForm) {
        console.log('DEBUG JS: Formulário de perfil encontrado. Configurando validação e máscaras.');
        setupFormValidation(perfilForm);
        setupInputMasks();
    } else {
        console.warn('AVISO JS: Formulário de perfil (ID "formPerfil") não encontrado na página.');
    }

    if (typeof window.setupAutoDismissAlerts === 'function') {
        window.setupAutoDismissAlerts();
    }
    console.log('INFO JS: initializePerfilPage - Finalizado.');
};

/**
 * Inicializa a página de formulário de anúncio (criação/edição).
 * Esta função é chamada pelo dashboard_custom.js quando a rota 'anuncio' ou 'anuncio/editarAnuncio' é detectada.
 * @param {string} fullUrl - A URL completa da página.
 * @param {object|null} [initialData=null] - Dados JSON iniciais para a página (se for uma resposta JSON).
 */
window.initializeAnuncioFormPage = async function(fullUrl, initialData = null) {
    console.info('INFO JS: initializeAnuncioFormPage - Iniciando inicialização da página de formulário de anúncio.');

    const formAnuncio = document.getElementById('formCriarAnuncio');
    if (!formAnuncio) {
        console.warn('AVISO JS: Formulário de anúncio (ID "formCriarAnuncio") não encontrado. Ignorando inicialização do formulário.');
        return;
    }

    try { // Adicionado bloco try-catch para capturar erros de inicialização
        const anuncioData = JSON.parse(formAnuncio.dataset.anuncioData || '{}');
        const formMode = formAnuncio.dataset.formMode;
        const userPlanType = formAnuncio.dataset.userPlanType;
        const userRole = document.body.dataset.userRole || 'normal'; // Obter o papel do usuário

        console.log('DEBUG JS: initializeAnuncioFormPage - Modo do Formulário: ', formMode);
        console.log('DEBUG JS: initializeAnuncioFormPage - Tipo de Plano do Usuário: ', userPlanType);
        console.log('DEBUG JS: initializeAnuncioFormPage - Papel do Usuário: ', userRole); // NOVO LOG
        console.log('DEBUG JS: initializeAnuncioFormPage - Dados do Anúncio (parcial): ', Object.keys(anuncioData).slice(0, 5).map(key => `${key}: ${anuncioData[key]}`));
        console.log('DEBUG JS: initializeAnuncioFormPage - Full anuncioData:', anuncioData);


        const cardHeader = document.querySelector('.card-header');
        const formTitleElement = document.getElementById('formAnuncioTitle');
        const btnSubmitAnuncio = document.getElementById('btnSubmitAnuncio');

        if (cardHeader && formTitleElement && btnSubmitAnuncio) {
            // Remove todas as classes de cor para evitar conflitos
            cardHeader.classList.remove('bg-warning', 'text-dark', 'bg-info');
            btnSubmitAnuncio.classList.remove('btn-primary', 'btn-warning', 'btn-info');

            if (formMode === 'edit') {
                formTitleElement.innerHTML = '<i class="fas fa-edit me-2"></i>EDITAR ANÚNCIO';
                btnSubmitAnuncio.innerHTML = '<i class="fas fa-save me-2"></i>ATUALIZAR ANÚNCIO';
                cardHeader.classList.add('bg-warning', 'text-dark');
                btnSubmitAnuncio.classList.add('btn-warning');
            } else { // formMode === 'create'
                formTitleElement.innerHTML = '<i class="fas fa-plus-circle me-2"></i>CRIAR NOVO ANÚNCIO';
                btnSubmitAnuncio.innerHTML = '<i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO';
                cardHeader.classList.add('bg-primary', 'text-white');
                btnSubmitAnuncio.classList.add('btn-primary');
            }
            console.log('DEBUG JS: initializeAnuncioFormPage - Cores do cabeçalho e botão aplicadas dinamicamente.');
        } else {
            console.warn('AVISO JS: Elementos de cabeçalho, título ou botão do formulário de anúncio não encontrados.');
        }

        setupFormValidation(formAnuncio);
        console.log('DEBUG JS: initializeAnuncioFormPage - setupFormValidation concluído.');

        setupInputMasks();
        console.log('DEBUG JS: initializeAnuncioFormPage - setupInputMasks concluído.');

        await loadAndPopulateLocations(anuncioData);
        console.log('DEBUG JS: initializeAnuncioFormPage - loadAndPopulateLocations concluído.');

        setupCheckboxValidation();
        console.log('DEBUG JS: initializeAnuncioFormPage - setupCheckboxValidation concluído.');

        initializeFormFields(formAnuncio, anuncioData, formMode, userPlanType);
        console.log('DEBUG JS: initializeAnuncioFormPage - initializeFormFields concluído.');

        setupFileUploadHandlers(formAnuncio, anuncioData, formMode, userPlanType);
        console.log('DEBUG JS: initializeAnuncioFormPage - setupFileUploadHandlers concluído.');

        // Aplica as restrições de plano APÓS todos os campos e mídias serem inicializados
        applyPlanRestrictions(userPlanType);
        console.log('DEBUG JS: initializeAnuncioFormPage - applyPlanRestrictions concluído.');

        // Configura os botões de ação do administrador, se aplicável
        if (userRole === 'admin' && formMode === 'edit') {
            // PASSANDO O STATUS DO ANÚNCIO E O USER_ID DO ANUNCIANTE DIRETAMENTE
            setupAdminActionButtons(anuncioData.id, anuncioData.user_id, anuncioData.status); 
            console.log('DEBUG JS: initializeAnuncioFormPage - setupAdminActionButtons concluído.');
        }

        if (typeof window.setupAutoDismissAlerts === 'function') {
            window.setupAutoDismissAlerts();
            console.log('DEBUG JS: initializeAnuncioFormPage - setupAutoDismissAlerts concluído.');
        }
        console.log('INFO JS: initializeAnuncioFormPage - Finalizado.');
    } catch (error) {
        console.error('ERRO JS: initializeAnuncioFormPage - Erro durante a inicialização:', error);
        window.showFeedbackModal('error', `Erro ao inicializar a página: anuncio/editarAnuncio. Detalhes: ${error.message}`, 'Erro de Inicialização');
    }
};

/**
 * Inicializa a página de visualização de anúncio.
 * Esta função é chamada pelo dashboard_custom.js quando a rota 'anuncio/visualizar' é detectada.
 * @param {string|null} fullUrl - A URL completa da página.
 * @param {object|null} [initialData=null] - Dados JSON iniciais para a página (se for uma resposta JSON).
 */
window.initializeVisualizarAnuncioPage = async function(fullUrl, initialData = null) {
    console.log('INFO JS: initializeVisualizarAnuncioPage - Iniciando inicialização da página de visualização.');
    
    let cardElement = document.querySelector('[data-page-type="view"]');
    let currentAnuncioId = cardElement?.dataset.anuncioId || new URLSearchParams(new URL(fullUrl).search).get('id');

    console.log('DEBUG JS: initializeVisualizarAnuncioPage - Card element:', cardElement);
    console.log('DEBUG JS: initializeVisualizarAnuncioPage - currentAnuncioId definido como:', currentAnuncioId);

    if (!cardElement) {
        console.info('INFO JS: initializeVisualizarAnuncioPage - Card com data-page-type="view" não encontrado. Ignorando inicialização da visualização.');
        window.showFeedbackModal('error', 'O elemento principal da página de visualização não foi encontrado. Verifique o HTML.', 'Erro de Configuração');
        return;
    }

    const cardHeader = cardElement.querySelector('.card-header');
    const formTitleElement = cardElement.querySelector('#formAnuncioTitle');
    if (cardHeader && formTitleElement) {
        formTitleElement.innerHTML = '<i class="fas fa-eye me-2"></i>Detalhes do Anúncio';
        cardHeader.classList.remove('bg-warning', 'text-dark');
        cardHeader.classList.add('bg-primary', 'text-white');
        console.log('DEBUG JS: initializeVisualizarAnuncioPage - Elementos de cabeçalho e título encontrados para visualização. Aplicando cores dinâmicas.');
    } else {
        console.warn('AVISO JS: Elementos de cabeçalho ou título da página de visualização não encontrados.');
    }

    if (!currentAnuncioId) {
        console.error('ERRO JS: initializeVisualizarAnuncioPage - ID do anúncio não encontrado ou inválido para visualização.');
        window.showFeedbackModal('error', 'Não foi possível carregar os detalhes do anúncio. ID inválido.', 'Erro de Visualização');
        return;
    }

    if (typeof window.setupAutoDismissAlerts === 'function') {
        window.setupAutoDismissAlerts();
    }

    let anuncioDataToDisplay = initialData?.anuncio;
    if (!anuncioDataToDisplay) {
        try {
            // Incluir o ID do anúncio na URL da requisição AJAX
            const response = await fetch(`${window.URLADM}anuncio/visualizarAnuncio?id=${currentAnuncioId}&ajax_data_only=true`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.success && data.anuncio) {
                anuncioDataToDisplay = data.anuncio;
            } else {
                throw new Error(data.message || 'Dados do anúncio não encontrados.');
            }
        } catch (error) {
            console.error('ERRO JS: initializeVisualizarAnuncioPage - Erro ao buscar dados do anúncio:', error);
            window.showFeedbackModal('error', 'Não foi possível carregar os detalhes do anúncio. Erro de rede ou dados.', 'Erro de Visualização');
            return;
        }
    }

// Lógica para preencher os campos da página de visualização com anuncioDataToDisplay
    if (anuncioDataToDisplay) {
        console.log('DEBUG JS: Dados do anúncio para exibição:', anuncioDataToDisplay);
        try { // NOVO BLOCO TRY-CATCH PARA A LÓGICA DE EXIBIÇÃO
            // Mapeamento de IDs HTML para chaves de dados que o JS PODE preencher diretamente.
            // Campos como 'status', 'created_at', 'updated_at', e preços são deixados de fora
            // pois o PHP já os formata no HTML inicial.
            // O campo 'displayServiceName' TAMBÉM FOI REMOVIDO DAQUI para que o PHP seja o único a defini-lo.
            const fieldMappings = {
                // 'displayServiceName': 'service_name', // REMOVIDO: PHP é o responsável por este label
                'displayPlanType': 'plan_type',
                'displayAge': 'age',
                'displayHeight': 'height_m',
                'displayWeight': 'weight_kg',
                'displayGender': 'gender',
                'displayNationality': 'nationality',
                'displayEthnicity': 'ethnicity',
                'displayEyeColor': 'eye_color',
                'displayPhoneNumber': 'phone_number',
                'displayDescription': 'description',
                'displayVisits': 'visits'
            };

            for (const id in fieldMappings) {
                const element = document.getElementById(id);
                const dataKey = fieldMappings[id];
                if (element) {
                    let value = anuncioDataToDisplay[dataKey];
                    // Formatação específica para campos que o JS ainda pode precisar ajustar
                    if (id === 'displayHeight') {
                        value = value ? `${value} m` : 'Não informado';
                    } else if (id === 'displayWeight') {
                        value = value ? `${value} kg` : 'Não informado';
                    } else if (id === 'displayVisits') {
                        value = value || '0'; // Visitas pode ser 0
                    } else {
                        value = value || 'Não informado'; // Valor padrão para outros campos de texto
                    }
                    element.textContent = value;
                    console.log(`DEBUG JS: Populating ${id} (mapped to ${dataKey}) with: ${value}`);
                } else {
                    // Loga o erro, mas não interrompe a execução para permitir que outros campos sejam preenchidos
                    console.error(`ERRO JS: Elemento HTML com ID "${id}" não encontrado na página. Verifique visualizar_anuncio.php.`);
                }
            }

            // Preenchimento de campos que o PHP já formatou (garante que o valor inicial do PHP seja mantido)
            // Não é necessário buscar o valor do anuncioDataToDisplay para estes, pois o PHP já os inseriu.
            // Apenas verificamos se os elementos existem.
            const formattedFields = ['displayStatus', 'displayCreatedAt', 'displayUpdatedAt', 'displayPrice15min', 'displayPrice30min', 'displayPrice1h'];
            formattedFields.forEach(id => {
                const element = document.getElementById(id);
                if (!element) {
                    console.error(`ERRO JS: Elemento HTML com ID "${id}" (formatado pelo PHP) não encontrado na página. Verifique visualizar_anuncio.php.`);
                }
            });


            // Campo de Localização (tratamento especial)
            const displayLocationElement = document.getElementById('displayLocation');
            if (displayLocationElement) {
                const neighborhood = anuncioDataToDisplay.neighborhood_name || 'N/A';
                const city = anuncioDataToDisplay.city_name || 'N/A';
                const state = anuncioDataToDisplay.state_name || 'N/A'; // Assumindo que state_name já vem formatado do PHP
                displayLocationElement.textContent = `${neighborhood}, ${city} - ${state}`;
            } else {
                console.error(`ERRO JS: Elemento HTML com ID "displayLocation" não encontrado na página. Verifique visualizar_anuncio.php.`);
            }

            // Mídias principais (Capa e Vídeo de Confirmação)
            const coverPhotoImg = document.getElementById('displayCoverPhoto');
            if (coverPhotoImg) {
                if (anuncioDataToDisplay.cover_photo_path) {
                    coverPhotoImg.src = anuncioDataToDisplay.cover_photo_path;
                    coverPhotoImg.style.display = 'block';
                } else {
                    coverPhotoImg.src = 'https://placehold.co/300x200/e0e0e0/555555?text=Sem+Foto+Capa';
                    coverPhotoImg.style.display = 'block';
                }
            } else {
                console.error(`ERRO JS: Elemento HTML com ID "displayCoverPhoto" não encontrado na página. Verifique visualizar_anuncio.php.`);
            }

            const confirmationVideoPlayer = document.getElementById('displayConfirmationVideo');
            if (confirmationVideoPlayer) {
                if (anuncioDataToDisplay.confirmation_video_path) {
                    confirmationVideoPlayer.src = anuncioDataToDisplay.confirmation_video_path;
                    confirmationVideoPlayer.style.display = 'block';
                    confirmationVideoPlayer.load(); // Carrega o vídeo
                } else {
                    confirmationVideoPlayer.src = 'https://placehold.co/300x200/e0e0e0/555555?text=Sem+Vídeo+Confirmação'; // Placeholder para vídeos
                    confirmationVideoPlayer.style.display = 'block';
                }
            } else {
                console.error(`ERRO JS: Elemento HTML com ID "displayConfirmationVideo" não encontrado na página. Verifique visualizar_anuncio.php.`);
            }

            // Listas de checkboxes (aparencia, idiomas, locais_atendimento, formas_pagamento, servicos)
            const displayLists = ['aparencia', 'idiomas', 'locais_atendimento', 'formas_pagamento', 'servicos'];
            displayLists.forEach(listName => {
                const listElement = document.getElementById(`display${listName.charAt(0).toUpperCase() + listName.slice(1)}`);
                if (listElement) {
                    if (anuncioDataToDisplay[listName] && anuncioDataToDisplay[listName].length > 0) {
                        listElement.textContent = anuncioDataToDisplay[listName].join(', ');
                    } else {
                        listElement.textContent = 'N/A';
                    }
                } else {
                    console.error(`ERRO JS: Elemento HTML com ID "display${listName.charAt(0).toUpperCase() + listName.slice(1)}" não encontrado na página. Verifique visualizar_anuncio.php.`);
                }
            });

            // Galeria de Fotos
            const galleryContainer = document.getElementById('displayGalleryPhotos');
            if (galleryContainer) {
                galleryContainer.innerHTML = ''; // Limpa a galeria existente
                if (anuncioDataToDisplay.fotos_galeria && anuncioDataToDisplay.fotos_galeria.length > 0) {
                    anuncioDataToDisplay.fotos_galeria.forEach(photoPath => {
                        const img = document.createElement('img');
                        img.src = photoPath;
                        img.alt = 'Foto da Galeria';
                        img.classList.add('img-fluid', 'rounded', 'shadow-sm', 'mb-2', 'me-2');
                        img.style.maxWidth = '150px'; // Tamanho menor para miniaturas
                        img.style.maxHeight = '150px';
                        img.style.objectFit = 'cover';
                        galleryContainer.appendChild(img);
                    });
                } else {
                    galleryContainer.innerHTML = '<p class="text-muted">Nenhuma foto na galeria.</p>';
                }
            } else {
                console.error(`ERRO JS: Elemento HTML com ID "displayGalleryPhotos" não encontrado na página. Verifique visualizar_anuncio.php.`);
            }

            // Galeria de Vídeos
            const videosContainer = document.getElementById('displayGalleryVideos');
            if (videosContainer) {
                videosContainer.innerHTML = '';
                if (anuncioDataToDisplay.videos && anuncioDataToDisplay.videos.length > 0) {
                    anuncioDataToDisplay.videos.forEach(videoPath => {
                        const video = document.createElement('video');
                        video.src = videoPath;
                        video.controls = true;
                        video.classList.add('img-fluid', 'rounded', 'shadow-sm', 'mb-2', 'me-2');
                        video.style.maxWidth = '200px';
                        video.style.maxHeight = '150px';
                        video.style.objectFit = 'cover';
                        videosContainer.appendChild(video);
                    });
                } else {
                    videosContainer.innerHTML = '<p class="text-muted">Nenhum vídeo na galeria.</p>';
                }
            } else {
                console.error(`ERRO JS: Elemento HTML com ID "displayGalleryVideos" não encontrado na página. Verifique visualizar_anuncio.php.`);
            }

            // Galeria de Áudios
            const audiosContainer = document.getElementById('displayGalleryAudios');
            if (audiosContainer) {
                audiosContainer.innerHTML = '';
                if (anuncioDataToDisplay.audios && anuncioDataToDisplay.audios.length > 0) {
                    anuncioDataToDisplay.audios.forEach(audioPath => {
                        const audio = document.createElement('audio');
                        audio.src = audioPath;
                        audio.controls = true;
                        audio.classList.add('mb-2', 'me-2');
                        audiosContainer.appendChild(audio);
                    });
                } else {
                    audiosContainer.innerHTML = '<p class="text-muted">Nenhum áudio na galeria.</p>';
                }
            } else {
                console.error(`ERRO JS: Elemento HTML com ID "displayGalleryAudios" não encontrado na página. Verifique visualizar_anuncio.php.`);
            }

        } catch (displayError) {
            console.error('ERRO JS: initializeVisualizarAnuncioPage - Erro durante a população dos elementos de exibição:', displayError);
            window.showFeedbackModal('error', `Erro ao exibir os detalhes do anúncio. Detalhes: ${displayError.message}`, 'Erro de Exibição');
            // Não retorna para permitir que o fetchAndApplyAnuncioStatus seja chamado
        }
    }

    await window.fetchAndApplyAnuncioStatus();

    console.log('INFO JS: initializeVisualizarAnuncioPage - Em modo de visualização, ID do anúncio:', currentAnuncioId + '.');
    console.log('INFO JS: initializeVisualizarAnuncioPage - Finalizado.');
};


// =================================================================================================
// FUNÇÕES AUXILIARES GERAIS (NÃO GLOBALIZADAS, CHAMADAS APENAS DENTRO DESTE ARQUIVO)
// =================================================================================================

/**
 * Configura a validação de formulários HTML5.
 * Impede o envio se houver campos inválidos e exibe mensagens de feedback.
 * @param {HTMLFormElement} form O elemento do formulário a ser validado.
 */
function setupFormValidation(form) {
    if (!form) {
        console.warn('AVISO JS: setupFormValidation - Formulário não fornecido.');
        return;
    }
    form.removeEventListener('submit', handleFormSubmit);
    form.addEventListener('submit', handleFormSubmit);
}

async function handleFormSubmit(event) {
    event.preventDefault();
    event.stopPropagation();

    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');

    Array.from(form.querySelectorAll('.is-invalid')).forEach(el => el.classList.remove('is-invalid'));
    Array.from(form.querySelectorAll('.invalid-feedback')).forEach(el => el.textContent = '');
    Array.from(form.querySelectorAll('.text-danger.small')).forEach(el => {
        if (el) {
            el.textContent = '';
            el.style.display = 'none';
        }
    });
    Array.from(form.querySelectorAll('.photo-upload-box.is-invalid-media')).forEach(el => el.classList.remove('is-invalid-media'));
    Array.from(form.querySelectorAll('.form-check-group.is-invalid-group')).forEach(el => el.classList.remove('is-invalid-group'));


    if (!form.checkValidity()) {
        console.warn('AVISO JS: Formulário inválido. Exibindo feedback de validação HTML5.');
        Array.from(form.querySelectorAll(':invalid')).forEach(el => {
            el.classList.add('is-invalid');
            const feedbackElementById = document.getElementById(`${el.id}-feedback`);
            if (feedbackElementById) {
                feedbackElementById.textContent = el.validationMessage;
                feedbackElementById.style.display = 'block';
            } else {
                const feedbackDiv = el.nextElementSibling;
                if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                    feedbackDiv.textContent = el.validationMessage;
                    feedbackDiv.style.display = 'block';
                }
            }
        });
        form.querySelector(':invalid')?.focus();
        window.showFeedbackModal('error', 'Por favor, preencha todos os campos obrigatórios.', 'Erro de Validação!');
        return;
    }

    let isValidCheckboxes = true;
    const checkboxGroups = [
        { name: 'aparencia[]', min: 1, feedbackId: 'aparencia-feedback', message: 'Por favor, selecione pelo menos 1 item de aparência.' },
        { name: 'idiomas[]', min: 1, feedbackId: 'idiomas-feedback', message: 'Por favor, selecione pelo menos 1 idioma.' },
        { name: 'locais_atendimento[]', min: 1, feedbackId: 'locais_atendimento-feedback', message: 'Por favor, selecione pelo menos 1 local de atendimento.' },
        { name: 'formas_pagamento[]', min: 1, feedbackId: 'formas_pagamento-feedback', message: 'Por favor, selecione pelo menos 1 forma de pagamento.' },
        { name: 'servicos[]', min: 2, feedbackId: 'servicos-feedback', message: 'Por favor, selecione pelo menos 2 serviços.' }
    ];

    checkboxGroups.forEach(group => {
        const checkboxes = form.querySelectorAll(`input[name="${group.name}"]`);
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const feedbackElement = document.getElementById(group.feedbackId);
        const groupContainer = document.getElementById(group.feedbackId.replace('-feedback', '-checkboxes'));

        if (checkedCount < group.min) {
            if (feedbackElement) {
                feedbackElement.textContent = group.message;
                feedbackElement.style.display = 'block';
            }
            if (groupContainer) {
                groupContainer.classList.add('is-invalid-group');
            }
            isValidCheckboxes = false;
        } else {
            if (feedbackElement) {
                feedbackElement.textContent = '';
                feedbackElement.style.display = 'none';
            }
            if (groupContainer) {
                groupContainer.classList.remove('is-invalid-group');
            }
        }
    });

    let isValidPrices = true;
    const price15minInput = document.getElementById('price_15min');
    const price30minInput = document.getElementById('price_30min');
    const price1hInput = document.getElementById('price_1h');
    const pricesFeedback = document.getElementById('precos-feedback');

    const rawPrice15minUnmasked = price15minInput && price15minInput.inputmask ? price15minInput.inputmask.unmaskedvalue() : '';
    const rawPrice30minUnmasked = price30minInput && price30minInput.inputmask ? price30minInput.inputmask.unmaskedvalue() : '';
    const rawPrice1hUnmasked = price1hInput && price1hInput.inputmask ? price1hInput.inputmask.unmaskedvalue() : '';

    console.log('DEBUG JS: Unmasked Price 15min (string):', rawPrice15minUnmasked);
    console.log('DEBUG JS: Unmasked Price 30min (string):', rawPrice30minUnmasked);
    console.log('DEBUG JS: Unmasked Price 1h (string):', rawPrice1hUnmasked);

    const rawPrice15min = parseFloat(rawPrice15minUnmasked.replace(',', '.'));
    const rawPrice30min = parseFloat(rawPrice30minUnmasked.replace(',', '.'));
    const rawPrice1h = parseFloat(rawPrice1hUnmasked.replace(',', '.'));

    console.log('DEBUG JS: Parsed Price 15min (float):', rawPrice15min);
    console.log('DEBUG JS: Parsed Price 30min (float):', rawPrice30min);
    console.log('DEBUG JS: Parsed Price 1h (float):', rawPrice1h);


    if ((isNaN(rawPrice15min) || rawPrice15min <= 0) &&
        (isNaN(rawPrice30min) || rawPrice30min <= 0) &&
        (isNaN(rawPrice1h) || rawPrice1h <= 0)) {
        if (pricesFeedback) {
            pricesFeedback.textContent = 'Pelo menos um preço deve ser preenchido com um valor maior que zero.';
            pricesFeedback.style.display = 'block';
        }
        isValidPrices = false;
        if (price15minInput) price15minInput.classList.add('is-invalid');
        if (price30minInput) price30minInput.classList.add('is-invalid');
        if (price1hInput) price1hInput.classList.add('is-invalid');
    } else {
        if (pricesFeedback) {
            pricesFeedback.textContent = '';
            pricesFeedback.style.display = 'none';
        }
        if (price15minInput) price15minInput.classList.remove('is-invalid');
        if (price30minInput) price30minInput.classList.remove('is-invalid');
        if (price1hInput) price1hInput.classList.remove('is-invalid');
    }

    let isValidMedia = true;
    const confirmationVideoInput = document.getElementById('confirmation_video_input');
    const coverPhotoInput = document.getElementById('foto_capa_input');
    const confirmationVideoFeedback = document.getElementById('confirmationVideo-feedback');
    const coverPhotoFeedback = document.getElementById('coverPhoto-feedback');

    const hasNewConfirmationVideo = confirmationVideoInput?.files?.length > 0;
    const existingConfirmationVideoPathInput = document.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]');
    const hasExistingConfirmationVideo = existingConfirmationVideoPathInput && existingConfirmationVideoPathInput.value !== '';
    const confirmationVideoRemoved = document.getElementById('confirmation_video_removed')?.value === 'true';

    if ((!hasNewConfirmationVideo && !hasExistingConfirmationVideo) &&
        (form.dataset.formMode === 'create' || confirmationVideoRemoved)) {
        if (confirmationVideoFeedback) {
            confirmationVideoFeedback.textContent = 'O vídeo de confirmação é obrigatório.';
            confirmationVideoFeedback.style.display = 'block';
        }
        document.getElementById('confirmationVideoUploadBox').classList.add('is-invalid-media');
        isValidMedia = false;
    } else {
        if (confirmationVideoFeedback) {
            confirmationVideoFeedback.textContent = '';
            confirmationVideoFeedback.style.display = 'none';
        }
        document.getElementById('confirmationVideoUploadBox').classList.remove('is-invalid-media');
    }

    const hasNewCoverPhoto = coverPhotoInput?.files?.length > 0;
    const existingCoverPhotoPathInput = document.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]');
    const hasExistingCoverPhoto = existingCoverPhotoPathInput && existingCoverPhotoPathInput.value !== '';
    const coverPhotoRemoved = document.getElementById('cover_photo_removed')?.value === 'true';

    if ((!hasNewCoverPhoto && !hasExistingCoverPhoto) &&
        (form.dataset.formMode === 'create' || coverPhotoRemoved)) {
        if (coverPhotoFeedback) {
            coverPhotoFeedback.textContent = 'A foto da capa é obrigatória.';
            coverPhotoFeedback.style.display = 'block';
        }
        document.getElementById('coverPhotoUploadBox').classList.add('is-invalid-media');
        isValidMedia = false;
    } else {
        if (coverPhotoFeedback) {
            coverPhotoFeedback.textContent = '';
            coverPhotoFeedback.style.display = 'none';
        }
        document.getElementById('coverPhotoUploadBox').classList.remove('is-invalid-media');
    }

    let currentValidGalleryPhotos = 0;
    document.querySelectorAll('.gallery-upload-box').forEach((box) => {
        const input = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = input.files.length > 0;

        // Se o box tem conteúdo (existente ou novo), incrementa o contador
        if (hasExisting || isNew) {
            currentValidGalleryPhotos++;
        }
        console.log(`DEBUG JS: Galeria - Slot (box): hasExisting=${hasExisting}, isNew=${isNew}, currentValidGalleryPhotos=${currentValidGalleryPhotos}`);
    });

    console.log('DEBUG JS: Galeria - Total de fotos válidas na galeria (calculado):', currentValidGalleryPhotos);
    console.log('DEBUG JS: Galeria - Tipo de plano do usuário (form.dataset.userPlanType):', form.dataset.userPlanType);

    const minPhotosRequired = 1;
    const freePhotoLimit = 1;
    const premiumPhotoLimit = 20;

    const galleryFeedbackElement = document.getElementById('galleryPhotoContainer-feedback');

    if (currentValidGalleryPhotos < minPhotosRequired) {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = `Mínimo de ${minPhotosRequired} foto(s) na galeria.` + (form.dataset.userPlanType === 'free' ? ' Para planos gratuitos, apenas 1 foto é permitida.' : '');
            galleryFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'free' && currentValidGalleryPhotos > freePhotoLimit) {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = `Seu plano gratuito permite apenas ${freePhotoLimit} foto na galeria.`;
            galleryFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'premium' && currentValidGalleryPhotos > premiumPhotoLimit) {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = `Seu plano premium permite no máximo ${premiumPhotoLimit} fotos na galeria.`;
            galleryFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else {
        if (galleryFeedbackElement) {
            galleryFeedbackElement.textContent = '';
            galleryFeedbackElement.style.display = 'none';
        }
    }

    const videoFeedbackElement = document.getElementById('videoUploadBoxes-feedback');

    const currentValidVideos = Array.from(document.querySelectorAll('.video-upload-box')).filter(box => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name^="existing_"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = fileInput && fileInput.files.length > 0;
        return isNew || hasExisting;
    }).length;

    if (form.dataset.userPlanType === 'free' && currentValidVideos > 0) {
        if (videoFeedbackElement) {
            videoFeedbackElement.textContent = 'Vídeos são permitidos apenas para planos pagos.';
            videoFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'premium' && currentValidVideos > 3) {
        if (videoFeedbackElement) {
            videoFeedbackElement.textContent = 'Limite de 3 vídeos para plano premium.';
            videoFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else {
        if (videoFeedbackElement) {
            videoFeedbackElement.textContent = '';
            videoFeedbackElement.style.display = 'none';
        }
    }

    const audioFeedbackElement = document.getElementById('audioUploadBoxes-feedback');

    const currentValidAudios = Array.from(document.querySelectorAll('.audio-upload-box')).filter(box => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name^="existing_"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = fileInput && fileInput.files.length > 0;
        return isNew || hasExisting;
    }).length;

    if (form.dataset.userPlanType === 'free' && currentValidAudios > 0) {
        if (audioFeedbackElement) {
            audioFeedbackElement.textContent = 'Áudios são permitidos apenas para planos pagos.';
            audioFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'premium' && currentValidAudios > 3) {
        if (audioFeedbackElement) {
            audioFeedbackElement.textContent = 'Limite de 3 áudios para plano premium.';
            audioFeedbackElement.style.display = 'block';
        }
        isValidMedia = false;
    } else {
        if (audioFeedbackElement) {
            audioFeedbackElement.textContent = '';
            audioFeedbackElement.style.display = 'none';
        }
    }

    console.log('DEBUG JS: Validação Personalizada - isValidCheckboxes:', isValidCheckboxes);
    console.log('DEBUG JS: Validação Personalizada - isValidPrices:', isValidPrices);
    console.log('DEBUG JS: Validação Personalizada - isValidMedia:', isValidMedia);


    if (!isValidCheckboxes || !isValidPrices || !isValidMedia) {
        console.warn('AVISO JS: Validação personalizada falhou. O formulário NÃO será enviado.');
        const firstInvalidElement = form.querySelector('.is-invalid, .is-invalid-media, .is-invalid-group');
        if (firstInvalidElement) {
            firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        window.showFeedbackModal('error', 'Por favor, corrija os erros no formulário.', 'Erro de Validação!');
        return;
    }

    console.log('DEBUG JS: Todas as validações passaram. Preparando para enviar o formulário via AJAX.');
    submitAnuncioForm(form);
}

/**
 * Envia o formulário de anúncio via AJAX.
 * @param {HTMLFormElement} form O formulário a ser enviado.
 */
async function submitAnuncioForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonHTML = window.activateButtonLoading(submitButton, 'Salvando...');

    window.showLoadingModal();

    const formData = new FormData();

    Array.from(form.elements).forEach(element => {
        if (element.name && element.type !== 'file' &&
            !element.name.startsWith('existing_gallery_paths') &&
            !element.name.startsWith('existing_video_paths') &&
            !element.name.startsWith('existing_audio_paths') &&
            !element.name.startsWith('removed_gallery_paths') &&
            !element.name.startsWith('removed_video_paths') &&
            !element.name.startsWith('removed_audio_paths') &&
            element.name !== 'existing_cover_photo_path' &&
            element.name !== 'cover_photo_removed' &&
            element.name !== 'existing_confirmation_video_path' &&
            element.name !== 'confirmation_video_removed'
        ) {
            if (element.type === 'checkbox' || element.type === 'radio') {
                if (element.checked) {
                    formData.append(element.name, element.value);
                }
            } else if (element.tagName === 'SELECT' && element.multiple) {
                Array.from(element.options).filter(option => option.selected).forEach(option => {
                    formData.append(element.name, option.value);
                });
            } else {
                formData.append(element.name, element.value);
            }
        }
    });

    // CORREÇÃO AQUI: Ajustar o valor de height_m para metros
    let heightInput = document.getElementById('height_m');
    if (heightInput) {
        // Pega o valor diretamente do input, que estará no formato "X,YY" devido à máscara
        const maskedValue = heightInput.value;
        // Substitui a vírgula por ponto para que parseFloat funcione corretamente
        const rawHeight = maskedValue.replace(',', '.');
        // Converte para float e garante 2 casas decimais para o backend
        const formattedHeight = parseFloat(rawHeight).toFixed(2);
        formData.set('height_m', formattedHeight);
        console.log(`DEBUG JS: Altura (height_m) enviada: ${formattedHeight} (masked: ${maskedValue}, raw for parse: ${rawHeight})`);
    }


    let weightInput = document.getElementById('weight_kg');
    if (weightInput && weightInput.inputmask) {
        formData.set('weight_kg', weightInput.inputmask.unmaskedvalue());
    } else if (weightInput) {
        formData.set('weight_kg', weightInput.value.replace(/\D/g, ''));
    }

    const price15minInput = document.getElementById('price_15min');
    const price30minInput = document.getElementById('price_30min');
    const price1hInput = document.getElementById('price_1h');

    if (price15minInput && price15minInput.inputmask) {
        formData.set('price_15min', price15minInput.inputmask.unmaskedvalue());
    } else if (price15minInput) {
        formData.set('price_15min', price15minInput.value.replace('R$', '').replace(/\./g, '').replace(',', '.'));
    }
    if (price30minInput && price30minInput.inputmask) {
        formData.set('price_30min', price30minInput.inputmask.unmaskedvalue());
    } else if (price30minInput) {
        formData.set('price_30min', price30minInput.value.replace('R$', '').replace(/\./g, '').replace(',', '.'));
    }
    if (price1hInput && price1hInput.inputmask) {
        formData.set('price_1h', price1hInput.inputmask.unmaskedvalue());
    } else if (price1hInput) {
        formData.set('price_1h', price1hInput.value.replace('R$', '').replace(/\./g, '').replace(',', '.'));
    }
    formData.append('form_mode', form.dataset.formMode);

    const fotoCapaInput = document.getElementById('foto_capa_input');
    const existingCoverPhotoPathInput = form.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]');
    const coverPhotoRemovedInput = document.getElementById('cover_photo_removed');

    if (fotoCapaInput && fotoCapaInput.files.length > 0) {
        formData.append('foto_capa', fotoCapaInput.files[0]);
    } else if (coverPhotoRemovedInput && coverPhotoRemovedInput.value === 'true') {
        formData.append('cover_photo_removed', 'true');
    } else if (existingCoverPhotoPathInput && existingCoverPhotoPathInput.value) {
        formData.append('foto_capa', existingCoverPhotoPathInput.value); // Envia o caminho existente
    }

    const confirmationVideoInput = document.getElementById('confirmation_video_input');
    const existingConfirmationVideoPathInput = form.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]');
    const confirmationVideoRemovedInput = document.getElementById('confirmation_video_removed');

    if (confirmationVideoInput && confirmationVideoInput.files.length > 0) {
        formData.append('confirmation_video', confirmationVideoInput.files[0]);
    } else if (confirmationVideoRemovedInput && confirmationVideoRemovedInput.value === 'true') {
        formData.append('confirmation_video_removed', 'true');
    } else if (existingConfirmationVideoPathInput && existingConfirmationVideoPathInput.value) {
        formData.append('confirmation_video', existingConfirmationVideoPathInput.value); // Envia o caminho existente
    }

    const galleryPhotoContainers = document.querySelectorAll('.gallery-upload-box');
    galleryPhotoContainers.forEach((box, index) => {
        const fileInput = box.querySelector(`input[name="fotos_galeria_upload_${index}"]`);
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');

        if (fileInput && fileInput.files.length > 0) {
            formData.append('fotos_galeria[]', fileInput.files[0]);
        } else if (existingPathInput && existingPathInput.value) {
            formData.append('fotos_galeria[]', existingPathInput.value);
        }
    });

    const videoContainers = document.querySelectorAll('.video-upload-box');
    videoContainers.forEach((box, index) => {
        const fileInput = box.querySelector(`input[name="videos_upload_${index}"]`);
        const existingPathInput = box.querySelector('input[name="existing_video_paths[]"]');

        if (fileInput && fileInput.files.length > 0) {
            formData.append('videos[]', fileInput.files[0]);
        } else if (existingPathInput && existingPathInput.value) {
            formData.append('videos[]', existingPathInput.value);
        }
    });

    const audioContainers = document.querySelectorAll('.audio-upload-box');
    audioContainers.forEach((box, index) => {
        const fileInput = box.querySelector(`input[name="audios_upload_${index}"]`);
        const existingPathInput = box.querySelector('input[name="existing_audio_paths[]"]');

        if (fileInput && fileInput.files.length > 0) {
            formData.append('audios[]', fileInput.files[0]);
        } else if (existingPathInput && existingPathInput.value) {
            formData.append('audios[]', existingPathInput.value);
        }
    });

    console.log('DEBUG JS: Conteúdo do FormData antes do envio:');
    for (let pair of formData.entries()) {
        if (pair[1] instanceof File) {
            console.log(`    ${pair[0]}: File - ${pair[1].name} (${pair[1].type})`);
        } else {
            console.log(`    ${pair[0]}: ${pair[1]}`);
        }
    }

    try {
        const url = form.action;
        console.log(`DEBUG JS: Enviando formulário para: ${url}`);

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        // AGORA ESPERAMOS O MODAL DE CARREGAMENTO SUMIR COMPLETAMENTE
        await window.hideLoadingModal(); 
        window.deactivateButtonLoading(submitButton, originalButtonHTML);

        console.log('INFO JS: Modal de carregamento ocultado. Mostrando modal de feedback.');

        if (result.success) {
            // Define autoCloseDelay para 2000ms (2 segundos)
            window.showFeedbackModal('success', result.message, 'Sucesso!', 2000); 
            document.body.dataset.hasAnuncio = result.has_anuncio ? 'true' : 'false';
            document.body.dataset.anuncioStatus = result.anuncio_status || 'not_found';
            document.body.dataset.anuncioId = result.anuncio_id || '';
            window.updateAnuncioSidebarLinks();

            if (form.dataset.formMode === 'create' && result.anuncio_id) {
                setTimeout(() => {
                    window.loadContent(`${window.URLADM}anuncio/editarAnuncio?id=${result.anuncio_id}`, 'anuncio/editarAnuncio');
                }, 1500);
            } else if (result.redirect) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1500);
            }
        } else { 
            let errorMessage = result.message || 'Ocorreu um erro ao processar o anúncio.';
            if (result.errors) {
                for (const field in result.errors) {
                    const feedbackElement = document.getElementById(`${field}-feedback`);
                    if (feedbackElement) {
                        feedbackElement.textContent = result.errors[field];
                        feedbackElement.style.display = 'block';
                        const uploadBoxIdMap = {
                            'confirmation_video': 'confirmationVideoUploadBox',
                            'foto_capa': 'coverPhotoUploadBox',
                            'fotos_galeria': 'galleryPhotoContainer',
                            'videos': 'videoUploadBoxes',
                            'audios': 'audioUploadBoxes',
                            'aparencia': 'aparencia-checkboxes',
                            'idiomas': 'idiomas-checkboxes',
                            'locais_atendimento': 'locais_atendimento-checkboxes',
                            'formas_pagamento': 'formas_pagamento-checkboxes',
                            'servicos': 'servicos-checkboxes',
                            'precos': 'precos-feedback'
                        };
                        const targetElementId = uploadBoxIdMap[field] || field;
                        const targetElement = document.getElementById(targetElementId);

                        if (targetElement) {
                            if (targetElement.classList.contains('photo-upload-box') || targetElement.id === 'galleryPhotoContainer' || targetElement.id === 'videoUploadBoxes' || targetElement.id === 'audioUploadBoxes') {
                                targetElement.classList.add('is-invalid-media');
                            } else if (targetElement.classList.contains('row') && targetElement.id.endsWith('-checkboxes')) {
                                targetElement.classList.add('is-invalid-group');
                            } else if (targetElement.id === 'precos-feedback') {
                                // Não adiciona classe de erro diretamente ao feedback de preço, pois os inputs já são marcados.
                            }
                        }
                    } else {
                        errorMessage += `\n- ${result.errors[field]}`;
                    }
                }
            }
            const firstInvalidElement = form.querySelector('.is-invalid, .is-invalid-media, .is-invalid-group');
            if (firstInvalidElement) {
                firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            window.showFeedbackModal('error', errorMessage, 'Erro!');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX:', error);
        // Garante que o modal de carregamento seja ocultado mesmo em caso de erro de rede
        await window.hideLoadingModal(); 
        window.deactivateButtonLoading(submitButton, originalButtonHTML);
        window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
    }
}


/**
 * Configura máscaras para campos de input.
 */
function setupInputMasks() {
    console.info('INFO JS: setupInputMasks - Aplicando máscaras nos inputs.');

    const phoneNumberInput = document.getElementById('phone_number');
    if (phoneNumberInput) {
        Inputmask({ "mask": "(99) 99999-9999" }).mask(phoneNumberInput);
    }

    const ageInput = document.getElementById('age');
    if (ageInput) {
        Inputmask("99", { numericInput: true, placeholder: "" }).mask(ageInput);
    }

    const heightInput = document.getElementById('height_m');
    if (heightInput) {
        Inputmask({
            mask: "9,99", // Força um dígito antes da vírgula e dois depois (ex: 1,55)
            numericInput: false, // Não inverte a entrada para direita
            placeholder: "0,00",
            rightAlign: false,
            onBeforeMask: function (value, opts) {
                // Ao carregar valor do backend (ex: "1.70" ou "1,70"), converte para vírgula para a máscara (ex: "1,70")
                if (value !== null && value !== undefined && value !== '') {
                    // Tenta converter para float primeiro, depois formata para string com vírgula
                    let numericValue = parseFloat(String(value).replace(',', '.'));
                    if (!isNaN(numericValue)) {
                        let formattedValue = numericValue.toFixed(2).replace('.', ',');
                        console.debug(`DEBUG JS: height_m onBeforeMask - Valor de entrada: ${value}, Valor formatado para máscara: ${formattedValue}`);
                        return formattedValue;
                    }
                }
                console.debug(`DEBUG JS: height_m onBeforeMask - Valor de entrada vazio/nulo ou inválido. Retornando valor original.`);
                return value; // Retorna o valor original se for vazio/nulo ou não puder ser parseado
            }
        }).mask(heightInput);
    }

    const weightInput = document.getElementById('weight_kg');
    if (weightInput) {
        Inputmask({
            alias: 'numeric',
            groupSeparator: '',
            radixPoint: '',
            autoGroup: false,
            digits: 0,
            digitsOptional: true,
            placeholder: "",
            rightAlign: false,
            clearMaskOnLostFocus: false,
            onBeforeMask: function (value, opts) {
                return String(value).replace(/\D/g, '');
            }
        }).mask(weightInput);
    }

    const priceInputs = ['price_15min', 'price_30min', 'price_1h'];
    priceInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            Inputmask({
                alias: 'numeric',
                groupSeparator: '.',
                radixPoint: ',',
                autoGroup: true,
                digits: 2,
                digitsOptional: false,
                prefix: 'R$ ',
                placeholder: "0,00",
                rightAlign: false,
                clearMaskOnLostFocus: false,
                onBeforeMask: function (value, opts) {
                    const cleanedValue = String(value).replace(/[R$\s.]/g, '').replace(',', '.');
                    return cleanedValue;
                },
                onUnMask: function (maskedValue, unmaskedValue) {
                    return parseFloat(unmaskedValue.replace(',', '.')).toFixed(2);
                }
            }).mask(input);
        }
    });
}

/**
 * Carrega e popula os selects de UF, Cidade e Bairro.
 * @param {object} anuncioData Dados do anúncio para pré-selecionar valores.
 * @returns {Promise<void>} Uma promessa que resolve quando as localizações são carregadas e pré-preenchidas.
 */
async function loadAndPopulateLocations(anuncioData) {
    const ufSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const neighborhoodSelect = document.getElementById('neighborhood_id');

    const initialUf = anuncioData.state_uf;
    const initialCityCode = anuncioData.city_code;
    const initialNeighborhoodName = anuncioData.neighborhood_name;

    console.log('DEBUG JS: loadAndPopulateLocations - Initial UF:', initialUf);
    console.log('DEBUG JS: loadAndPopulateLocations - Initial City Code:', initialCityCode);
    console.log('DEBUG JS: loadAndPopulateLocations - Initial Neighborhood Name:', initialNeighborhoodName);

    try {
        const responseStates = await fetch(`${window.projectBaseURL}app/adms/assets/js/data/states.json`);
        const dataStates = await responseStates.json();
        ufSelect.innerHTML = '<option value="">Selecione um Estado</option>';
        dataStates.data.forEach(uf => {
            const option = document.createElement('option');
            option.value = uf.Uf;
            option.textContent = uf.Nome;
            ufSelect.appendChild(option);
        });

        const loadCitiesForUf = (uf) => new Promise(async (resolve, reject) => {
            citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
            citySelect.disabled = true;
            // neighborhoodSelect.innerHTML = '<option value="">Selecione um Bairro</option>'; // Removido para manter o input de texto
            neighborhoodSelect.disabled = true;

            if (uf) {
                try {
                    const responseCities = await fetch(`${window.projectBaseURL}app/adms/assets/js/data/cities.json`);
                    const dataCities = await responseCities.json();
                    const cities = dataCities.data.filter(city => city.Uf === uf);

                    citySelect.innerHTML = '<option value="">Selecione uma Cidade</option>';
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.Codigo;
                        option.textContent = city.Nome;
                        citySelect.appendChild(option);
                    });
                    citySelect.disabled = false;
                    resolve();
                } catch (error) {
                    console.error('ERRO JS: Erro ao carregar cidades:', error);
                    citySelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                    reject(error);
                }
            } else {
                citySelect.innerHTML = '<option value="">Selecione uma Cidade</option>';
                resolve();
            }
        });

        // A função loadNeighborhoodsForCity não é mais necessária para um input de texto de bairro.
        // O campo de bairro é um input de texto simples, não um select dinâmico.
        // Apenas habilitá-lo quando a cidade for selecionada.

        // Remove listeners antigos para evitar duplicação em navegações SPA
        if (ufSelect._changeHandler) ufSelect.removeEventListener('change', ufSelect._changeHandler);
        if (citySelect._changeHandler) citySelect.removeEventListener('change', citySelect._changeHandler);

        // Adiciona os event listeners e armazena as referências
        const ufChangeHandler = async function() {
            const selectedUf = this.value;
            await loadCitiesForUf(selectedUf);
            // Se o estado foi alterado, limpa a cidade e o bairro
            citySelect.value = '';
            neighborhoodSelect.value = '';
            neighborhoodSelect.disabled = true;
            // Se houver um initialCityCode e o estado selecionado for o initialUf, tenta pré-selecionar a cidade
            if (initialCityCode && selectedUf === initialUf) {
                citySelect.value = initialCityCode;
                // Dispara o evento change para que o listener da cidade seja acionado
                const event = new Event('change');
                citySelect.dispatchEvent(event);
            }
        };
        ufSelect.addEventListener('change', ufChangeHandler);
        ufSelect._changeHandler = ufChangeHandler;

        const cityChangeHandler = function() {
            const selectedCityCode = this.value;
            if (selectedCityCode) {
                neighborhoodSelect.disabled = false;
                neighborhoodSelect.placeholder = "Digite o nome do Bairro";
            } else {
                neighborhoodSelect.disabled = true;
                neighborhoodSelect.placeholder = "Selecione a Cidade primeiro";
                neighborhoodSelect.value = ''; // Limpa o bairro se a cidade for deselecionada
            }
            // Se houver um initialNeighborhoodName e a cidade selecionada for a initialCityCode, tenta pré-preencher o bairro
            if (initialNeighborhoodName && selectedCityCode === initialCityCode) {
                neighborhoodSelect.value = initialNeighborhoodName;
            }
        };
        citySelect.addEventListener('change', cityChangeHandler);
        citySelect._changeHandler = cityChangeHandler;

        // Lógica de pré-seleção na carga inicial
        if (initialUf) {
            ufSelect.value = initialUf;
            await loadCitiesForUf(initialUf);
            if (initialCityCode) {
                citySelect.value = initialCityCode;
                // Dispara o evento change para que o listener da cidade seja acionado
                const event = new Event('change');
                citySelect.dispatchEvent(event);
                // O bairro já deve ter sido preenchido em initializeFormFields, mas garantir que esteja habilitado
                if (initialNeighborhoodName) {
                    neighborhoodSelect.disabled = false;
                    neighborhoodSelect.placeholder = "Digite o nome do Bairro";
                }
            }
        }

    } catch (error) {
        console.error('ERRO JS: Erro geral ao carregar localizações:', error);
        if (ufSelect) ufSelect.innerHTML = '<option value="">Erro ao carregar estados</option>';
        if (citySelect) citySelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
        window.showFeedbackModal('error', 'Não foi possível carregar os dados de localização.', 'Erro de Localização');
        throw error;
    }
    console.log('INFO JS: loadAndPopulateLocations - Localização carregada e populada.');
}


/**
 * Inicializa os campos do formulário com dados existentes (para edição) ou limpa para criação.
 * @param {HTMLFormElement} form O formulário principal.
 * @param {object} anuncioData Dados do anúncio para pré-preenchimento.
 * @param {string} formMode Modo do formulário ('create' ou 'edit').
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function initializeFormFields(form, anuncioData, formMode, userPlanType) {
    console.info('INFO JS: initializeFormFields - Inicializando campos do formulário.');

    const formTitleElement = document.getElementById('formAnuncioTitle');
    if (formTitleElement) {
        // Esta parte já é controlada por initializeAnuncioFormPage
        // formTitleElement.textContent = formMode === 'edit' ? 'Editar Anúncio' : 'Criar Novo Anúncio';
    }

    const textAndNumberFields = [
        'service_name', 'age', 'phone_number', 'description',
        'gender', 'nationality', 'ethnicity', 'eye_color'
    ];

    textAndNumberFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && anuncioData[field] !== undefined && anuncioData[field] !== null) {
            input.value = String(anuncioData[field]);
            console.log(`DEBUG JS: Campo ${field} preenchido com: ${input.value}`);
        } else if (input && formMode === 'create') {
            input.value = '';
            console.log(`DEBUG JS: Campo ${field} limpo para criação.`);
        }
    });

    const heightInput = document.getElementById('height_m');
    if (heightInput && anuncioData.height_m !== undefined && anuncioData.height_m !== null) {
        // Para o campo de altura, garantir que o valor seja uma string para o inputmask
        // O onBeforeMask da máscara de altura cuidará da formatação para "X,YY"
        heightInput.value = String(anuncioData.height_m);
        console.log(`DEBUG JS: Campo height_m preenchido com: ${heightInput.value} (anuncioData original: ${anuncioData.height_m})`);
    } else if (heightInput && formMode === 'create') {
        heightInput.value = '';
        console.log(`DEBUG JS: Campo height_m limpo para criação.`);
    }

    const weightInput = document.getElementById('weight_kg');
    if (weightInput && anuncioData.weight_kg !== undefined && anuncioData.weight_kg !== null) {
        weightInput.value = String(anuncioData.weight_kg);
        console.log(`DEBUG JS: Campo weight_kg preenchido com: ${weightInput.value} (anuncioData: ${anuncioData.weight_kg})`);
    } else if (weightInput && formMode === 'create') {
        weightInput.value = '';
        console.log(`DEBUG JS: Campo weight_kg limpo para criação.`);
    }


    const priceFields = ['price_15min', 'price_30min', 'price_1h'];
    priceFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && anuncioData[field] !== undefined && anuncioData[field] !== null) {
            if (input.inputmask) {
                const numericValue = parseFloat(anuncioData[field]);
                if (!isNaN(numericValue)) {
                    input.value = String(numericValue);
                } else {
                    input.value = '';
                }
            } else {
                input.value = parseFloat(anuncioData[field]).toFixed(2).replace('.', ',');
                console.warn(`AVISO JS: Inputmask não aplicado para ${field}. Valor formatado manualmente.`);
            }
            console.log(`DEBUG JS: Campo ${field} preenchido com: ${input.value} (anuncioData: ${anuncioData[field]})`);
        } else if (input && formMode === 'create') {
            input.value = '';
            console.log(`DEBUG JS: Campo ${field} limpo para criação.`);
        }
    });


    const checkboxGroups = {
        'aparencia[]': 'aparencia',
        'idiomas[]': 'idiomas',
        'locais_atendimento[]': 'locais_atendimento',
        'formas_pagamento[]': 'formas_pagamento',
        'servicos[]': 'servicos'
    };

    for (const name in checkboxGroups) {
        const dataKey = checkboxGroups[name];
        const checkboxes = form.querySelectorAll(`input[name="${name}"]`);
        const existingValues = anuncioData[dataKey] || [];

        checkboxes.forEach(checkbox => {
            checkbox.checked = existingValues.includes(checkbox.value);
        });
        console.log(`DEBUG JS: Checkboxes para ${dataKey} preenchidos. Valores existentes:`, existingValues);
    }

    const anuncioIdInput = form.querySelector('input[name="anuncio_id"]');
    if (anuncioIdInput) {
        anuncioIdInput.value = anuncioData.id || '';
        console.log(`DEBUG JS: Campo anuncio_id preenchido com: ${anuncioIdInput.value}`);
    }

    const anuncianteUserIdInput = form.querySelector('input[name="anunciante_user_id"]'); // Adicionado para garantir que o ID do anunciante seja enviado
    if (anuncianteUserIdInput) {
        anuncianteUserIdInput.value = anuncioData.user_id || '';
        console.log(`DEBUG JS: Campo anunciante_user_id preenchido com: ${anuncianteUserIdInput.value}`);
    }

    const confirmationVideoPreview = document.getElementById('confirmationVideoPreview');
    const coverPhotoPreview = document.getElementById('coverPhotoPreview');
    const confirmationVideoPlaceholder = document.querySelector('#confirmationVideoUploadBox .upload-placeholder');
    const coverPhotoPlaceholder = document.querySelector('#coverPhotoUploadBox .upload-placeholder');
    const confirmationVideoRemoveBtn = document.querySelector('#confirmationVideoUploadBox .btn-remove-photo');
    const coverPhotoRemoveBtn = document.querySelector('#coverPhotoUploadBox .btn-remove-photo');

    const existingConfirmationVideoPathInput = document.querySelector('input[name="existing_confirmation_video_path"]');
    const existingCoverPhotoPathInput = document.querySelector('input[name="existing_cover_photo_path"]');

    // Helper para verificar se o caminho é uma URL absoluta
    const isAbsolutePath = (path) => path && (path.startsWith('http://') || path.startsWith('https://'));

    if (formMode === 'edit') {
        console.log('DEBUG JS: Modo de edição. Tentando preencher mídias principais.');
        console.log(`DEBUG JS: anuncioData.confirmation_video_path: ${anuncioData.confirmation_video_path}`);
        if (anuncioData.confirmation_video_path && confirmationVideoPreview) {
            let videoUrl;
            if (isAbsolutePath(anuncioData.confirmation_video_path)) {
                videoUrl = anuncioData.confirmation_video_path;
            } else {
                videoUrl = `${window.projectBaseURL}${anuncioData.confirmation_video_path}`;
            }
            confirmationVideoPreview.src = videoUrl;
            confirmationVideoPreview.style.display = 'block';
            if (confirmationVideoPlaceholder) confirmationVideoPlaceholder.style.display = 'none';
            if (confirmationVideoRemoveBtn) confirmationVideoRemoveBtn.classList.remove('d-none');
            if (existingConfirmationVideoPathInput) existingConfirmationVideoPathInput.value = anuncioData.confirmation_video_path;
            console.log(`DEBUG JS: Vídeo de confirmação preenchido com URL: ${videoUrl}`);
        } else {
            console.log('DEBUG JS: Vídeo de confirmação não encontrado nos dados ou preview não existe. Limpando.');
            if (confirmationVideoPreview) confirmationVideoPreview.removeAttribute('src');
            if (confirmationVideoPreview) confirmationVideoPreview.style.display = 'none';
            if (confirmationVideoPlaceholder) confirmationVideoPlaceholder.style.display = 'flex';
            if (confirmationVideoRemoveBtn) confirmationVideoRemoveBtn.classList.add('d-none');
            if (existingConfirmationVideoPathInput) existingConfirmationVideoPathInput.value = '';
        }

        console.log(`DEBUG JS: anuncioData.cover_photo_path: ${anuncioData.cover_photo_path}`);
        if (anuncioData.cover_photo_path && coverPhotoPreview) {
            let photoUrl;
            if (isAbsolutePath(anuncioData.cover_photo_path)) {
                photoUrl = anuncioData.cover_photo_path;
            } else {
                photoUrl = `${window.projectBaseURL}${anuncioData.cover_photo_path}`;
            }
            coverPhotoPreview.src = photoUrl;
            coverPhotoPreview.style.display = 'block';
            if (coverPhotoPlaceholder) coverPhotoPlaceholder.style.display = 'none';
            if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.classList.remove('d-none');
            if (existingCoverPhotoPathInput) existingCoverPhotoPathInput.value = anuncioData.cover_photo_path;
            console.log(`DEBUG JS: Foto da capa preenchido com URL: ${photoUrl}`);
        } else {
            console.log('DEBUG JS: Foto da capa não encontrada nos dados ou preview não existe. Limpando.');
            if (coverPhotoPreview) coverPhotoPreview.removeAttribute('src');
            if (coverPhotoPreview) coverPhotoPreview.style.display = 'none';
            if (coverPhotoPlaceholder) coverPhotoPlaceholder.style.display = 'flex';
            if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.classList.add('d-none');
            if (existingCoverPhotoPathInput) existingCoverPhotoPathInput.value = '';
        }
    } else { // Modo de criação: garantir que os campos de mídia estejam limpos
        console.log('DEBUG JS: Modo de criação. Limpando mídias principais.');
        if (confirmationVideoPreview) confirmationVideoPreview.removeAttribute('src');
        if (confirmationVideoPreview) confirmationVideoPreview.style.display = 'none';
        if (confirmationVideoPlaceholder) confirmationVideoPlaceholder.style.display = 'flex';
        if (confirmationVideoRemoveBtn) confirmationVideoRemoveBtn.classList.add('d-none');
        if (existingConfirmationVideoPathInput) existingConfirmationVideoPathInput.value = '';

        if (coverPhotoPreview) coverPhotoPreview.removeAttribute('src');
        if (coverPhotoPreview) coverPhotoPreview.style.display = 'none';
        if (coverPhotoPlaceholder) coverPhotoPlaceholder.style.display = 'flex';
        if (coverPhotoRemoveBtn) coverPhotoRemoveBtn.classList.add('d-none');
        if (existingCoverPhotoPathInput) existingCoverPhotoPathInput.value = '';
    }

    const mediaMultiUploads = {
        gallery: { container: document.getElementById('galleryPhotoContainer'), dataKey: 'fotos_galeria', type: 'image' },
        video: { container: document.getElementById('videoUploadBoxes'), dataKey: 'videos', type: 'video' },
        audio: { container: document.getElementById('audioUploadBoxes'), dataKey: 'audios', type: 'audio' }
    };

    for (const key in mediaMultiUploads) {
        const { container, dataKey, type } = mediaMultiUploads[key];
        if (!container) {
            console.warn(`AVISO JS: Container para ${key} (ID: ${container?.id}) não encontrado. Pulando.`);
            continue;
        }

        const existingMediaArray = anuncioData[dataKey] || [];
        const boxes = container.querySelectorAll('.photo-upload-box');

        console.log(`DEBUG JS: Processando ${dataKey}. Dados existentes (anuncioData.${dataKey}):`, existingMediaArray);
        console.log(`DEBUG JS: Número de boxes encontrados para ${dataKey}:`, boxes.length);

        boxes.forEach((box, index) => {
            const preview = box.querySelector('.photo-preview') || box.querySelector('video') || box.querySelector('audio');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo');
            const existingPathInput = box.querySelector('input[name^="existing_"]'); // existing_gallery_paths[], etc.

            const currentMediaPath = existingMediaArray[index]; // Pega o caminho do array de dados

            console.log(`DEBUG JS: ${dataKey} - Slot ${index}: currentMediaPath =`, currentMediaPath);
            console.log(`DEBUG JS: ${dataKey} - Slot ${index}: existingPathInput =`, existingPathInput);

            if (formMode === 'edit' && currentMediaPath) {
                let mediaUrl;
                if (isAbsolutePath(currentMediaPath)) {
                    mediaUrl = currentMediaPath;
                } else {
                    mediaUrl = `${window.projectBaseURL}${currentMediaPath}`;
                }
                console.debug(`DEBUG JS: Carregando ${type} da galeria slot ${index}: ${currentMediaPath}. URL final: ${mediaUrl}`);
                if (preview) {
                    preview.src = mediaUrl;
                    preview.style.display = 'block';
                } else {
                    console.warn(`AVISO JS: Elemento de preview não encontrado para ${type} no slot ${index}.`);
                }
                if (placeholder) placeholder.style.display = 'none';
                if (removeBtn) removeBtn.classList.remove('d-none');
                if (existingPathInput) existingPathInput.value = currentMediaPath; // Mantém o caminho relativo no hidden input
            } else {
                console.debug(`DEBUG JS: Nenhum ${type} existente para slot ${index} ou modo de criação. Limpando.`);
                if (preview) preview.removeAttribute('src');
                if (preview) preview.style.display = 'none';
                if (placeholder) placeholder.style.display = 'flex';
                if (removeBtn) removeBtn.classList.add('d-none');
                if (existingPathInput) existingPathInput.value = '';
            }
        });
    }

    // applyPlanRestrictions(userPlanType); // Movido para o final de initializeAnuncioFormPage
}


/**
 * Configura os manipuladores de upload de arquivos (fotos, vídeos, áudios).
 * Esta função agora foca apenas em adicionar os event listeners.
 * @param {HTMLFormElement} form O formulário principal.
 * @param {object} anuncioData Dados do anúncio para pré-preenchimento.
 * @param {string} formMode Modo do formulário ('create' ou 'edit').
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function setupFileUploadHandlers(form, anuncioData, formMode, userPlanType) {
    console.info('INFO JS: setupFileUploadHandlers - Configurando event listeners para uploads de mídia.');

    function setupSingleMediaInput(inputElement, previewElement, removeButton, removedHiddenInput, existingPathHiddenInput, type) {
        const uploadBox = inputElement.closest('.photo-upload-box');
        const placeholder = uploadBox.querySelector('.upload-placeholder');

        if (!inputElement || !previewElement || !removeButton || !uploadBox || !placeholder) {
            console.error('ERRO JS: Elementos de mídia não encontrados para setup de input único.', { inputElement, previewElement, removeButton, uploadBox, placeholder });
            return;
        }

        // Remove listeners antigos para evitar duplicação em navegações SPA
        // REMOVIDO: uploadBox.removeEventListener('click', uploadBox._clickHandler); // Não mais necessário, _clickHandler não é global
        inputElement.removeEventListener('change', inputElement._changeHandler);
        removeButton.removeEventListener('click', removeButton._clickHandler);

        const clickHandler = function() {
            if (!uploadBox.classList.contains('locked')) {
                inputElement.click();
            } else {
                window.showFeedbackModal('info', 'Este slot está bloqueado para o seu plano atual.', 'Acesso Restrito');
            }
        };
        uploadBox.addEventListener('click', clickHandler);
        uploadBox._clickHandler = clickHandler; // Armazena a referência para remoção futura

        const changeHandler = function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeButton.classList.remove('d-none');
                    if (removedHiddenInput) removedHiddenInput.value = 'false';
                    if (existingPathHiddenInput) existingPathHiddenInput.value = '';
                    console.debug(`DEBUG JS: Preview de ${type} atualizado com novo arquivo.`);
                    applyPlanRestrictions(userPlanType); // Reaplicar restrições após upload
                };
                if (type === 'image') {
                    reader.readAsDataURL(file);
                } else if (type === 'video' || type === 'audio') {
                    previewElement.src = URL.createObjectURL(file);
                    previewElement.load();
                }
            } else {
                // Se o input de arquivo foi limpo (ex: usuário selecionou e depois cancelou),
                // mas havia um caminho existente, ele deve ser restaurado no preview e no hidden input.
                // A menos que o botão de remover tenha sido clicado anteriormente.
                if (existingPathHiddenInput && existingPathHiddenInput.value !== '' && (!removedHiddenInput || removedHiddenInput.value !== 'true')) {
                    const path = existingPathInput.value;
                    if (path.startsWith('http://') || path.startsWith('https://')) {
                        previewElement.src = path;
                    } else {
                        previewElement.src = `${window.projectBaseURL}${path}`;
                    }
                    previewElement.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeButton.classList.remove('d-none');
                } else {
                    previewElement.removeAttribute('src');
                    previewElement.style.display = 'none';
                    placeholder.style.display = 'flex';
                    removeButton.classList.add('d-none');
                }
                if (removedHiddenInput) removedHiddenInput.value = 'false';
                // Garante que o valor existente é mantido se não houver novo arquivo
                if (existingPathHiddenInput) existingPathHiddenInput.value = existingPathHiddenInput.value; 
                applyPlanRestrictions(userPlanType); // Reaplicar restrições após limpar input
            }
        };
        inputElement.addEventListener('change', changeHandler);
        inputElement._changeHandler = changeHandler;

        const removeClickHandler = function(e) {
            e.stopPropagation();
            window.showConfirmModal('Remover Mídia', 'Tem certeza que deseja remover esta mídia?', () => {
                inputElement.value = '';
                previewElement.removeAttribute('src');
                previewElement.style.display = 'none';
                placeholder.style.display = 'flex';
                removeButton.classList.add('d-none');
                if (removedHiddenInput) {
                    removedHiddenInput.value = 'true';
                }
                if (existingPathHiddenInput) {
                    existingPathHiddenInput.value = '';
                }
                console.debug(`DEBUG JS: ${type} removido.`);
                applyPlanRestrictions(userPlanType); // Reaplicar restrições após remover
            });
        };
        removeButton.addEventListener('click', removeClickHandler);
        removeButton._clickHandler = removeClickHandler;
    }

    setupSingleMediaInput(
        document.getElementById('confirmation_video_input'),
        document.getElementById('confirmationVideoPreview'),
        document.querySelector('#confirmationVideoUploadBox .btn-remove-photo'),
        document.getElementById('confirmation_video_removed'),
        document.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]'),
        'video'
    );

    setupSingleMediaInput(
        document.getElementById('foto_capa_input'),
        document.getElementById('coverPhotoPreview'),
        document.querySelector('#coverPhotoUploadBox .btn-remove-photo'),
        document.getElementById('cover_photo_removed'),
        document.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]'),
        'image'
    );

    const mediaMultiUploads = {
        gallery: { container: document.getElementById('galleryPhotoContainer'), type: 'image' },
        video: { container: document.getElementById('videoUploadBoxes'), type: 'video' },
        audio: { container: document.getElementById('audioUploadBoxes'), type: 'audio' }
    };

    for (const key in mediaMultiUploads) {
        const { container, type } = mediaMultiUploads[key];
        if (!container) {
            console.warn(`AVISO JS: Container para ${key} (ID: ${container?.id}) não encontrado. Pulando.`);
            continue;
        }

        container.querySelectorAll('.photo-upload-box').forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('.photo-preview') || box.querySelector('video') || box.querySelector('audio');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo');
            const existingPathInput = box.querySelector('input[name^="existing_"]');
            const premiumLockOverlay = box.querySelector('.premium-lock-overlay');

            // Remove listeners antigos para evitar duplicação em navegações SPA
            box.removeEventListener('click', box._clickHandler);
            input.removeEventListener('change', input._changeHandler);
            removeBtn.removeEventListener('click', removeBtn._clickHandler);

            const clickHandler = function() {
                if (!premiumLockOverlay || premiumLockOverlay.style.display === 'none') {
                    input.click();
                } else {
                    window.showFeedbackModal('info', 'Este slot está bloqueado para o seu plano atual.', 'Acesso Restrito');
                }
            };
            box.addEventListener('click', clickHandler);
            box._clickHandler = clickHandler;

            const changeHandler = function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                        removeBtn.classList.remove('d-none');
                        if (existingPathInput) existingPathInput.value = '';
                        applyPlanRestrictions(userPlanType); // Reaplicar restrições
                    };
                    if (type === 'image') {
                        reader.readAsDataURL(file);
                    } else if (type === 'video' || type === 'audio') {
                        preview.src = URL.createObjectURL(file);
                        preview.load();
                    }
                } else {
                    if (existingPathInput && existingPathInput.value !== '') {
                        const path = existingPathInput.value;
                        if (path.startsWith('http://') || path.startsWith('https://')) {
                            preview.src = path;
                        } else {
                            preview.src = `${window.projectBaseURL}${path}`;
                        }
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                        removeBtn.classList.remove('d-none');
                    } else {
                        preview.removeAttribute('src');
                        preview.style.display = 'none';
                        placeholder.style.display = 'flex';
                        removeBtn.classList.add('d-none');
                    }
                    applyPlanRestrictions(userPlanType); // Reaplicar restrições
                }
            };
            input.addEventListener('change', changeHandler);
            input._changeHandler = changeHandler;

            const removeClickHandler = function(event) {
                event.stopPropagation();
                window.showConfirmModal('Remover Mídia', 'Tem certeza que deseja remover esta mídia?', () => {
                    preview.removeAttribute('src');
                    preview.style.display = 'none';
                    placeholder.style.display = 'flex';
                    removeBtn.classList.add('d-none');
                    input.value = '';
                    if (existingPathInput) existingPathInput.value = '';
                    applyPlanRestrictions(userPlanType); // Reaplicar restrições
                });
            };
            removeBtn.addEventListener('click', removeClickHandler);
            removeBtn._clickHandler = removeClickHandler;
        });
    }
}


/**
 * Aplica restrições de plano para uploads de mídia (fotos, vídeos, áudios).
 * Bloqueia slots e exibe overlays para recursos não permitidos pelo plano.
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function applyPlanRestrictions(userPlanType) {
    console.info('INFO JS: Aplicando restrições de plano para mídias. Plano:', userPlanType);

    const galleryPhotoContainers = document.querySelectorAll('.gallery-upload-box');
    const videoUploadBoxes = document.querySelectorAll('.video-upload-box');
    const audioUploadBoxes = document.querySelectorAll('.audio-upload-box');

    const freePhotoLimit = 1;
    const premiumPhotoLimit = 20;
    const premiumVideoAudioLimit = 3;

    let currentGalleryPhotosCount = 0;
    galleryPhotoContainers.forEach(box => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');
        const premiumLockOverlay = box.querySelector('.premium-lock-overlay');
        const inputElement = box.querySelector('input[type="file"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = fileInput && fileInput.files.length > 0;

        // Se o box tem conteúdo (existente ou novo), incrementa o contador
        if (hasExisting || isNew) {
            currentGalleryPhotosCount++;
        }

        // Resetar estado de bloqueio antes de aplicar novas regras
        box.classList.remove('locked');
        if (premiumLockOverlay) premiumLockOverlay.style.display = 'none';
        if (inputElement) inputElement.disabled = false; // Habilitar input por padrão

        // Lógica de bloqueio
        if (userPlanType === 'free') {
            // Para plano gratuito, apenas o primeiro slot de galeria é permitido
            if (box !== galleryPhotoContainers[0]) { // Se não for o primeiro slot
                box.classList.add('locked');
                if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                if (inputElement) inputElement.disabled = true;
            }
        } else if (userPlanType === 'premium') {
            // Para plano premium, bloqueia se exceder o limite (20 fotos)
            // Esta lógica é mais complexa e geralmente é tratada pela validação do backend
            // Mas para o UI, podemos bloquear slots vazios se o limite já foi atingido por fotos existentes
            if (currentGalleryPhotosCount >= premiumPhotoLimit && !hasExisting && !isNew) {
                box.classList.add('locked');
                if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                if (inputElement) inputElement.disabled = true;
            }
        }
    });

    // Lógica para Vídeos e Áudios
    [videoUploadBoxes, audioUploadBoxes].forEach(mediaTypeBoxes => {
        let currentMediaCount = 0;
        mediaTypeBoxes.forEach(box => {
            const fileInput = box.querySelector('input[type="file"]');
            const existingPathInput = box.querySelector('input[name^="existing_"]');
            if ((existingPathInput && existingPathInput.value !== '') || (fileInput && fileInput.files.length > 0)) {
                currentMediaCount++;
            }
        });

        mediaTypeBoxes.forEach(box => {
            const premiumLockOverlay = box.querySelector('.premium-lock-overlay');
            const inputElement = box.querySelector('input[type="file"]');
            
            // Resetar estado de bloqueio
            box.classList.remove('locked');
            if (premiumLockOverlay) premiumLockOverlay.style.display = 'none';
            if (inputElement) inputElement.disabled = false;

            if (userPlanType === 'free') {
                box.classList.add('locked');
                if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                if (inputElement) inputElement.disabled = true;
            } else if (userPlanType === 'premium') {
                const boxHasContent = (box.querySelector('input[name^="existing_"]') && box.querySelector('input[name^="existing_"]').value !== '') || (box.querySelector('input[type="file"]') && box.querySelector('input[type="file"]').files.length > 0);
                if (currentMediaCount >= premiumVideoAudioLimit && !boxHasContent) {
                    box.classList.add('locked');
                    if (premiumLockOverlay) premiumLockOverlay.style.display = 'flex';
                    if (inputElement) inputElement.disabled = true;
                }
            }
        });
    });
}


/**
 * Configura a validação de checkboxes para grupos específicos.
 */
function setupCheckboxValidation() {
    const checkboxGroups = [
        { containerId: 'aparencia-checkboxes', feedbackId: 'aparencia-feedback', name: 'aparencia[]' },
        { containerId: 'idiomas-checkboxes', feedbackId: 'idiomas-feedback', name: 'idiomas[]' },
        { containerId: 'locais_atendimento-checkboxes', feedbackId: 'locais_atendimento-feedback', name: 'locais_atendimento[]' },
        { containerId: 'formas_pagamento-checkboxes', feedbackId: 'formas_pagamento-feedback', name: 'formas_pagamento[]' },
        { containerId: 'servicos-checkboxes', feedbackId: 'servicos-feedback', name: 'servicos[]' }
    ];

    checkboxGroups.forEach(group => {
        const container = document.getElementById(group.containerId);
        if (container) {
            const checkboxes = container.querySelectorAll(`input[name="${group.name}"]`);
            checkboxes.forEach(checkbox => {
                checkbox.removeEventListener('change', handleCheckboxChange);
                checkbox.addEventListener('change', handleCheckboxChange);
            });
        }
    });

    function handleCheckboxChange(event) {
        const checkbox = event.target;
        const groupContainer = checkbox.closest('.row'); // O container é a div.row
        if (groupContainer) {
            groupContainer.classList.remove('is-invalid-group');
            const feedbackId = groupContainer.id.replace('-checkboxes', '-feedback');
            const feedbackElement = document.getElementById(feedbackId);
            if (feedbackElement) {
                feedbackElement.textContent = '';
                feedbackElement.style.display = 'none';
            }
        }
    }
}

/**
 * Configura os event listeners para os botões de ação do administrador (Aprovar, Reprovar, Excluir, Ativar, Pausar, Visualizar).
 * @param {string} anuncioId O ID do anúncio.
 * @param {string} anuncianteUserId O ID do usuário anunciante.
 * @param {string} currentAnuncioStatus O status atual do anúncio (ex: 'pending', 'active', 'inactive', 'rejected').
 */
function setupAdminActionButtons(anuncioId, anuncianteUserId, currentAnuncioStatus) {
    console.log('INFO JS: setupAdminActionButtons - Configurando botões de ação do administrador.');
    console.log(`DEBUG JS: setupAdminActionButtons - Anúncio ID: ${anuncioId}, Anunciante User ID: ${anuncianteUserId}, Status: ${currentAnuncioStatus}`);

    const btnApprove = document.getElementById('btnApproveAnuncio');
    const btnReject = document.getElementById('btnRejectAnuncio');
    const btnDelete = document.getElementById('btnDeleteAnuncio');
    const btnActivate = document.getElementById('btnActivateAnuncio');
    const btnDeactivate = document.getElementById('btnDeactivateAnuncio');
    const btnVisualizar = document.getElementById('btnVisualizarAnuncio'); // NOVO: Botão Visualizar

    // Remove listeners antigos para evitar duplicação em navegações SPA
    // É importante remover os listeners antes de adicionar novos, especialmente em SPAs.
    if (btnApprove) btnApprove.removeEventListener('click', btnApprove._clickHandler);
    if (btnReject) btnReject.removeEventListener('click', btnReject._clickHandler);
    if (btnDelete) btnDelete.removeEventListener('click', btnDelete._clickHandler);
    if (btnActivate) btnActivate.removeEventListener('click', btnActivate._clickHandler);
    if (btnDeactivate) btnDeactivate.removeEventListener('click', btnDeactivate._clickHandler);
    // NOVO: Remove listener para o botão Visualizar
    if (btnVisualizar) btnVisualizar.removeEventListener('click', btnVisualizar._clickHandler);


    // Lógica para habilitar/desabilitar e adicionar listeners
    if (btnApprove) {
        const canApprove = currentAnuncioStatus === 'pending' || currentAnuncioStatus === 'inactive' || currentAnuncioStatus === 'rejected';
        window.toggleButtonState(btnApprove, canApprove);
        if (canApprove) {
            const handler = function() {
                window.showConfirmModal('Aprovar Anúncio', 'Tem certeza que deseja APROVAR este anúncio? Ele ficará ativo para o usuário.', () => {
                    performAdminAction('approve', anuncioId, anuncianteUserId);
                });
            };
            btnApprove.addEventListener('click', handler);
            btnApprove._clickHandler = handler;
        }
    }

    if (btnReject) {
        const canReject = currentAnuncioStatus === 'pending' || currentAnuncioStatus === 'active' || currentAnuncioStatus === 'inactive';
        window.toggleButtonState(btnReject, canReject);
        if (canReject) {
            const handler = function() {
                window.showConfirmModal('Reprovar Anúncio', 'Tem certeza que deseja REPROVAR este anúncio? O usuário será notificado.', () => {
                    performAdminAction('reject', anuncioId, anuncianteUserId);
                });
            };
            btnReject.addEventListener('click', handler);
            btnReject._clickHandler = handler;
        }
    }

    if (btnDelete) {
        // O botão de deletar deve estar sempre disponível para o admin, independentemente do status
        window.toggleButtonState(btnDelete, true); 
        const handler = function() {
            window.showConfirmModal('Excluir Anúncio', 'Tem certeza que deseja EXCLUIR este anúncio? Esta ação é irreversível.', () => {
                performAdminAction('delete', anuncioId, anuncianteUserId);
            });
        };
        btnDelete.addEventListener('click', handler);
        btnDelete._clickHandler = handler;
    }

    if (btnActivate) {
        const canActivate = currentAnuncioStatus === 'inactive' || currentAnuncioStatus === 'pending' || currentAnuncioStatus === 'rejected';
        window.toggleButtonState(btnActivate, canActivate);
        if (canActivate) {
            const handler = function() {
                window.showConfirmModal('Ativar Anúncio', 'Tem certeza que deseja ATIVAR este anúncio? Ele voltará a ficar visível publicamente.', () => {
                    performAdminAction('activate', anuncioId, anuncianteUserId);
                });
            };
            btnActivate.addEventListener('click', handler);
            btnActivate._clickHandler = handler;
        }
    }

    if (btnDeactivate) {
        const canDeactivate = currentAnuncioStatus === 'active';
        window.toggleButtonState(btnDeactivate, canDeactivate);
        if (canDeactivate) {
            const handler = function() {
                window.showConfirmModal('Pausar Anúncio', 'Tem certeza que deseja PAUSAR este anúncio? Ele não ficará visível publicamente.', () => {
                    performAdminAction('deactivate', anuncioId, anuncianteUserId);
                });
            };
            btnDeactivate.addEventListener('click', handler);
            btnDeactivate._clickHandler = handler;
        }
    }

    // NOVO: Lógica para o botão "Visualizar Anúncio" para o administrador
    if (btnVisualizar) {
        // Admin sempre pode visualizar um anúncio, independentemente do status
        window.toggleButtonState(btnVisualizar, true);
        // Define o href para a página de visualização do anúncio, usando o ID do anúncio
        btnVisualizar.href = `${window.URLADM}anuncio/visualizarAnuncio?id=${anuncioId}`;
        btnVisualizar.dataset.spa = 'true'; // Garante que a navegação seja via SPA
        // Não é necessário um click handler separado, pois o href e data-spa já cuidam da navegação.
        console.log(`DEBUG JS: btnVisualizarAnuncio configurado para admin. Href: ${btnVisualizar.href}`);
    }
}

/**
 * Realiza a ação do administrador via AJAX.
 * @param {string} action Ação a ser realizada ('approve', 'reject', 'delete', 'activate', 'deactivate').
 * @param {string} anuncioId O ID do anúncio.
 * @param {string} anuncianteUserId O ID do usuário anunciante.
 */
async function performAdminAction(action, anuncioId, anuncianteUserId) {
    console.log(`DEBUG JS: Realizando ação do administrador: ${action} para anúncio ID: ${anuncioId}, usuário: ${anuncianteUserId}`);

    const url = `${window.URLADM}anuncio/${action}Anuncio`; // Ex: anuncio/approveAnuncio
    const formData = new FormData();
    formData.append('anuncio_id', anuncioId);
    formData.append('anunciante_user_id', anuncianteUserId); // Envia o user_id do anunciante

    window.showLoadingModal('Processando...');

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        await window.hideLoadingModal();

        if (result.success) {
            window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
            
            // Atualiza o dataset do body para refletir o novo status do anúncio do ANUNCIANTE
            // Isso é crucial para que a sidebar do anunciante (se ele estiver logado) seja atualizada.
            // Nota: Esta atualização afeta o *seu* body dataset (admin), mas o objetivo é que o backend
            // tenha processado a mudança para o anunciante.
            document.body.dataset.anuncioStatus = result.new_anuncio_status || document.body.dataset.anuncioStatus;
            document.body.dataset.hasAnuncio = result.has_anuncio !== undefined ? (result.has_anuncio ? 'true' : 'false') : document.body.dataset.hasAnuncio;
            
            // Se a ação foi exclusão, redireciona para a dashboard do admin
            if (action === 'delete') {
                setTimeout(() => {
                    window.loadContent(`${window.URLADM}dashboard`, 'dashboard');
                }, 1500);
            } else {
                // Para outras ações (aprovar/reprovar/ativar/pausar), recarrega a página de edição
                // para refletir o status atualizado dos botões.
                setTimeout(() => {
                    window.loadContent(`${window.URLADM}anuncio/editarAnuncio?id=${anuncioId}`, 'anuncio/editarAnuncio');
                }, 1500);
            }

            // A atualização da sidebar do *anunciante* deve ser tratada pelo backend
            // notificando o front-end do anunciante ou por um mecanismo de polling/websocket
            // se o anunciante estiver online. No contexto atual, a chamada abaixo
            // atualizaria a sidebar do *admin* se ele tivesse um anúncio, o que não é o caso.
            // A instrução do usuário é para "sidebar do usuario normal desse anunciante",
            // o que implica uma comunicação entre usuários ou um refresh do lado do anunciante.
            // Por simplicidade, o backend deve garantir a mudança de status no DB, e a sidebar do
            // anunciante será atualizada na próxima vez que ele carregar a dashboard.
            // window.updateAnuncioSidebarLinks(); // Não é necessário aqui, pois afeta o admin.

        } else {
            window.showFeedbackModal('error', result.message || 'Erro ao realizar a ação.', 'Erro!');
        }
    } catch (error) {
        console.error('ERRO JS: Erro na requisição AJAX de ação do administrador:', error);
        await window.hideLoadingModal();
        window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
    }
}
