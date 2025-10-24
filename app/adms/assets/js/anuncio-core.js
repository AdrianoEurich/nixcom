/**
 * ANÚNCIO CORE - Funções principais e inicialização
 * Versão: 3.0 (Modular Simples)
 */

console.log('🔧 ANÚNCIO CORE carregado');

// Variáveis globais
let form;
let formMode;
let userRole;
let userPlanType;
let anuncioData = null;

/**
 * Função principal de inicialização
 */
async function initializeAnuncioFormPage(fullUrl, initialData = null) {
    console.log('🎯 ANÚNCIO CORE: Inicializando página de formulário de anúncio');
    console.log('🔍 DEBUG: fullUrl:', fullUrl, '| initialData:', initialData);

    form = document.getElementById('formAnuncio');
    if (!form) {
        console.error('❌ ANÚNCIO CORE: Formulário "formAnuncio" não encontrado.');
        window.showFeedbackModal('error', 'Erro interno: Formulário de anúncio não encontrado.', 'Erro de Inicialização');
        return;
    }

    formMode = form.dataset.formMode || 'create';
    userRole = document.body.dataset.userRole || 'usuario';
    userPlanType = form.dataset.userPlanType || document.body.dataset.userPlanType || 'free';

    console.log("🔍 DEBUG: formMode:", formMode, "| userRole:", userRole, "| userPlanType:", userPlanType);

    // Inicializar módulos
    if (window.AnuncioValidation) {
        window.AnuncioValidation.init(form, formMode, userPlanType);
    }
    
    // Máscaras serão aplicadas depois que os dados forem carregados
    
    // Localizações serão inicializadas depois que os dados forem carregados no modo de edição
    if (formMode !== 'edit' && window.AnuncioLocations) {
        console.log("✅ ANÚNCIO CORE: Inicializando módulo de localizações");
        await window.AnuncioLocations.init(form, formMode, initialData);
    } else if (formMode === 'edit' && window.AnuncioLocations) {
        console.log("✅ ANÚNCIO CORE: Modo de edição - localizações serão inicializadas depois");
    } else {
        console.error("❌ ANÚNCIO CORE: Módulo de localizações não encontrado");
    }
    
    if (window.AnuncioUploads) {
        console.log("✅ ANÚNCIO CORE: Inicializando módulo de uploads");
        window.AnuncioUploads.init(form, formMode, userPlanType);
    } else {
        console.error("❌ ANÚNCIO CORE: Módulo de uploads não encontrado");
    }
    
    if (window.AnuncioForms) {
        window.AnuncioForms.init(form, formMode, userRole, userPlanType);
    }

    console.log("✅ ANÚNCIO CORE: Todos os módulos inicializados");

    if (formMode === 'edit') {
        console.log("📝 ANÚNCIO CORE: Modo edição detectado");
        await loadAnuncioDataForEdit(fullUrl);
    } else {
        console.log("➕ ANÚNCIO CORE: Modo criação");
        initializeCreateMode();
    }
};

async function loadAnuncioDataForEdit(fullUrl) {
    // Limpar a URL removendo fragmentos (#) e extrair o ID
    const cleanUrl = fullUrl.split('#')[0];
    const urlParts = cleanUrl.split('?');
    const anuncioId = urlParts.length > 1 ? new URLSearchParams(urlParts[1]).get('id') : null;
    
    if (!anuncioId) {
        console.error('❌ ANÚNCIO CORE: ID do anúncio não encontrado na URL para edição.');
        console.error('❌ ANÚNCIO CORE: URL original:', fullUrl);
        console.error('❌ ANÚNCIO CORE: URL limpa:', cleanUrl);
        window.showFeedbackModal('error', 'Erro: ID do anúncio não especificado para edição.', 'Erro de Carregamento');
        return;
    }
    console.log("🔍 DEBUG: Anúncio ID:", anuncioId);

    try {
        const response = await fetch(`${window.URLADM}anuncio/editarAnuncio?id=${anuncioId}&ajax_data_only=true`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (result.success && result.anuncio) {
            anuncioData = result.anuncio;
            console.log("✅ ANÚNCIO CORE: Dados do anúncio carregados:", anuncioData);
            console.log("🔍 DEBUG: anuncioData.videos:", anuncioData.videos);
            console.log("🔍 DEBUG: anuncioData.audios:", anuncioData.audios);
            console.log("🔍 DEBUG: anuncioData.fotos_galeria:", anuncioData.fotos_galeria);
            initializeFormWithData(anuncioData);
        } else {
            console.error('❌ ANÚNCIO CORE: Erro ao carregar dados para edição:', result.message || 'Dados não encontrados.');
            window.showFeedbackModal('error', `Erro ao carregar dados do anúncio: ${result.message || 'Dados não encontrados.'}`, 'Erro de Carregamento');
        }
    } catch (error) {
        console.error('❌ ANÚNCIO CORE: Erro ao buscar dados do anúncio para edição:', error);
        window.showFeedbackModal('error', `Erro ao carregar dados do anúncio: ${error.message}`, 'Erro de Carregamento');
    }
}

function initializeCreateMode() {
    console.log("🔧 ANÚNCIO CORE: Inicializando formulário para criação");
    initializeFormWithData(null);
    
    // CORREÇÃO: Reinicializar uploads após um delay para garantir que o DOM esteja pronto
    setTimeout(() => {
        if (window.AnuncioUploads && window.AnuncioUploads.reinit) {
            console.log("🔄 ANÚNCIO CORE: Reinicializando uploads para modo criação...");
            window.AnuncioUploads.reinit();
        }
    }, 500);
}

function initializeFormWithData(data) {
    console.log("🔧 ANÚNCIO CORE: Inicializando formulário");
    console.log("🔍 DEBUG: anuncioData:", data);

    // Preencher campos do formulário
    if (window.AnuncioForms && window.AnuncioForms.populateFormFields) {
        window.AnuncioForms.populateFormFields(data, formMode);
    }
    
    // Aplicar máscaras APÓS preencher os campos
    if (window.AnuncioMasks) {
        window.AnuncioMasks.init(form);
    }

    // Carregar mídias existentes no modo de edição
    console.log('🔍 DEBUG: Verificando condições para carregar mídias...');
    console.log('🔍 DEBUG: formMode:', formMode);
    console.log('🔍 DEBUG: data existe:', !!data);
    console.log('🔍 DEBUG: window.AnuncioUploads existe:', !!window.AnuncioUploads);
    console.log('🔍 DEBUG: loadExistingMedia existe:', !!(window.AnuncioUploads && window.AnuncioUploads.loadExistingMedia));
    
    if (formMode === 'edit' && data && window.AnuncioUploads && window.AnuncioUploads.loadExistingMedia) {
        console.log('DEBUG JS: AnuncioCore - Carregando mídias existentes...');
        
        // CORREÇÃO: Usar múltiplas tentativas para garantir que o DOM esteja pronto
        const loadMediaWithRetry = (attempt = 1, maxAttempts = 5) => {
            console.log(`🔄 ANÚNCIO CORE: Tentativa ${attempt} de carregar mídias...`);
            
            // Verificar se os containers existem no DOM
            const videoContainers = document.querySelectorAll('#videoUploadBoxes .photo-upload-box');
            const audioContainers = document.querySelectorAll('#audioUploadBoxes .photo-upload-box');
            const galleryContainers = document.querySelectorAll('#galleryUploadBoxes .photo-upload-box');
            
            console.log('🔍 DEBUG: Containers encontrados:');
            console.log('  - Vídeos:', videoContainers.length);
            console.log('  - Áudios:', audioContainers.length);
            console.log('  - Galeria:', galleryContainers.length);
            
            if (videoContainers.length > 0 || audioContainers.length > 0 || galleryContainers.length > 0) {
                console.log('✅ ANÚNCIO CORE: Containers encontrados, carregando mídias...');
                window.AnuncioUploads.loadExistingMedia(data);
            } else if (attempt < maxAttempts) {
                console.log(`⏳ ANÚNCIO CORE: Containers não encontrados, tentando novamente em ${attempt * 200}ms...`);
                setTimeout(() => loadMediaWithRetry(attempt + 1, maxAttempts), attempt * 200);
            } else {
                console.error('❌ ANÚNCIO CORE: Falha ao carregar mídias após', maxAttempts, 'tentativas');
                // FALLBACK: Tentar carregar mídias mesmo sem containers específicos
                console.log('🔄 ANÚNCIO CORE: Tentando carregar mídias como fallback...');
                window.AnuncioUploads.loadExistingMedia(data);
            }
        };
        
        // Iniciar o carregamento com retry
        setTimeout(() => loadMediaWithRetry(), 100);
    } else {
        console.log('⚠️ ANÚNCIO CORE: Condições não atendidas para carregar mídias');
        console.log('  - formMode:', formMode);
        console.log('  - data:', !!data);
        console.log('  - AnuncioUploads:', !!window.AnuncioUploads);
        console.log('  - loadExistingMedia:', !!(window.AnuncioUploads && window.AnuncioUploads.loadExistingMedia));
    }

    // Inicializar localizações no modo de edição após carregar os dados
    if (formMode === 'edit' && data && window.AnuncioLocations) {
        console.log('🔍 ANÚNCIO CORE: Inicializando localizações com dados carregados...');
        console.log('🔍 ANÚNCIO CORE: Dados para localizações:', data);
        setTimeout(async () => {
            console.log('🔍 ANÚNCIO CORE: Executando inicialização de localizações...');
            await window.AnuncioLocations.init(form, formMode, data);
        }, 500);
    } else if (formMode === 'create' && window.AnuncioLocations) {
        console.log('🔍 ANÚNCIO CORE: Inicializando localizações no modo de criação...');
        setTimeout(async () => {
            await window.AnuncioLocations.init(form, formMode, null);
        }, 500);
    }

    // Aplicar restrições de plano
    if (window.AnuncioUploads && window.AnuncioUploads.applyPlanRestrictions) {
        window.AnuncioUploads.applyPlanRestrictions(userPlanType);
    }

    // Configurar botões de administrador se for admin/administrador e estiver editando
    const userRoleNorm = (userRole || '').toLowerCase();
    if ((userRoleNorm === 'admin' || userRoleNorm === 'administrador') && formMode === 'edit' && data?.id) {
        console.log('DEBUG JS: AnuncioCore - Configurando botões de administrador...');
        if (typeof setupAdminActionButtons === 'function') {
            setupAdminActionButtons(data.id, data.user_id, data.status);
            console.log('✅ ANÚNCIO CORE: Botões de administrador configurados');
        } else {
            console.warn('⚠️ ANÚNCIO CORE: Função setupAdminActionButtons não encontrada');
        }
    }

    console.log("✅ ANÚNCIO CORE: Formulário inicializado com sucesso");
}

// Criar namespace para o módulo
window.AnuncioCore = {
    initializeAnuncioFormPage: initializeAnuncioFormPage
};

// Expor função globalmente também
window.initializeAnuncioFormPage = initializeAnuncioFormPage;

console.log("✅ ANÚNCIO CORE: Módulo carregado e pronto");
console.log("🔍 DEBUG: Função initializeAnuncioFormPage exposta:", typeof window.initializeAnuncioFormPage);
console.log("🔍 DEBUG: AnuncioCore namespace:", typeof window.AnuncioCore);
