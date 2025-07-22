// app/adms/assets/js/anuncio.js

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
// FUNÇÕES DE ATUALIZAÇÃO DA SIDEBAR (GLOBALMENTE DISPONÍVEIS)
// Estas funções são definidas no escopo global para serem acessíveis imediatamente após o carregamento do script.
// =================================================================================================

/**
 * Atualiza o estado dos links da sidebar relacionados a anúncios (Criar, Editar, Visualizar, Excluir, Pausar/Ativar).
 * Baseia-se nos atributos `data-has-anuncio` e `data-anuncio-status` do `body`.
 * Esta função é chamada na carga inicial da página e após operações SPA que alteram o estado do anúncio.
 */
window.updateAnuncioSidebarLinks = async function() {
    console.log('DEBUG JS: updateAnuncioSidebarLinks - Iniciado.');
    const bodyDataset = document.body.dataset;
    const hasAnuncio = bodyDataset.hasAnuncio === 'true';
    let anuncioStatus = bodyDataset.anuncioStatus || 'not_found'; // Default para 'not_found'

    console.log('DEBUG JS: updateAnuncioSidebarLinks - Body Dataset:', bodyDataset);

    // Se o status for 'error_fetching', tenta buscar novamente
    if (anuncioStatus === 'error_fetching' || (anuncioStatus === 'not_found' && hasAnuncio)) { // Adicionado condição para hasAnuncio
        console.log('DEBUG JS: updateAnuncioSidebarLinks - Tentando buscar status do anúncio novamente.');
        const fetchedStatus = await window.fetchAndApplyAnuncioStatus(); // Usar window.fetchAndApplyAnuncioStatus
        if (fetchedStatus) {
            anuncioStatus = fetchedStatus;
            document.body.dataset.anuncioStatus = anuncioStatus; // Atualiza o dataset do body
        } else {
            console.warn('AVISO JS: Não foi possível buscar o status do anúncio. Mantendo o estado atual.');
        }
    }

    const navCriarAnuncio = document.getElementById('navCriarAnuncio');
    const navEditarAnuncio = document.getElementById('navEditarAnuncio');
    const navVisualizarAnuncio = document.getElementById('navVisualizarAnuncio');
    const navExcluirAnuncio = document.getElementById('navExcluirAnuncio');
    const navPausarAnuncio = document.getElementById('navPausarAnuncio'); // Botão de Pausar/Ativar

    // Lógica para o link "Criar Anúncio"
    if (navCriarAnuncio) {
        const isDisabled = hasAnuncio; // Desabilita se já tem anúncio
        navCriarAnuncio.classList.toggle('disabled', isDisabled);
        navCriarAnuncio.style.opacity = isDisabled ? '0.5' : '1';
        navCriarAnuncio.style.pointerEvents = isDisabled ? 'none' : 'auto';
        console.log('DEBUG JS: navCriarAnuncio disabled (via JS):', isDisabled);
    }

    // Lógica para "Editar Anúncio", "Visualizar Anúncio", "Excluir Anúncio"
    // Eles devem estar habilitados APENAS se houver um anúncio existente
    const enableExistingAnuncioLinks = hasAnuncio;
    [navEditarAnuncio, navVisualizarAnuncio, navExcluirAnuncio].forEach(link => {
        if (link) {
            link.classList.toggle('disabled', !enableExistingAnuncioLinks);
            link.style.opacity = !enableExistingAnuncioLinks ? '0.5' : '1';
            link.style.pointerEvents = !enableExistingAnuncioLinks ? 'none' : 'auto';
        }
    });
    console.log('DEBUG JS: navEditarAnuncio, navVisualizarAnuncio, navExcluirAnuncio disabled (via JS):', !enableExistingAnuncioLinks);


    // Lógica para o botão "Pausar/Ativar Anúncio"
    if (navPausarAnuncio) {
        let canInteract = hasAnuncio && (anuncioStatus === 'active' || anuncioStatus === 'inactive' || anuncioStatus === 'pending');
        let iconClass = 'fas fa-pause-circle';
        let buttonText = 'Pausar Anúncio';
        let buttonColorClass = 'btn-info'; // Cor padrão

        switch (anuncioStatus) {
            case 'active':
                iconClass = 'fas fa-pause-circle';
                buttonText = 'Pausar Anúncio';
                buttonColorClass = 'btn-warning'; // Amarelo para pausar
                break;
            case 'inactive':
                iconClass = 'fas fa-play-circle';
                buttonText = 'Ativar Anúncio';
                buttonColorClass = 'btn-success'; // Verde para ativar
                break;
            case 'pending':
                iconClass = 'fas fa-clock';
                buttonText = 'Anúncio Pendente';
                buttonColorClass = 'btn-secondary'; // Cinza para pendente
                canInteract = false; // Não pode interagir se pendente
                break;
            case 'rejected':
                iconClass = 'fas fa-times-circle';
                buttonText = 'Anúncio Rejeitado';
                buttonColorClass = 'btn-danger';
                canInteract = false;
                break;
            case 'not_found':
            case 'error_fetching':
            default:
                iconClass = 'fas fa-exclamation-circle';
                buttonText = 'Status Desconhecido';
                buttonColorClass = 'btn-secondary';
                canInteract = false;
                break;
        }

        // Atualiza o ícone e texto do botão
        const iconElement = navPausarAnuncio.querySelector('i');
        if (iconElement) {
            iconElement.className = iconClass + ' me-2'; // Adiciona 'me-2' para espaçamento
        }
        const textNode = Array.from(navPausarAnuncio.childNodes).find(node => node.nodeType === Node.TEXT_NODE);
        if (textNode) {
            textNode.nodeValue = buttonText;
        } else {
            // Se não houver um textNode, cria um ou atualiza o innerHTML (menos ideal, mas funciona)
            navPausarAnuncio.innerHTML = `<i class="${iconClass} me-2"></i>${buttonText}`;
        }

        // Remove todas as classes de cor de botão e adiciona a correta
        navPausarAnuncio.classList.remove('btn-primary', 'btn-success', 'btn-warning', 'btn-danger', 'btn-info', 'btn-secondary');
        navPausarAnuncio.classList.add(buttonColorClass);

        // Habilita/desabilita o botão
        navPausarAnuncio.classList.toggle('disabled', !canInteract);
        navPausarAnuncio.style.opacity = !canInteract ? '0.5' : '1';
        navPausarAnuncio.style.pointerEvents = !canInteract ? 'none' : 'auto';

        console.log(`DEBUG JS: navPausarAnuncio status: ${anuncioStatus} canInteract: ${canInteract}`);
    }
    console.log(`INFO JS: Sidebar links atualizados. Has Anuncio: ${hasAnuncio} Anuncio Status: ${anuncioStatus}`);
};

/**
 * Busca o status do anúncio do servidor e atualiza o dataset do body.
 * @returns {Promise<string|null>} O status do anúncio ou null em caso de erro.
 */
window.fetchAndApplyAnuncioStatus = async function() { // Globalizada para ser acessível por dashboard_anuncios.js
    const userId = document.body.dataset.userId; // Certifique-se de que o body tem data-user-id
    if (!userId) {
        console.error('ERRO JS: fetchAndApplyAnuncioStatus - User ID não encontrado no dataset do body.');
        return null;
    }

    console.log(`DEBUG JS: fetchAndApplyAnuncioStatus - Buscando status atual do anúncio ID: ${userId}. Requisição com ajax_data_only=true.`);
    try {
        const response = await fetch(`${window.URLADM}anuncio/visualizarAnuncio?ajax_data_only=true`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('ERRO JS: fetchAndApplyAnuncioStatus - Resposta de rede não OK:', response.status, response.statusText, errorText);
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }

        const data = await response.json(); // Tenta parsear como JSON

        if (data.success && data.anuncio) {
            document.body.dataset.anuncioStatus = data.anuncio.status;
            document.body.dataset.hasAnuncio = 'true'; // Garante que hasAnuncio seja true se um anúncio for encontrado
            document.body.dataset.anuncioId = data.anuncio.id; // Adicionado para ter o ID do anúncio
            console.log('INFO JS: fetchAndApplyAnuncioStatus - Status do anúncio atualizado:', data.anuncio.status);
            return data.anuncio.status;
        } else {
            document.body.dataset.anuncioStatus = 'not_found';
            document.body.dataset.hasAnuncio = 'false';
            document.body.dataset.anuncioId = ''; // Limpa o ID se não houver anúncio
            console.warn('AVISO JS: fetchAndApplyAnuncioStatus - Anúncio não encontrado ou dados incompletos:', data.message);
            return 'not_found';
        }
    } catch (error) {
        console.error('ERRO JS: fetchAndApplyAnuncioStatus - Erro ao buscar status do anúncio:', error);
        document.body.dataset.anuncioStatus = 'error_fetching';
        document.body.dataset.hasAnuncio = 'false'; // Assume que não tem anúncio ou não conseguiu verificar
        document.body.dataset.anuncioId = ''; // Limpa o ID em caso de erro
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
    console.log('INFO JS: initializePerfilPage (Versão 203) - Iniciando inicialização da página de perfil.');
    // Lógica específica para a página de perfil
    const perfilForm = document.getElementById('formPerfil');
    if (perfilForm) {
        console.log('DEBUG JS: Formulário de perfil encontrado. Configurando validação e máscaras.');
        setupFormValidation(perfilForm);
        setupPhoneMask(document.getElementById('phone_number'));
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
 */
window.initializeAnuncioFormPage = async function() {
    console.log('INFO JS: initializeAnuncioFormPage (Versão Corrigida - Mídias) - Iniciando inicialização da página de formulário de anúncio.');

    const formAnuncio = document.getElementById('formCriarAnuncio');
    if (!formAnuncio) {
        console.warn('AVISO JS: Formulário de anúncio (ID "formCriarAnuncio") não encontrado. Ignorando inicialização do formulário.');
        return;
    }

    // Recupera os dados passados do PHP via data-anuncio-data
    const anuncioData = JSON.parse(formAnuncio.dataset.anuncioData || '{}');
    const formMode = formAnuncio.dataset.formMode; // 'create' ou 'edit'
    const userPlanType = formAnuncio.dataset.userPlanType;

    console.log('DEBUG JS: initializeAnuncioFormPage - Modo do Formulário:', formMode);
    console.log('DEBUG JS: initializeAnuncioFormPage - Tipo de Plano do Usuário:', userPlanType);
    console.log('DEBUG JS: initializeAnuncioFormPage - Dados do Anúncio (parcial):', Object.keys(anuncioData).slice(0, 5).map(key => `${key}: ${anuncioData[key]}`));


    // Atualiza o título e a cor do cabeçalho do card
    const cardHeader = document.querySelector('.card-header');
    const formTitleElement = document.getElementById('formAnuncioTitle');
    const btnSubmitAnuncio = document.getElementById('btnSubmitAnuncio');

    if (cardHeader && formTitleElement && btnSubmitAnuncio) {
        if (formMode === 'edit') {
            formTitleElement.innerHTML = '<i class="fas fa-edit me-2"></i>EDITAR ANÚNCIO';
            btnSubmitAnuncio.innerHTML = '<i class="fas fa-save me-2"></i>ATUALIZAR ANÚNCIO';
            cardHeader.classList.remove('bg-primary');
            cardHeader.classList.add('bg-warning', 'text-dark'); // Amarelo para edição
            btnSubmitAnuncio.classList.remove('btn-primary');
            btnSubmitAnuncio.classList.add('btn-warning');
        } else {
            formTitleElement.innerHTML = '<i class="fas fa-plus-circle me-2"></i>CRIAR NOVO ANÚNCIO';
            btnSubmitAnuncio.innerHTML = '<i class="fas fa-plus-circle me-2"></i>CRIAR ANÚNCIO';
            cardHeader.classList.remove('bg-warning', 'text-dark');
            cardHeader.classList.add('bg-primary', 'text-white'); // Azul para criação
            btnSubmitAnuncio.classList.remove('btn-warning');
            btnSubmitAnuncio.classList.add('btn-primary');
        }
        console.log('DEBUG JS: initializeAnuncioFormPage - Cores do cabeçalho e botão aplicadas dinamicamente.');
    } else {
        console.warn('AVISO JS: Elementos de cabeçalho, título ou botão do formulário de anúncio não encontrados.');
    }


    // 1. Configuração de Validação do Formulário
    setupFormValidation(formAnuncio);

    // 2. Máscaras de Input
    setupInputMasks();

    // 3. Carregar e pré-selecionar estados, cidades e bairros
    await loadAndPopulateLocations(anuncioData);

    // 4. Lógica de validação de checkboxes (Serviços, Aparência, Idiomas, Locais, Pagamentos)
    setupCheckboxValidation();

    // 5. Inicializa as pré-visualizações de mídias existentes (NOVA FUNÇÃO)
    initializeFormMediaPreviews(anuncioData, formMode, userPlanType);

    // 6. Configura os manipuladores de upload de fotos e vídeos (AGORA APENAS EVENT LISTENERS)
    setupFileUploadHandlers(formAnuncio, anuncioData, formMode, userPlanType);


    // Re-inicializa os alertas automáticos após o carregamento da página
    if (typeof window.setupAutoDismissAlerts === 'function') {
        window.setupAutoDismissAlerts();
    }
    console.log('INFO JS: initializeAnuncioFormPage - Finalizado.');
};

/**
 * Inicializa a página de visualização de anúncio.
 * Esta função é chamada pelo dashboard_custom.js quando a rota 'anuncio/visualizar' é detectada.
 * @param {string|null} anuncioIdFromUrl O ID do anúncio extraído da URL, se disponível.
 */
window.initializeVisualizarAnuncioPage = async function(anuncioIdFromUrl = null) {
    console.log('INFO JS: initializeVisualizarAnuncioPage (Versão Corrigida - Botão) - Iniciando inicialização da página de visualização.');
    console.log('DEBUG JS: initializeVisualizarAnuncioPage - anuncioIdFromUrl recebido:', anuncioIdFromUrl);

    let cardElementInitial = document.querySelector('[data-page-type="view"]');
    console.log('DEBUG JS: initializeVisualizarAnuncioPage - Card element (initial check):', cardElementInitial);
    if (!cardElementInitial) {
        console.info('INFO JS: initializeVisualizarAnuncioPage - Card com data-page-type="view" não encontrado (inicial). Ignorando inicialização da visualização.');
        return;
    }

    // Atualiza a cor do cabeçalho do card
    const cardHeader = cardElementInitial.querySelector('.card-header');
    const formTitleElement = cardElementInitial.querySelector('#formAnuncioTitle');
    if (cardHeader && formTitleElement) {
        formTitleElement.innerHTML = '<i class="fas fa-eye me-2"></i>Detalhes do Anúncio';
        cardHeader.classList.remove('bg-warning', 'text-dark');
        cardHeader.classList.add('bg-primary', 'text-white'); // Azul para visualização
        console.log('DEBUG JS: initializeVisualizarAnuncioPage - Elementos de cabeçalho e título encontrados para visualização. Aplicando cores dinâmicas.');
    } else {
        console.warn('AVISO JS: Elementos de cabeçalho ou título da página de visualização não encontrados.');
    }

    let cardElementAfterColor = document.querySelector('[data-page-type="view"]');
    console.log('DEBUG JS: initializeVisualizarAnuncioPage - Card element (after color apply):', cardElementAfterColor);
    if (!cardElementAfterColor) {
        console.info('INFO JS: initializeVisualizarAnuncioPage - Card com data-page-type="view" não encontrado (após cor). Isso é inesperado.');
        return;
    }


    // Tenta obter o ID do anúncio do atributo data-anuncio-id do próprio card
    const currentAnuncioId = cardElementInitial.dataset.anuncioId || anuncioIdFromUrl;

    if (!currentAnuncioId || currentAnuncioId === 'http:') {
        console.error('ERRO JS: initializeVisualizarAnuncioPage - ID do anúncio não encontrado ou inválido para visualização.');
        window.showFeedbackModal('error', 'Não foi possível carregar os detalhes do anúncio. ID inválido.', 'Erro de Visualização');
        return;
    }
    console.log('DEBUG JS: initializeVisualizarAnuncioPage - currentAnuncioId definido como:', currentAnuncioId);

    if (typeof window.setupAutoDismissAlerts === 'function') {
        window.setupAutoDismissAlerts();
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
    form.removeEventListener('submit', handleFormSubmit); // Remove para evitar duplicação
    form.addEventListener('submit', handleFormSubmit);
}

async function handleFormSubmit(event) {
    event.preventDefault(); // Previne o envio padrão do formulário
    event.stopPropagation(); // Impede a propagação do evento

    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');

    // Limpa feedback de validação anterior
    Array.from(form.querySelectorAll('.is-invalid')).forEach(el => el.classList.remove('is-invalid'));
    Array.from(form.querySelectorAll('.invalid-feedback')).forEach(el => el.textContent = '');
    Array.from(form.querySelectorAll('.text-danger.small.mt-2')).forEach(el => el.textContent = '');
    // Remove a classe de erro visual para boxes de upload
    Array.from(form.querySelectorAll('.photo-upload-box.is-invalid-media')).forEach(el => el.classList.remove('is-invalid-media'));


    // Validação HTML5 nativa (para campos que ainda têm 'required' no HTML, como selects e inputs de texto)
    if (!form.checkValidity()) {
        console.warn('AVISO JS: Formulário inválido. Exibindo feedback de validação HTML5.');
        Array.from(form.querySelectorAll(':invalid')).forEach(el => {
            el.classList.add('is-invalid');
            const feedbackDiv = el.nextElementSibling;
            if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                feedbackDiv.textContent = el.validationMessage;
            }
        });
        form.querySelector(':invalid')?.focus();
        window.showFeedbackModal('error', 'Por favor, preencha todos os campos obrigatórios.', 'Erro de Validação!');
        return;
    }

    // Validação personalizada para checkboxes de Aparência, Idiomas, Locais, Pagamentos, Serviços
    let isValidCheckboxes = true;
    const checkboxGroups = [
        { name: 'aparencia[]', min: 1, feedbackId: 'aparencia-feedback', message: 'Por favor, selecione pelo menos 1 item de aparência.' },
        { name: 'idiomas[]', min: 1, feedbackId: 'idiomas-feedback', message: 'Por favor, selecione pelo menos 1 idioma.' },
        { name: 'locais_atendimento[]', min: 1, feedbackId: 'locais-feedback', message: 'Por favor, selecione pelo menos 1 local de atendimento.' },
        { name: 'formas_pagamento[]', min: 1, feedbackId: 'pagamentos-feedback', message: 'Por favor, selecione pelo menos 1 forma de pagamento.' },
        { name: 'servicos[]', min: 2, feedbackId: 'servicos-feedback', message: 'Por favor, selecione pelo menos 2 serviços.' }
    ];

    checkboxGroups.forEach(group => {
        const checkboxes = form.querySelectorAll(`input[name="${group.name}"]`);
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const feedbackElement = document.getElementById(group.feedbackId);

        if (checkedCount < group.min) {
            feedbackElement.textContent = group.message;
            isValidCheckboxes = false;
        } else {
            feedbackElement.textContent = '';
        }
    });

    // Validação de Preços (pelo menos um preenchido)
    let isValidPrices = true;
    const price15minInput = document.getElementById('price_15min');
    const price30minInput = document.getElementById('price_30min');
    const price1hInput = document.getElementById('price_1h');
    const pricesFeedback = document.getElementById('precos-feedback');

    const rawPrice15min = price15minInput ? parseFloat(price15minInput.value.replace(/\./g, '').replace(',', '.')) : NaN;
    const rawPrice30min = price30minInput ? parseFloat(price30minInput.value.replace(/\./g, '').replace(',', '.')) : NaN;
    const rawPrice1h = price1hInput ? parseFloat(price1hInput.value.replace(/\./g, '').replace(',', '.')) : NaN;

    if ((isNaN(rawPrice15min) || rawPrice15min <= 0) &&
        (isNaN(rawPrice30min) || rawPrice30min <= 0) &&
        (isNaN(rawPrice1h) || rawPrice1h <= 0)) {
        pricesFeedback.textContent = 'Pelo menos um preço deve ser preenchido com um valor maior que zero.';
        isValidPrices = false;
    } else {
        pricesFeedback.textContent = '';
    }

    // Validação de arquivos de mídia (Vídeo de Confirmação e Foto da Capa)
    let isValidMedia = true;
    const confirmationVideoInput = document.getElementById('confirmation_video_input');
    const coverPhotoInput = document.getElementById('foto_capa_input');
    const confirmationVideoFeedback = document.getElementById('confirmationVideo-feedback');
    const coverPhotoFeedback = document.getElementById('coverPhoto-feedback');

    // Para o vídeo de confirmação:
    const hasNewConfirmationVideo = confirmationVideoInput?.files?.length > 0;
    const existingConfirmationVideoPathInput = document.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]');
    const hasExistingConfirmationVideo = existingConfirmationVideoPathInput && existingConfirmationVideoPathInput.value !== '';
    const confirmationVideoRemoved = document.getElementById('confirmation_video_removed')?.value === 'true';

    // A validação deve ser: se não há novo vídeo E não há vídeo existente E (está em modo de criação OU o vídeo existente foi removido)
    if ((!hasNewConfirmationVideo && !hasExistingConfirmationVideo) &&
        (form.dataset.formMode === 'create' || confirmationVideoRemoved)) {
        confirmationVideoFeedback.textContent = 'O vídeo de confirmação é obrigatório.';
        document.getElementById('confirmationVideoUploadBox').classList.add('is-invalid-media');
        isValidMedia = false;
    } else {
        confirmationVideoFeedback.textContent = '';
        document.getElementById('confirmationVideoUploadBox').classList.remove('is-invalid-media');
    }

    // Para a foto da capa:
    const hasNewCoverPhoto = coverPhotoInput?.files?.length > 0;
    const existingCoverPhotoPathInput = document.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]');
    const hasExistingCoverPhoto = existingCoverPhotoPathInput && existingCoverPhotoPathInput.value !== '';
    const coverPhotoRemoved = document.getElementById('cover_photo_removed')?.value === 'true';

    // A validação deve ser: se não há nova foto E não há foto existente E (está em modo de criação OU a foto existente foi removida)
    if ((!hasNewCoverPhoto && !hasExistingCoverPhoto) &&
        (form.dataset.formMode === 'create' || coverPhotoRemoved)) {
        coverPhotoFeedback.textContent = 'A foto da capa é obrigatória.';
        document.getElementById('coverPhotoUploadBox').classList.add('is-invalid-media');
        isValidMedia = false;
    } else {
        coverPhotoFeedback.textContent = '';
        document.getElementById('coverPhotoUploadBox').classList.remove('is-invalid-media');
    }


    // Validação da Galeria de Fotos - AQUI É ONDE O LIMITE DE 1 FOTO PARA PLANO GRÁTIS É VALIDADO
    let currentValidGalleryPhotos = 0;
    document.querySelectorAll('.gallery-upload-box').forEach((box) => {
        const input = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]'); // Este input existe no HTML

        const hasExisting = existingPathInput && existingPathInput.value !== ''; // Verifica se o caminho existente não está vazio
        const isNew = input.files.length > 0;

        // Uma foto é válida se:
        // 1. É um novo upload
        // OU
        // 2. É uma foto existente que NÃO foi "removida" (ou seja, seu existingPathInput.value não está vazio)
        if (isNew || hasExisting) {
            currentValidGalleryPhotos++;
        }
        console.log(`DEBUG JS: Galeria - Slot (box): hasExisting=${hasExisting}, isNew=${isNew}, currentValidGalleryPhotos=${currentValidGalleryPhotos}`);
    });

    console.log('DEBUG JS: Galeria - Total de fotos válidas na galeria (calculado):', currentValidGalleryPhotos);
    console.log('DEBUG JS: Galeria - Tipo de plano do usuário (form.dataset.userPlanType):', form.dataset.userPlanType);

    const minPhotosRequired = 1; // Pelo menos 1 foto na galeria para qualquer plano
    const freePhotoLimit = 1;    // Limite de 1 foto para plano gratuito
    const premiumPhotoLimit = 20; // Limite de 20 fotos para plano premium

    const galleryFeedbackElement = document.getElementById('galleryPhotoContainer-feedback');

    if (currentValidGalleryPhotos < minPhotosRequired) {
        galleryFeedbackElement.textContent = `Mínimo de ${minPhotosRequired} foto(s) na galeria.`;
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'free' && currentValidGalleryPhotos > freePhotoLimit) {
        galleryFeedbackElement.textContent = `Seu plano gratuito permite apenas ${freePhotoLimit} foto na galeria.`;
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'premium' && currentValidGalleryPhotos > premiumPhotoLimit) {
        galleryFeedbackElement.textContent = `Seu plano premium permite no máximo ${premiumPhotoLimit} fotos na galeria.`;
        isValidMedia = false;
    } else {
        galleryFeedbackElement.textContent = '';
    }

    // Validação de Vídeos
    const videoFeedbackElement = document.getElementById('videoUploadBoxes-feedback');

    const currentValidVideos = Array.from(document.querySelectorAll('.video-upload-box')).filter(box => {
        const input = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_video_paths[]"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = input.files.length > 0;
        return isNew || hasExisting;
    }).length;

    if (form.dataset.userPlanType === 'free' && currentValidVideos > 0) {
        videoFeedbackElement.textContent = 'Vídeos são permitidos apenas para planos pagos.';
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'premium' && currentValidVideos > 3) {
        videoFeedbackElement.textContent = 'Limite de 3 vídeos para plano premium.';
        isValidMedia = false;
    } else {
        videoFeedbackElement.textContent = '';
    }

    // Validação de Áudios
    const audioFeedbackElement = document.getElementById('audioUploadBoxes-feedback');

    const currentValidAudios = Array.from(document.querySelectorAll('.audio-upload-box')).filter(box => {
        const input = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_audio_paths[]"]');

        const hasExisting = existingPathInput && existingPathInput.value !== '';
        const isNew = input.files.length > 0;
        return isNew || hasExisting;
    }).length;

    if (form.dataset.userPlanType === 'free' && currentValidAudios > 0) {
        audioFeedbackElement.textContent = 'Áudios são permitidos apenas para planos pagos.';
        isValidMedia = false;
    } else if (form.dataset.userPlanType === 'premium' && currentValidAudios > 3) {
        audioFeedbackElement.textContent = 'Limite de 3 áudios para plano premium.';
        isValidMedia = false;
    } else {
        audioFeedbackElement.textContent = '';
    }


    console.log('DEBUG JS: Validação Personalizada - isValidCheckboxes:', isValidCheckboxes);
    console.log('DEBUG JS: Validação Personalizada - isValidPrices:', isValidPrices);
    console.log('DEBUG JS: Validação Personalizada - isValidMedia:', isValidMedia);


    if (!isValidCheckboxes || !isValidPrices || !isValidMedia) {
        console.warn('AVISO JS: Validação personalizada falhou. O formulário NÃO será enviado.');
        // Encontra o primeiro elemento com erro para rolar até ele
        const firstInvalidElement = form.querySelector('.is-invalid, .is-invalid-media, .is-invalid-group');
        if (firstInvalidElement) {
            firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        window.showFeedbackModal('error', 'Por favor, corrija os erros no formulário.', 'Erro de Validação!');
        return;
    }

    console.log('DEBUG JS: Todas as validações passaram. Preparando para enviar o formulário via AJAX.');
    // Se todas as validações passarem, envia o formulário via AJAX
    submitAnuncioForm(form);
}


/**
 * Envia o formulário de anúncio via AJAX.
 * @param {HTMLFormElement} form O formulário a ser enviado.
 */
async function submitAnuncioForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonHTML = window.activateButtonLoading(submitButton, 'Salvando...'); // Ativa spinner no botão

    window.showLoadingModal(); // Mostra modal de carregamento

    const formData = new FormData(); // Crie um novo FormData para controlar o que é adicionado

    // Adiciona todos os campos do formulário, exceto os de arquivo e os hidden de controle de mídia
    Array.from(form.elements).forEach(element => {
        // Exclui especificamente os hidden inputs de existing_paths e removed_paths
        // Eles serão tratados separadamente para fotos de galeria, vídeos e áudios
        // E também para capa e vídeo de confirmação.
        if (element.name && element.type !== 'file' &&
            !element.name.startsWith('existing_gallery_paths') &&
            !element.name.startsWith('existing_video_paths') &&
            !element.name.startsWith('existing_audio_paths') &&
            // Não precisamos mais de removed_paths para galeria/video/audio
            !element.name.startsWith('removed_gallery_paths') && // Este não é mais usado no HTML
            !element.name.startsWith('removed_video_paths') && // Este não é mais usado no HTML
            !element.name.startsWith('removed_audio_paths') && // Este não é mais usado no HTML
            // Capa e vídeo de confirmação são tratados abaixo
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

    // --- TRATAMENTO ESPECÍFICO PARA PESO E ALTURA ANTES DO ENVIO ---
    let peso = formData.get('peso');
    if (peso) {
        peso = parseInt(peso.replace(/\D/g, ''), 10);
        if (isNaN(peso)) {
            peso = null;
        }
        formData.set('peso', peso);
    }

    let altura = formData.get('altura');
    if (altura) {
        // Converte para o formato numérico esperado pelo backend (ex: 1.75)
        altura = altura.replace(',', '.');
        if (isNaN(parseFloat(altura))) {
            altura = null;
        }
        formData.set('altura', altura);
    }
    // --- FIM DO TRATAMENTO ESPECÍFICO ---

    formData.append('form_mode', form.dataset.formMode);

    // --- TRATAMENTO DA FOTO DE CAPA ---
    const fotoCapaInput = document.getElementById('foto_capa_input');
    const existingCoverPhotoPathInput = form.querySelector('#coverPhotoUploadBox input[name="existing_cover_photo_path"]');
    const coverPhotoRemovedInput = document.getElementById('cover_photo_removed');

    if (fotoCapaInput && fotoCapaInput.files.length > 0) {
        formData.append('foto_capa', fotoCapaInput.files[0]);
        // Se uma nova foto é enviada, não precisamos enviar o caminho existente ou o flag de remoção
    } else if (coverPhotoRemovedInput && coverPhotoRemovedInput.value === 'true') {
        // Se a foto existente foi marcada para remoção
        formData.append('cover_photo_removed', 'true');
    } else if (existingCoverPhotoPathInput && existingCoverPhotoPathInput.value) {
        // Se não há nova foto e a existente não foi marcada para remoção, mantém o caminho existente
        formData.append('existing_cover_photo_path', existingCoverPhotoPathInput.value);
    }
    // Se não houver nenhum dos casos acima (ex: campo vazio em criação), não adiciona ao formData.

    // --- TRATAMENTO DO VÍDEO DE CONFIRMAÇÃO ---
    const confirmationVideoInput = document.getElementById('confirmation_video_input');
    const existingConfirmationVideoPathInput = form.querySelector('#confirmationVideoUploadBox input[name="existing_confirmation_video_path"]');
    const confirmationVideoRemovedInput = document.getElementById('confirmation_video_removed');

    if (confirmationVideoInput && confirmationVideoInput.files.length > 0) {
        formData.append('confirmation_video', confirmationVideoInput.files[0]);
    } else if (confirmationVideoRemovedInput && confirmationVideoRemovedInput.value === 'true') {
        formData.append('confirmation_video_removed', 'true');
    } else if (existingConfirmationVideoPathInput && existingConfirmationVideoPathInput.value) {
        formData.append('existing_confirmation_video_path', existingConfirmationVideoPathInput.value);
    }


    // --- LÓGICA CORRIGIDA PARA FOTOS DA GALERIA ---
    const galleryPhotoContainers = document.querySelectorAll('.gallery-upload-box');
    galleryPhotoContainers.forEach((box) => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');

        if (fileInput && fileInput.files.length > 0) {
            formData.append('fotos_galeria[]', fileInput.files[0]);
        } else if (existingPathInput && existingPathInput.value) {
            formData.append('existing_gallery_paths[]', existingPathInput.value);
        }
    });

    // --- LÓGICA CORRIGIDA PARA VÍDEOS DA GALERIA ---
    const videoContainers = document.querySelectorAll('.video-upload-box');
    videoContainers.forEach((box) => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_video_paths[]"]');

        if (fileInput && fileInput.files.length > 0) {
            formData.append('videos[]', fileInput.files[0]);
        } else if (existingPathInput && existingPathInput.value) {
            formData.append('existing_video_paths[]', existingPathInput.value);
        }
    });

    // --- LÓGICA CORRIGIDA PARA ÁUDIOS DA GALERIA ---
    const audioContainers = document.querySelectorAll('.audio-upload-box');
    audioContainers.forEach((box) => {
        const fileInput = box.querySelector('input[type="file"]');
        const existingPathInput = box.querySelector('input[name="existing_audio_paths[]"]');

        if (fileInput && fileInput.files.length > 0) {
            formData.append('audios[]', fileInput.files[0]);
        } else if (existingPathInput && existingPathInput.value) {
            formData.append('existing_audio_paths[]', existingPathInput.value);
        }
    });


    // --- LOGS DE DEBUG PARA FormData ---
    console.log('DEBUG JS: Conteúdo do FormData antes do envio:');
    for (let pair of formData.entries()) {
        if (pair[1] instanceof File) {
            console.log(`    ${pair[0]}: File - ${pair[1].name} (${pair[1].type})`);
        } else {
            console.log(`    ${pair[0]}: ${pair[1]}`);
        }
    }
    // --- FIM DOS LOGS DE DEBUG PARA FormData ---


    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        // ATRASO PARA O SPINNER, DEPOIS ESCONDE CARREGAMENTO E MOSTRA FEEDBACK
        setTimeout(() => { // Atraso de 1 segundo para o spinner
            window.hideLoadingModal(); // Esconde o modal de carregamento
            window.deactivateButtonLoading(submitButton, originalButtonHTML); // Desativa spinner do botão

            console.log('INFO JS: Spinner ocultado. Mostrando modal de feedback.');

            if (result.success) {
                window.showFeedbackModal('success', result.message, 'Sucesso!', 3000);
                // Atualiza o dataset do body após sucesso na criação/edição
                document.body.dataset.hasAnuncio = result.has_anuncio ? 'true' : 'false';
                document.body.dataset.anuncioStatus = result.anuncio_status || 'not_found';
                document.body.dataset.anuncioId = result.anuncio_id || ''; // Atualiza o ID do anúncio
                window.updateAnuncioSidebarLinks(); // Atualiza a sidebar
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1500); // Pequeno atraso para o usuário ver o feedback antes do redirecionamento
                }
            } else {
                let errorMessage = result.message || 'Ocorreu um erro ao processar o anúncio.';
                if (result.errors) {
                    for (const field in result.errors) {
                        const feedbackElement = document.getElementById(`${field}-feedback`);
                        if (feedbackElement) {
                            feedbackElement.textContent = result.errors[field];
                            const inputElement = document.getElementById(field);
                            if (inputElement) {
                                inputElement.classList.add('is-invalid');
                            }
                            // Adiciona classe de erro visual para boxes de upload
                            // Os IDs aqui devem corresponder aos IDs dos containers, não dos feedbacks
                            const uploadBoxIdMap = {
                                'confirmationVideo': 'confirmationVideoUploadBox',
                                'coverPhoto': 'coverPhotoUploadBox',
                                'galleryPhotoContainer': 'galleryPhotoContainer',
                                'videoUploadBoxes': 'videoUploadBoxes',
                                'audioUploadBoxes': 'audioUploadBoxes'
                            };
                            const uploadBox = document.getElementById(uploadBoxIdMap[field] || field); // Usa o ID mapeado ou o próprio field
                            if (uploadBox) {
                                uploadBox.classList.add('is-invalid-media');
                            }
                        } else {
                            errorMessage += `\n- ${result.errors[field]}`;
                        }
                    }
                }
                window.showFeedbackModal('error', errorMessage, 'Erro!');
            }
        }, 1000); // 1 segundo de atraso para o spinner
    } catch (error) {
        console.error('ERRO JS: Erro ao enviar formulário de anúncio:', error);
        setTimeout(() => { // Atraso de 1 segundo para o spinner
            window.hideLoadingModal();
            window.deactivateButtonLoading(submitButton, originalButtonHTML);
            window.showFeedbackModal('error', `Falha na comunicação com o servidor: ${error.message}.`, 'Erro de Rede');
        }, 1000); // 1 segundo de atraso para o spinner
    }
}


/**
 * Configura máscaras para campos de input.
 */
function setupInputMasks() {
    // Máscara para Altura (formato X,XX - 3 dígitos no total)
    const alturaInput = document.getElementById('altura');
    if (alturaInput) {
        alturaInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito

            if (value.length === 0) {
                e.target.value = '';
                return;
            }

            // Limita a 3 dígitos
            if (value.length > 3) {
                value = value.substring(0, 3);
            }

            // Formata para X,XX
            if (value.length === 1) {
                e.target.value = value + ','; // Ex: "1" -> "1,"
            } else if (value.length === 2) {
                e.target.value = value.substring(0, 1) + ',' + value.substring(1, 2); // Ex: "17" -> "1,7"
            } else if (value.length === 3) {
                e.target.value = value.substring(0, 1) + ',' + value.substring(1, 3); // Ex: "175" -> "1,75"
            } else {
                e.target.value = value; // Para o caso de 0 dígitos (vazio)
            }
        });

        // Garante que o valor inicial também seja formatado se houver
        if (alturaInput.value) {
            alturaInput.dispatchEvent(new Event('input'));
        }
    }

    // Máscara para Peso (apenas números inteiros)
    const pesoInput = document.getElementById('peso');
    if (pesoInput) {
        pesoInput.addEventListener('input', function(e) {
            let value = e.target.value;
            value = value.replace(/\D/g, '');
            e.target.value = value;
        });
    }

    // Máscara para Preços (R$ 0.000,00)
    const priceInputs = document.querySelectorAll('input[name^="precos["]');
    priceInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length === 0) {
                e.target.value = '';
                return;
            }

            let cents = parseInt(value, 10);
            let formattedValue = (cents / 100).toFixed(2);

            formattedValue = formattedValue.replace('.', ',');

            let parts = formattedValue.split(',');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            formattedValue = parts.join(',');

            e.target.value = formattedValue;
        });
    });
}

/**
 * Aplica máscara de telefone ao input fornecido.
 * @param {HTMLInputElement} inputElement
 */
function setupPhoneMask(inputElement) {
    if (inputElement) {
        inputElement.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let maskedValue = '';
            if (value.length > 0) {
                maskedValue = '(' + value.substring(0, 2);
            }
            if (value.length >= 3) {
                maskedValue += ') ' + value.substring(2, 7);
            }
            if (value.length >= 8) {
                maskedValue += '-' + value.substring(7, 11);
            }
            e.target.value = maskedValue;
        });
    }
}


/**
 * Inicializa as pré-visualizações de mídias existentes ao carregar o formulário.
 * Esta função é chamada apenas uma vez na inicialização da página.
 * @param {object} anuncioData Dados do anúncio para pré-preenchimento.
 * @param {string} formMode Modo do formulário ('create' ou 'edit').
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function initializeFormMediaPreviews(anuncioData, formMode, userPlanType) {
    console.info('INFO JS: initializeFormMediaPreviews - Iniciando pré-visualização de mídias existentes.');

    // --- Vídeo de Confirmação ---
    const confirmationVideoUploadBox = document.getElementById('confirmationVideoUploadBox');
    const confirmationVideoPreview = document.getElementById('confirmationVideoPreview');
    const confirmationVideoPlaceholder = confirmationVideoUploadBox.querySelector('.upload-placeholder');
    const confirmationVideoRemoveBtn = confirmationVideoUploadBox.querySelector('.btn-remove-photo');
    const existingConfirmationVideoPathInput = confirmationVideoUploadBox.querySelector('input[name="existing_confirmation_video_path"]');
    const confirmationVideoRemovedInput = document.getElementById('confirmation_video_removed');

    if (formMode === 'edit' && anuncioData.confirmation_video_path) {
        console.debug('DEBUG JS: Carregando vídeo de confirmação existente:', anuncioData.confirmation_video_path);
        confirmationVideoPreview.src = anuncioData.confirmation_video_path;
        confirmationVideoPreview.style.display = 'block';
        confirmationVideoPlaceholder.style.display = 'none';
        confirmationVideoRemoveBtn.classList.remove('d-none');
        if (existingConfirmationVideoPathInput) existingConfirmationVideoPathInput.value = anuncioData.confirmation_video_path;
        if (confirmationVideoRemovedInput) confirmationVideoRemovedInput.value = 'false';
    } else {
        console.debug('DEBUG JS: Nenhum vídeo de confirmação existente ou modo de criação.');
        confirmationVideoPreview.src = '';
        confirmationVideoPreview.style.display = 'none';
        confirmationVideoPlaceholder.style.display = 'flex';
        confirmationVideoRemoveBtn.classList.add('d-none');
        if (existingConfirmationVideoPathInput) existingConfirmationVideoPathInput.value = '';
        if (confirmationVideoRemovedInput) confirmationVideoRemovedInput.value = 'false';
    }

    // --- Foto da Capa ---
    const coverPhotoUploadBox = document.getElementById('coverPhotoUploadBox');
    const coverPhotoPreview = document.getElementById('coverPhotoPreview');
    const coverPhotoPlaceholder = coverPhotoUploadBox.querySelector('.upload-placeholder');
    const coverPhotoRemoveBtn = coverPhotoUploadBox.querySelector('.btn-remove-photo');
    const existingCoverPhotoPathInput = coverPhotoUploadBox.querySelector('input[name="existing_cover_photo_path"]');
    const coverPhotoRemovedInput = document.getElementById('cover_photo_removed');

    if (formMode === 'edit' && anuncioData.cover_photo_path) {
        console.debug('DEBUG JS: Carregando foto de capa existente:', anuncioData.cover_photo_path);
        coverPhotoPreview.src = anuncioData.cover_photo_path;
        coverPhotoPreview.style.display = 'block';
        coverPhotoPlaceholder.style.display = 'none';
        coverPhotoRemoveBtn.classList.remove('d-none');
        if (existingCoverPhotoPathInput) existingCoverPhotoPathInput.value = anuncioData.cover_photo_path;
        if (coverPhotoRemovedInput) coverPhotoRemovedInput.value = 'false';
    } else {
        console.debug('DEBUG JS: Nenhuma foto de capa existente ou modo de criação.');
        coverPhotoPreview.src = '';
        coverPhotoPreview.style.display = 'none';
        coverPhotoPlaceholder.style.display = 'flex';
        coverPhotoRemoveBtn.classList.add('d-none');
        if (existingCoverPhotoPathInput) existingCoverPhotoPathInput.value = '';
        if (coverPhotoRemovedInput) coverPhotoRemovedInput.value = 'false';
    }

    // --- Galeria de Fotos, Vídeos e Áudios (múltiplos uploads) ---
    const mediaContainers = {
        gallery: { container: document.getElementById('galleryPhotoContainer'), dataKey: 'fotos_galeria', type: 'image' },
        video: { container: document.getElementById('videoUploadBoxes'), dataKey: 'videos', type: 'video' },
        audio: { container: document.getElementById('audioUploadBoxes'), dataKey: 'audios', type: 'audio' }
    };

    for (const key in mediaContainers) {
        const { container, dataKey, type } = mediaContainers[key];
        if (!container) continue;

        const existingMediaArray = anuncioData[dataKey] || [];
        const boxes = container.querySelectorAll('.photo-upload-box');

        boxes.forEach((box, index) => {
            const preview = box.querySelector('.photo-preview') || box.querySelector('video') || box.querySelector('audio');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo');
            const existingPathInput = box.querySelector('input[name^="existing_"]'); // existing_gallery_paths[], etc.

            const currentMediaPath = existingMediaArray[index];

            if (formMode === 'edit' && currentMediaPath) {
                console.debug(`DEBUG JS: Carregando ${type} da galeria ${index}: ${currentMediaPath}`);
                preview.src = currentMediaPath;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                removeBtn.classList.remove('d-none');
                if (existingPathInput) existingPathInput.value = currentMediaPath;
            } else {
                console.debug(`DEBUG JS: Nenhum ${type} existente para slot ${index} ou modo de criação.`);
                preview.src = '';
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.classList.add('d-none');
                if (existingPathInput) existingPathInput.value = '';
            }
        });
    }

    // Aplica as restrições de plano após carregar as mídias
    applyPlanRestrictions(userPlanType);
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

    // --- Função auxiliar para configurar um único input de mídia ---
    function setupSingleMediaInput(inputElement, previewElement, removeButton, removedHiddenInput, existingPathHiddenInput, type) {
        const uploadBox = inputElement.closest('.photo-upload-box');
        const placeholder = uploadBox.querySelector('.upload-placeholder');

        if (!inputElement || !previewElement || !removeButton || !uploadBox || !placeholder) {
            console.error('ERRO JS: Elementos de mídia não encontrados para setup de input único.', { inputElement, previewElement, removeButton, uploadBox, placeholder });
            return;
        }

        // Evento de clique para abrir o seletor de arquivos
        uploadBox.addEventListener('click', function() {
            if (!uploadBox.classList.contains('locked')) { // Só permite clique se não estiver bloqueado
                inputElement.click();
            } else {
                window.showFeedbackModal('info', 'Este slot está bloqueado para o seu plano atual.', 'Acesso Restrito');
            }
        });

        // Evento de mudança no input de arquivo para pré-visualização
        inputElement.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeButton.classList.remove('d-none');
                    if (removedHiddenInput) removedHiddenInput.value = 'false'; // Sinaliza que a mídia não foi removida
                    if (existingPathHiddenInput) existingPathHiddenInput.value = ''; // Limpa o caminho existente se um novo arquivo for selecionado
                    console.debug(`DEBUG JS: Preview de ${type} atualizado com novo arquivo.`);
                    // Revalida a seção de mídias após upload
                    handleFormSubmit(new Event('submit', { cancelable: true })); // Dispara validação do formulário
                };

                if (type === 'image') {
                    reader.readAsDataURL(file);
                } else if (type === 'video' || type === 'audio') {
                    previewElement.src = URL.createObjectURL(file);
                    previewElement.load(); // Carrega o vídeo/áudio
                    // Não precisamos de reader.readAsDataURL para vídeo/áudio, apenas a URL do objeto
                }
            } else {
                // Se o input de arquivo foi limpo (ex: usuário selecionou e depois cancelou),
                // mas havia um caminho existente, ele deve ser restaurado no preview e no hidden input.
                // A menos que o botão de remover tenha sido clicado anteriormente.
                if (existingPathHiddenInput && existingPathHiddenInput.value !== '' && (!removedHiddenInput || removedHiddenInput.value !== 'true')) {
                    previewElement.src = existingPathHiddenInput.value;
                    previewElement.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeButton.classList.remove('d-none');
                } else {
                    previewElement.src = '';
                    previewElement.style.display = 'none';
                    placeholder.style.display = 'flex';
                    removeButton.classList.add('d-none');
                }
                if (removedHiddenInput) removedHiddenInput.value = 'false'; // Reseta o flag se não foi uma remoção explícita
                if (existingPathHiddenInput) existingPathInput.value = existingPathHiddenInput.value; // Garante que o valor existente é mantido se não houver novo arquivo
            }
        });

        // Evento de clique no botão de remover
        removeButton.addEventListener('click', function(e) {
            e.stopPropagation(); // Impede que o clique se propague para a caixa de upload
            window.showConfirmModal('Remover Mídia', 'Tem certeza que deseja remover esta mídia?', () => {
                inputElement.value = ''; // Limpa o input de arquivo
                previewElement.src = ''; // Limpa a pré-visualização
                previewElement.style.display = 'none'; // Esconde a pré-visualização
                placeholder.style.display = 'flex'; // Mostra o placeholder
                removeButton.classList.add('d-none'); // Esconde o botão de remover
                if (removedHiddenInput) {
                    removedHiddenInput.value = 'true'; // Sinaliza que a mídia foi removida
                }
                if (existingPathHiddenInput) {
                    existingPathHiddenInput.value = ''; // Limpa o caminho existente
                }
                console.debug(`DEBUG JS: ${type} removido.`);
                // Revalida a seção de mídias após remoção
                handleFormSubmit(new Event('submit', { cancelable: true })); // Dispara validação do formulário
            });
        });
    }

    // --- Configuração para Vídeo de Confirmação e Foto da Capa ---
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

    // --- Configuração para Galeria de Fotos, Vídeos e Áudios (múltiplos uploads) ---
    const mediaMultiUploads = {
        gallery: { container: document.getElementById('galleryPhotoContainer'), type: 'image' },
        video: { container: document.getElementById('videoUploadBoxes'), type: 'video' },
        audio: { container: document.getElementById('audioUploadBoxes'), type: 'audio' }
    };

    for (const key in mediaMultiUploads) {
        const { container, type } = mediaMultiUploads[key];
        if (!container) continue;

        container.querySelectorAll('.photo-upload-box').forEach(box => {
            const input = box.querySelector('input[type="file"]');
            const preview = box.querySelector('.photo-preview') || box.querySelector('video') || box.querySelector('audio');
            const placeholder = box.querySelector('.upload-placeholder');
            const removeBtn = box.querySelector('.btn-remove-photo');
            const existingPathInput = box.querySelector('input[name^="existing_"]'); // existing_gallery_paths[], etc.
            const premiumLockOverlay = box.querySelector('.premium-lock-overlay');

            // Event listener para clique na caixa de upload
            box.addEventListener('click', function() {
                if (!premiumLockOverlay || premiumLockOverlay.style.display === 'none') { // Só permite clique se não estiver bloqueado
                    input.click();
                } else {
                    window.showFeedbackModal('info', 'Este slot está bloqueado para o seu plano atual.', 'Acesso Restrito');
                }
            });

            // Event listener para mudança no input de arquivo
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                        removeBtn.classList.remove('d-none');
                        if (existingPathInput) existingPathInput.value = ''; // Limpa o caminho existente se um novo arquivo for enviado
                        applyPlanRestrictions(userPlanType); // Reaplicar restrições após upload
                        handleFormSubmit(new Event('submit', { cancelable: true })); // Dispara validação do formulário
                    };
                    if (type === 'image') {
                        reader.readAsDataURL(file);
                    } else if (type === 'video' || type === 'audio') {
                        preview.src = URL.createObjectURL(file);
                        preview.load();
                    }
                } else {
                    // Se o input de arquivo foi limpo, restaura o estado anterior se houver existingPathInput
                    if (existingPathInput && existingPathInput.value !== '') {
                        preview.src = existingPathInput.value;
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                        removeBtn.classList.remove('d-none');
                    } else {
                        preview.src = '';
                        preview.style.display = 'none';
                        placeholder.style.display = 'flex';
                        removeBtn.classList.add('d-none');
                    }
                    applyPlanRestrictions(userPlanType); // Reaplicar restrições
                    handleFormSubmit(new Event('submit', { cancelable: true })); // Dispara validação do formulário
                }
            });

            // Event listener para o botão de remover
            removeBtn.addEventListener('click', function(event) {
                event.stopPropagation();
                window.showConfirmModal('Remover Mídia', 'Tem certeza que deseja remover esta mídia?', () => {
                    preview.src = '';
                    preview.style.display = 'none';
                    placeholder.style.display = 'flex';
                    removeBtn.classList.add('d-none');
                    input.value = ''; // Limpa o input file
                    if (existingPathInput) existingPathInput.value = ''; // Remove o caminho existente
                    applyPlanRestrictions(userPlanType); // Reaplicar restrições
                    handleFormSubmit(new Event('submit', { cancelable: true })); // Dispara validação do formulário
                });
            });
        });
    }
}


/**
 * Aplica a lógica de bloqueio de slots de mídia com base no plano do usuário.
 * @param {string} userPlanType Tipo de plano do usuário ('free', 'premium', etc.).
 */
function applyPlanRestrictions(userPlanType) {
    console.info('INFO JS: Aplicando restrições de plano para mídias. Plano:', userPlanType);

    const mediaLimits = {
        gallery: { free: 1, premium: 20 },
        video: { free: 0, premium: 3 },
        audio: { free: 0, premium: 3 }
    };

    // Galeria de Fotos: 1 gratuita, restante premium
    const galleryPhotoContainer = document.getElementById('galleryPhotoContainer');
    if (galleryPhotoContainer) {
        galleryPhotoContainer.querySelectorAll('.photo-upload-box').forEach((box, index) => {
            const lockOverlay = box.querySelector('.premium-lock-overlay');
            const input = box.querySelector('input[type="file"]');
            const existingPathInput = box.querySelector('input[name="existing_gallery_paths[]"]');
            const hasMedia = (existingPathInput && existingPathInput.value !== '') || (input && input.files.length > 0);

            if (lockOverlay) {
                if (userPlanType === 'free' && index >= mediaLimits.gallery.free) { // Slots além do limite gratuito são premium para plano gratuito
                    lockOverlay.style.display = 'flex';
                    box.classList.add('locked');
                    input.disabled = true; // Desabilita o input para upload
                    if (!hasMedia) { // Se não tem mídia, esconde o botão de remover
                        box.querySelector('.btn-remove-photo').classList.add('d-none');
                    }
                } else {
                    lockOverlay.style.display = 'none';
                    box.classList.remove('locked');
                    input.disabled = false; // Habilita o input
                    if (hasMedia) { // Só mostra o botão de remover se houver mídia
                        box.querySelector('.btn-remove-photo').classList.remove('d-none');
                    }
                }
            }
        });
    }

    // Vídeos e Áudios: Apenas para planos premium
    const mediaContainersToLock = [
        { container: document.getElementById('videoUploadBoxes'), type: 'video' },
        { container: document.getElementById('audioUploadBoxes'), type: 'audio' }
    ];

    mediaContainersToLock.forEach(({ container, type }) => {
        if (!container) return;
        container.querySelectorAll('.photo-upload-box').forEach(box => {
            const lockOverlay = box.querySelector('.premium-lock-overlay');
            const input = box.querySelector('input[type="file"]');
            const existingPathInput = box.querySelector(`input[name="existing_${type}_paths[]"]`); // Ajustado para pegar o nome correto
            const hasMedia = (existingPathInput && existingPathInput.value !== '') || (input && input.files.length > 0);

            if (lockOverlay) {
                if (userPlanType === 'free') {
                    lockOverlay.style.display = 'flex';
                    box.classList.add('locked');
                    input.disabled = true;
                    if (!hasMedia) {
                        box.querySelector('.btn-remove-photo').classList.add('d-none');
                    }
                } else {
                    lockOverlay.style.display = 'none';
                    box.classList.remove('locked');
                    input.disabled = false;
                    if (hasMedia) {
                        box.querySelector('.btn-remove-photo').classList.remove('d-none');
                    }
                }
            }
        });
    });
}


/**
 * Carrega e popula os selects de estados, cidades e bairros.
 * @param {Object} anuncioData Dados do anúncio para pré-selecionar valores.
 */
async function loadAndPopulateLocations(anuncioData) {
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const neighborhoodInput = document.getElementById('neighborhood_id');

    const initialUf = stateSelect ? anuncioData.state_uf : null;
    const initialCityCode = citySelect ? anuncioData.city_code : null;
    const initialNeighborhoodName = neighborhoodInput ? anuncioData.neighborhood_name : null;

    console.log('DEBUG JS: loadAndPopulateLocations - Initial UF:', initialUf);
    console.log('DEBUG JS: loadAndPopulateLocations - Initial City Code:', initialCityCode);
    console.log('DEBUG JS: loadAndPopulateLocations - Initial Neighborhood Name:', initialNeighborhoodName);


    if (stateSelect) stateSelect.innerHTML = '<option value="">Carregando Estados...</option>';
    if (citySelect) {
        citySelect.innerHTML = '<option value="">Selecione o Estado primeiro</option>';
        citySelect.disabled = true;
    }
    if (neighborhoodInput) {
        neighborhoodInput.value = '';
        neighborhoodInput.disabled = true;
        neighborhoodInput.placeholder = 'Selecione a Cidade primeiro';
    }

    try {
        const statesResponse = await fetch(`${window.projectBaseURL}app/adms/assets/js/data/states.json`);
        const states = (await statesResponse.json()).data;
        populateSelect(stateSelect, states, 'Uf', 'Nome', 'Selecione o Estado', initialUf);

        if (stateSelect) {
            stateSelect.onchange = async () => {
                const selectedUf = stateSelect.value;
                citySelect.innerHTML = '<option value="">Carregando Cidades...</option>';
                citySelect.disabled = true;
                neighborhoodInput.value = '';
                neighborhoodInput.disabled = true;
                neighborhoodInput.placeholder = 'Selecione a Cidade primeiro';

                if (selectedUf) {
                    const citiesResponse = await fetch(`${window.projectBaseURL}app/adms/assets/js/data/cities.json`);
                    const allCities = (await citiesResponse.json()).data;
                    const citiesForState = allCities.filter(city => city.Uf === selectedUf);
                    populateSelect(citySelect, citiesForState, 'Codigo', 'Nome', 'Selecione a Cidade', null);
                    citySelect.disabled = false;
                    neighborhoodInput.disabled = false;
                    neighborhoodInput.placeholder = 'Digite o Bairro';
                } else {
                    citySelect.innerHTML = '<option value="">Selecione o Estado primeiro</option>';
                    citySelect.disabled = true;
                }
            };
        }

        if (citySelect) {
            citySelect.onchange = () => {
                const selectedCityCode = citySelect.value;
                if (selectedCityCode) {
                    neighborhoodInput.disabled = false;
                    neighborhoodInput.placeholder = 'Digite o Bairro';
                } else {
                    neighborhoodInput.value = '';
                    neighborhoodInput.disabled = true;
                    neighborhoodInput.placeholder = 'Selecione a Cidade primeiro';
                }
            };
        }

        // Se houver valores iniciais, dispara os eventos de mudança para pré-selecionar
        if (initialUf) {
            stateSelect.value = initialUf;
            // Dispara o evento change para carregar as cidades
            stateSelect.dispatchEvent(new Event('change'));

            // Aguarda um pequeno tempo para as cidades serem carregadas antes de tentar selecionar a cidade
            await new Promise(resolve => setTimeout(resolve, 100));

            if (initialCityCode) {
                citySelect.value = initialCityCode;
                citySelect.dispatchEvent(new Event('change')); // Dispara para habilitar o bairro
            }
            if (initialNeighborhoodName) {
                neighborhoodInput.value = initialNeighborhoodName;
            }
        }

        console.log('INFO JS: loadAndPopulateLocations - Localização carregada e populada.');

    } catch (error) {
        console.error('ERRO JS: Erro ao carregar dados de localização:', error);
        if (stateSelect) stateSelect.innerHTML = '<option value="">Erro ao carregar estados</option>';
        if (citySelect) citySelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
        window.showFeedbackModal('error', 'Erro ao carregar dados de localização. Por favor, recarregue a página.', 'Erro de Localização');
    }
}

/**
 * Popula um elemento <select> com opções.
 * @param {HTMLSelectElement} selectElement O elemento select.
 * @param {Array<Object>} dataArray O array de objetos com os dados.
 * @param {string} valueKey A chave do objeto a ser usada como valor da opção.
 * @param {string} textKey A chave do objeto a ser usada como texto da opção.
 * @param {string} defaultOptionText O texto da opção padrão (ex: "Selecione...").
 * @param {string|null} selectedValue O valor a ser pré-selelecionado.
 */
function populateSelect(selectElement, dataArray, valueKey, textKey, defaultOptionText, selectedValue = null) {
    if (!selectElement) return;

    selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`;
    if (Array.isArray(dataArray)) {
        dataArray.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[textKey];
            if (selectedValue && item[valueKey] == selectedValue) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    } else {
        console.error('ERRO JS: populateSelect - dataArray não é um array:', dataArray);
        selectElement.innerHTML = `<option value="">Erro ao carregar dados</option>`;
    }
}

/**
 * Configura a validação em tempo real para os grupos de checkboxes.
 */
function setupCheckboxValidation() {
    const formAnuncio = document.getElementById('formCriarAnuncio');
    if (!formAnuncio) return;

    const checkboxGroups = [
        { id: 'aparencia-checkboxes', name: 'aparencia[]', min: 1, feedbackId: 'aparencia-feedback', message: 'Por favor, selecione pelo menos 1 item de aparência.' },
        { id: 'idiomas-checkboxes', name: 'idiomas[]', min: 1, feedbackId: 'idiomas-feedback', message: 'Por favor, selecione pelo menos 1 idioma.' },
        { id: 'locais-checkboxes', name: 'locais_atendimento[]', min: 1, feedbackId: 'locais-feedback', message: 'Por favor, selecione pelo menos 1 local de atendimento.' },
        { id: 'pagamentos-checkboxes', name: 'formas_pagamento[]', min: 1, feedbackId: 'pagamentos-feedback', message: 'Por favor, selecione pelo menos 1 forma de pagamento.' },
        { id: 'servicos-checkboxes', name: 'servicos[]', min: 2, feedbackId: 'servicos-feedback', message: 'Por favor, selecione pelo menos 2 serviços.' } // Corrigido o ID do container
    ];

    checkboxGroups.forEach(group => {
        const container = document.getElementById(group.id);
        if (container) {
            const checkboxes = container.querySelectorAll(`input[name="${group.name}"]`);
            const feedbackElement = document.getElementById(group.feedbackId);

            const validateGroup = () => {
                const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
                if (checkedCount < group.min) {
                    feedbackElement.textContent = group.message;
                } else {
                    feedbackElement.textContent = '';
                }
            };

            checkboxes.forEach(checkbox => {
                checkbox.removeEventListener('change', validateGroup);
                checkbox.addEventListener('change', validateGroup);
            });

            validateGroup(); // Valida na inicialização
        }
    });
}

// Event listeners que precisam do DOM completamente carregado.
// Este bloco deve ser o único DOMContentLoaded neste arquivo.
document.addEventListener('DOMContentLoaded', function() {
    console.log('INFO JS: DOMContentLoaded disparado em anuncio.js. (Adicionando event listeners para Pausar/Ativar).');

    // --- Event Listener para o botão Pausar/Ativar Anúncio na Sidebar ---
    const navPausarAnuncio = document.getElementById('navPausarAnuncio');
    if (navPausarAnuncio) {
        navPausarAnuncio.addEventListener('click', function(event) {
            event.preventDefault();
            console.log('DEBUG JS: navPausarAnuncio clicado.');

            const hasAnuncio = document.body.dataset.hasAnuncio === 'true';
            const anuncioStatus = document.body.dataset.anuncioStatus;
            const anuncioId = document.body.dataset.anuncioId;

            if (!anuncioId) {
                console.error('ERRO JS: ID do anúncio não encontrado no dataset do body para Pausar/Ativar Anúncio.');
                if (typeof window.showFeedbackModal === 'function') {
                    window.showFeedbackModal('error', 'Não foi possível encontrar o ID do anúncio para esta ação.', 'Erro de Anúncio');
                }
                return;
            }

            if (hasAnuncio && (anuncioStatus === 'active' || anuncioStatus === 'inactive')) {
                if (anuncioStatus === 'active') {
                    window.showConfirmModal(
                        'Pausar Anúncio',
                        'Tem certeza que deseja pausar seu anúncio? Ele não ficará visível no site.',
                        async () => { // Callback onConfirm
                            const originalButtonHTML = window.activateButtonLoading(navPausarAnuncio, 'Pausando...');
                            window.showLoadingModal();
                            try {
                                const response = await fetch(`${window.URLADM}anuncio/pausarAnuncio`, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ anuncio_id: anuncioId })
                                });
                                const data = await response.json();

                                setTimeout(() => {
                                    window.hideLoadingModal();
                                    window.deactivateButtonLoading(navPausarAnuncio, originalButtonHTML);
                                    if (data.success) {
                                        window.showFeedbackModal('success', data.message, 'Sucesso!');
                                        window.fetchAndApplyAnuncioStatus();
                                    } else {
                                        window.showFeedbackModal('error', data.message, 'Erro!');
                                    }
                                }, 1000); // Atraso do spinner
                            } catch (error) {
                                console.error('ERRO JS: Erro ao pausar anúncio:', error);
                                setTimeout(() => {
                                    window.hideLoadingModal();
                                    window.deactivateButtonLoading(navPausarAnuncio, originalButtonHTML);
                                    window.showFeedbackModal('error', 'Ocorreu um erro inesperado ao pausar o anúncio. Tente novamente.', 'Erro!');
                                }, 1000); // Atraso do spinner
                            }
                        },
                        () => { // Callback onCancel
                            console.log('INFO JS: Pausar Anúncio cancelado.');
                        },
                        'Sim, Pausar',
                        'Cancelar'
                    );
                } else if (anuncioStatus === 'inactive') {
                    window.showConfirmModal(
                        'Ativar Anúncio',
                        'Tem certeza que deseja ativar seu anúncio? Ele voltará a ficar visível no site.',
                        async () => { // Callback onConfirm
                            const originalButtonHTML = window.activateButtonLoading(navPausarAnuncio, 'Ativando...');
                            window.showLoadingModal();
                            try {
                                const response = await fetch(`${window.URLADM}anuncio/ativarAnuncio`, { // Ajustado para 'ativarAnuncio'
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ anuncio_id: anuncioId })
                                });
                                const data = await response.json();

                                setTimeout(() => {
                                    window.hideLoadingModal();
                                    window.deactivateButtonLoading(navPausarAnuncio, originalButtonHTML);
                                    if (data.success) {
                                        window.showFeedbackModal('success', data.message, 'Sucesso!');
                                        window.fetchAndApplyAnuncioStatus();
                                    } else {
                                        window.showFeedbackModal('error', data.message, 'Erro!');
                                    }
                                }, 1000); // Atraso do spinner
                            } catch (error) {
                                console.error('ERRO JS: Erro ao ativar anúncio:', error);
                                setTimeout(() => {
                                    window.hideLoadingModal();
                                    window.deactivateButtonLoading(navPausarAnuncio, originalButtonHTML);
                                    window.showFeedbackModal('error', 'Ocorreu um erro inesperado ao ativar o anúncio. Tente novamente.', 'Erro!');
                                }, 1000); // Atraso do spinner
                            }
                        },
                        () => { // Callback onCancel
                            console.log('INFO JS: Ativar Anúncio cancelado.');
                        },
                        'Sim, Ativar',
                        'Cancelar'
                    );
                }
            } else {
                console.warn(`AVISO JS: Ação de Pausar/Ativar não permitida. Has Anuncio: ${hasAnuncio}, Status: ${anuncioStatus}`);
                if (typeof window.showFeedbackModal === 'function') {
                    let msg = 'Não é possível pausar/ativar o anúncio neste momento.';
                    if (!hasAnuncio) msg = 'Você não possui um anúncio para pausar/ativar.';
                    else if (anuncioStatus === 'pending') msg = 'Seu anúncio está pendente de aprovação e não pode ser pausado/ativado.';
                    else if (anuncioStatus === 'rejected') msg = 'Seu anúncio foi rejeitado e não pode ser pausado/ativado.';
                    window.showFeedbackModal('info', msg, 'Ação Não Permitida');
                }
            }
        });
    } else {
        console.warn('AVISO JS: Elemento #navPausarAnuncio não encontrado.');
    }

    // --- Event Listener para o botão Excluir Anúncio na Sidebar ---
    const navExcluirAnuncio = document.getElementById('navExcluirAnuncio');
    if (navExcluirAnuncio) {
        navExcluirAnuncio.addEventListener('click', function(event) {
            event.preventDefault();
            console.log('DEBUG JS: navExcluirAnuncio clicado.');

            const anuncioId = document.body.dataset.anuncioId;
            if (!anuncioId) {
                console.error('ERRO JS: ID do anúncio não encontrado no dataset do body para Excluir Anúncio.');
                if (typeof window.showFeedbackModal === 'function') {
                    window.showFeedbackModal('error', 'Não foi possível encontrar o ID do anúncio para esta ação.', 'Erro de Anúncio');
                }
                return;
            }

            window.showConfirmModal(
                'Confirmar Exclusão',
                'Tem certeza que deseja excluir este anúncio? Esta ação é irreversível.',
                async () => { // Callback onConfirm
                    const originalButtonHTML = window.activateButtonLoading(navExcluirAnuncio, 'Excluindo...');
                    window.showLoadingModal();
                    try {
                        const response = await fetch(`${window.URLADM}anuncio/deleteAnuncio`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ anuncio_id: anuncioId })
                        });
                        const data = await response.json();

                        setTimeout(() => {
                            window.hideLoadingModal();
                            window.deactivateButtonLoading(navExcluirAnuncio, originalButtonHTML);
                            if (data.success) {
                                window.showFeedbackModal('success', data.message, 'Sucesso!');
                                document.body.dataset.hasAnuncio = 'false'; // Anúncio foi excluído
                                document.body.dataset.anuncioStatus = 'not_found';
                                document.body.dataset.anuncioId = '';
                                window.updateAnuncioSidebarLinks(); // Atualiza a sidebar
                                setTimeout(() => {
                                    window.location.href = window.URLADM + 'anuncio/index'; // Redireciona para a página de criação
                                }, 1500); // Atraso para o redirecionamento
                            } else {
                                window.showFeedbackModal('error', data.message, 'Erro!');
                            }
                        }, 1000); // Atraso do spinner
                    } catch (error) {
                        console.error('ERRO JS: Erro ao deletar anúncio:', error);
                        setTimeout(() => {
                            window.hideLoadingModal();
                            window.deactivateButtonLoading(navExcluirAnuncio, originalButtonHTML);
                            window.showFeedbackModal('error', 'Ocorreu um erro inesperado ao deletar o anúncio. Tente novamente.', 'Erro!');
                        }, 1000); // Atraso do spinner
                    }
                },
                () => { // Callback onCancel
                    console.log('INFO JS: Exclusão de Anúncio cancelada.');
                },
                'Sim, Excluir',
                'Cancelar'
            );
        });
    } else {
        console.warn('AVISO JS: Elemento #navExcluirAnuncio não encontrado.');
    }
});
