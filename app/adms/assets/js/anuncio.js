// anexo este script diretamente na view do formulário de anúncio, se for carregada via SPA,
// ou na página principal se for um carregamento full page.
// O importante é que a função window.initializeAnuncioPage seja globalmente acessível.

// Variáveis globais para os elementos do formulário de anúncio
let formCriarAnuncio;
let btnSubmitAnuncio;
let originalButtonHTML = '';

// Localização
let stateSelect;
let citySelect;
let neighborhoodSelect;

// Mídia
let coverPhotoInput;
let coverPhotoPreview;
let coverPhotoUploadBox;
let galleryPhotoContainer; // Pode ser necessário se houver lógica específica para este contêiner

// URLs da API (Assumindo que URLADM está disponível no escopo global do PHP)
// Garanta que URLADM esteja definida antes deste script, por exemplo, no seu layout PHP
const URL_FETCH_STATES = URLADM + 'api/states';
const URL_FETCH_CITIES = URLADM + 'api/cities/';
const URL_FETCH_NEIGHBORHOODS = URLADM + 'api/neighborhoods/';

/**
 * Funções auxiliares gerais para o formulário de anúncio.
 * Movidas para serem internas à função de inicialização ou globais se usadas por outros módulos.
 * Para este caso, como são auxiliares específicas de 'anuncio.js', mantemos aqui.
 */

/**
 * Valida se pelo menos N checkboxes de um grupo foram selecionados.
 * @param {string} containerId - O ID do contêiner dos checkboxes.
 * @param {string} inputName - O atributo 'name' dos checkboxes.
 * @param {number} minRequired - O número mínimo de checkboxes necessários.
 * @returns {boolean} - Verdadeiro se a validação passar, falso caso contrário.
 */
function validateMinCheckboxes(containerId, inputName, minRequired) {
    const checkboxes = document.querySelectorAll(`#${containerId} input[name="${inputName}[]"]:checked`);
    if (checkboxes.length < minRequired) {
        const container = document.getElementById(containerId);
        // Tenta encontrar o form-group ou elemento pai mais próximo para o erro
        const parentFormGroup = container ? (container.closest('.mb-4') || container.parentElement) : null;
        if (parentFormGroup) {
            window.showError(parentFormGroup, `Selecione pelo menos ${minRequired} opção(ões).`);
        }
        return false;
    }
    const container = document.getElementById(containerId);
    const parentFormGroup = container ? (container.closest('.mb-4') || container.parentElement) : null;
    if (parentFormGroup) {
        window.removeError(parentFormGroup);
    }
    return true;
}

/**
 * Limpa uma lista suspensa e adiciona uma opção padrão.
 * @param {HTMLElement} selectElement - O elemento <select> a ser limpo.
 * @param {string} defaultText - O texto da opção padrão.
 */
function clearAndAddDefaultOption(selectElement, defaultText) {
    selectElement.innerHTML = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = defaultText;
    selectElement.appendChild(defaultOption);
    selectElement.disabled = true;
}

/**
 * Configura o preview e o botão de remover para um input de arquivo.
 * @param {HTMLElement} inputElement - O input type="file".
 * @param {HTMLElement} previewElement - O elemento <img>, <video> ou <audio> para preview.
 * @param {HTMLElement} uploadBoxElement - O contêiner .photo-upload-box.
 * @param {HTMLElement} removeButton - O botão de remover.
 */
function setupFilePreviewAndRemoval(inputElement, previewElement, uploadBoxElement, removeButton) {
    // Garante que listeners não sejam duplicados ao re-inicializar
    inputElement.removeEventListener('change', handleFileChange);
    removeButton.removeEventListener('click', handleFileRemoval);
    uploadBoxElement.removeEventListener('click', handleUploadBoxClick);

    inputElement.addEventListener('change', handleFileChange);
    removeButton.addEventListener('click', handleFileRemoval);
    uploadBoxElement.addEventListener('click', handleUploadBoxClick);

    function handleFileChange() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                previewElement.src = e.target.result;
                previewElement.style.display = 'block';
                uploadBoxElement.querySelector('.upload-placeholder').style.display = 'none';
                removeButton.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            // Caso o usuário cancele a seleção de arquivo após abrir a janela
            previewElement.style.display = 'none';
            previewElement.src = '#'; // Limpa o src
            if (previewElement.tagName === 'VIDEO' || previewElement.tagName === 'AUDIO') {
                previewElement.load(); // Reseta o elemento de mídia
            }
            uploadBoxElement.querySelector('.upload-placeholder').style.display = 'flex';
            removeButton.classList.add('d-none');
        }
        window.removeError(uploadBoxElement); // Sempre remove o erro ao tentar selecionar um arquivo
    }

    function handleFileRemoval() {
        inputElement.value = ''; // Limpa o input file
        previewElement.src = '#';
        previewElement.style.display = 'none';
        if (previewElement.tagName === 'VIDEO' || previewElement.tagName === 'AUDIO') {
            previewElement.load();
        }
        uploadBoxElement.querySelector('.upload-placeholder').style.display = 'flex';
        removeButton.classList.add('d-none');
        window.removeError(uploadBoxElement); // Remove qualquer erro associado ao box
    }

    function handleUploadBoxClick(e) {
        // Se o clique não foi no botão de remover, simula o clique no input
        if (!e.target.closest('.btn-remove-photo') && !inputElement.disabled) {
            inputElement.click();
        }
    }
}

/**
 * Carrega os estados na lista suspensa.
 */
async function loadStates() {
    console.log('INFO JS: Carregando estados...');
    clearAndAddDefaultOption(stateSelect, 'Carregando Estados...');
    try {
        const response = await fetch(URL_FETCH_STATES);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (data.status && data.data) {
            clearAndAddDefaultOption(stateSelect, 'Selecione o Estado');
            data.data.forEach(state => {
                const option = document.createElement('option');
                option.value = state.id;
                option.textContent = state.name;
                stateSelect.appendChild(option);
            });
            stateSelect.disabled = false;
        } else {
            window.showFeedbackModal('error', data.message || 'Erro ao carregar estados.');
            clearAndAddDefaultOption(stateSelect, 'Erro ao Carregar Estados');
        }
    } catch (error) {
        console.error('ERRO JS: Falha ao buscar estados:', error);
        window.showFeedbackModal('error', 'Não foi possível carregar os estados. Tente novamente mais tarde.');
        clearAndAddDefaultOption(stateSelect, 'Erro ao Carregar Estados');
    }
}

/**
 * Carrega as cidades com base no estado selecionado.
 * @param {string} stateId - O ID do estado.
 */
async function loadCities(stateId) {
    console.log(`INFO JS: Carregando cidades para o estado ${stateId}...`);
    clearAndAddDefaultOption(citySelect, 'Carregando Cidades...');
    clearAndAddDefaultOption(neighborhoodSelect, 'Selecione o Bairro'); // Limpa bairros também

    if (!stateId) {
        clearAndAddDefaultOption(citySelect, 'Selecione a Cidade');
        return;
    }

    try {
        const response = await fetch(`${URL_FETCH_CITIES}${stateId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (data.status && data.data) {
            clearAndAddDefaultOption(citySelect, 'Selecione a Cidade');
            data.data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
            citySelect.disabled = false;
        } else {
            window.showFeedbackModal('error', data.message || 'Erro ao carregar cidades.');
            clearAndAddDefaultOption(citySelect, 'Erro ao Carregar Cidades');
        }
    } catch (error) {
        console.error('ERRO JS: Falha ao buscar cidades:', error);
        window.showFeedbackModal('error', 'Não foi possível carregar as cidades. Tente novamente mais tarde.');
        clearAndAddDefaultOption(citySelect, 'Erro ao Carregar Cidades');
    }
}

/**
 * Carrega os bairros com base na cidade selecionada.
 * @param {string} cityId - O ID da cidade.
 */
async function loadNeighborhoods(cityId) {
    console.log(`INFO JS: Carregando bairros para a cidade ${cityId}...`);
    clearAndAddDefaultOption(neighborhoodSelect, 'Carregando Bairros...');

    if (!cityId) {
        clearAndAddDefaultOption(neighborhoodSelect, 'Selecione o Bairro');
        return;
    }

    try {
        const response = await fetch(`${URL_FETCH_NEIGHBORHOODS}${cityId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (data.status && data.data) {
            clearAndAddDefaultOption(neighborhoodSelect, 'Selecione o Bairro');
            data.data.forEach(neighborhood => {
                const option = document.createElement('option');
                option.value = neighborhood.id;
                option.textContent = neighborhood.name;
                neighborhoodSelect.appendChild(option);
            });
            neighborhoodSelect.disabled = false;
        } else {
            window.showFeedbackModal('error', data.message || 'Erro ao carregar bairros.');
            clearAndAddDefaultOption(neighborhoodSelect, 'Erro ao Carregar Bairros');
        }
    } catch (error) {
        console.error('ERRO JS: Falha ao buscar bairros:', error);
        window.showFeedbackModal('error', 'Não foi possível carregar os bairros. Tente novamente mais tarde.');
        clearAndAddDefaultOption(neighborhoodSelect, 'Erro ao Carregar Bairros');
    }
}

/**
 * Inicializa todos os event listeners e lógicas da página de criação de anúncio.
 * Esta função deve ser chamada quando a view 'criar-anuncio' é carregada,
 * seja no carregamento inicial da página ou via AJAX (SPA).
 */
window.initializeAnuncioPage = function() {
    console.log('INFO JS: initializeAnuncioPage() executado. Configurando formulário de anúncio.');

    // Reset de variáveis e re-captura de elementos DOM (importante para SPA)
    formCriarAnuncio = document.getElementById('formCriarAnuncio');
    btnSubmitAnuncio = document.getElementById('btnSubmitAnuncio');
    originalButtonHTML = '';

    stateSelect = document.getElementById('state_id');
    citySelect = document.getElementById('city_id');
    neighborhoodSelect = document.getElementById('neighborhood_id');

    coverPhotoInput = document.getElementById('foto_capa_input');
    coverPhotoPreview = document.getElementById('coverPhotoPreview');
    coverPhotoUploadBox = document.getElementById('coverPhotoUploadBox');
    galleryPhotoContainer = document.getElementById('galleryPhotoContainer');

    // Remove listeners antigos para evitar duplicação (especialmente importante para inputs que são recriados)
    if (formCriarAnuncio) {
        formCriarAnuncio.removeEventListener('submit', handleSubmitAnuncioForm);
        formCriarAnuncio.addEventListener('submit', handleSubmitAnuncioForm);
    }

    if (stateSelect) {
        stateSelect.removeEventListener('change', handleStateChange);
        stateSelect.addEventListener('change', handleStateChange);
        loadStates(); // Carrega os estados quando a página é inicializada/re-inicializada
    }

    if (citySelect) {
        citySelect.removeEventListener('change', handleCityChange);
        citySelect.addEventListener('change', handleCityChange);
    }

    if (neighborhoodSelect) {
        neighborhoodSelect.removeEventListener('change', handleNeighborhoodChange);
        neighborhoodSelect.addEventListener('change', handleNeighborhoodChange);
    }

    // Funções de Event Handler
    function handleStateChange() {
        loadCities(this.value);
        window.removeError(stateSelect);
    }

    function handleCityChange() {
        loadNeighborhoods(this.value);
        window.removeError(citySelect);
    }

    function handleNeighborhoodChange() {
        window.removeError(neighborhoodSelect);
    }

    // Configura o preview e remoção para a foto de capa
    if (coverPhotoInput && coverPhotoPreview && coverPhotoUploadBox && coverPhotoUploadBox.querySelector('.btn-remove-photo')) {
        setupFilePreviewAndRemoval(
            coverPhotoInput,
            coverPhotoPreview,
            coverPhotoUploadBox,
            coverPhotoUploadBox.querySelector('.btn-remove-photo')
        );
    }

    // Configura o preview e remoção para as fotos da galeria
    document.querySelectorAll('.gallery-upload-box').forEach(box => {
        const input = box.querySelector('input[type="file"]');
        const preview = box.querySelector('.photo-preview');
        const removeBtn = box.querySelector('.btn-remove-photo');

        if (input && preview && box && removeBtn) {
            if (!box.classList.contains('premium-locked')) { // Apenas para caixas não bloqueadas
                setupFilePreviewAndRemoval(input, preview, box, removeBtn);
            } else {
                // Para caixas bloqueadas, impede o clique para upload
                // Remove listener anterior para evitar duplicação
                box.removeEventListener('click', handlePremiumLockedClick);
                box.addEventListener('click', handlePremiumLockedClick);
            }
        }
    });

    // Configura o preview e remoção para vídeos e áudios (todos bloqueados no HTML, mas com a estrutura para futuro)
    document.querySelectorAll('.video-upload-box, .audio-upload-box').forEach(box => {
        const input = box.querySelector('input[type="file"]');
        const preview = box.querySelector('.photo-preview');
        const removeBtn = box.querySelector('.btn-remove-photo');

        if (input && preview && box && removeBtn) { // Verifica se todos os elementos existem
            // Esses são sempre bloqueados por padrão no seu HTML atual
            // Remove listener anterior para evitar duplicação
            box.removeEventListener('click', handlePremiumLockedClick);
            box.addEventListener('click', handlePremiumLockedClick);
        }
    });

    function handlePremiumLockedClick(e) {
        if (!e.target.closest('.btn-remove-photo')) {
            window.showFeedbackModal('error', 'Esta opção de upload é apenas para planos pagos.');
        }
    }

    // Função para submissão do formulário
    async function handleSubmitAnuncioForm(event) {
        event.preventDefault();
        console.log('INFO JS: Tentativa de submissão do formulário de anúncio.');

        let isValid = true;

        // Limpa todos os erros existentes antes de revalidar
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback-custom').forEach(el => el.remove());

        // Validação de Localização
        if (stateSelect && !stateSelect.value) {
            window.showError(stateSelect, 'Selecione um estado.');
            isValid = false;
        }
        if (citySelect && !citySelect.value) {
            window.showError(citySelect, 'Selecione uma cidade.');
            isValid = false;
        }
        if (neighborhoodSelect && !neighborhoodSelect.value) {
            window.showError(neighborhoodSelect, 'Selecione um bairro.');
            isValid = false;
        }

        // Validação de Idade e Nacionalidade
        const idadeInput = document.getElementById('idade');
        if (idadeInput) {
            if (!idadeInput.value || idadeInput.value < 18 || idadeInput.value > 99) {
                window.showError(idadeInput, 'A idade deve estar entre 18 e 99 anos.');
                isValid = false;
            } else {
                window.removeError(idadeInput);
            }
        }

        const nacionalidadeInput = document.getElementById('nacionalidade');
        if (nacionalidadeInput) {
            if (!nacionalidadeInput.value.trim()) {
                window.showError(nacionalidadeInput, 'Preencha sua nacionalidade.');
                isValid = false;
            } else {
                window.removeError(nacionalidadeInput);
            }
        }

        // Validação de Descrição
        const descricaoInput = document.getElementById('descricao_sobre_mim');
        if (descricaoInput) {
            if (!descricaoInput.value.trim()) {
                window.showError(descricaoInput, 'Preencha a descrição sobre você.');
                isValid = false;
            } else {
                window.removeError(descricaoInput);
            }
        }

        // Validação de Serviços Oferecidos (mínimo 2)
        if (!validateMinCheckboxes('servicos-checkboxes', 'servicos', 2)) {
            isValid = false;
        }

        // Validação de Preços (pelo menos um preenchido)
        const precoInputs = document.querySelectorAll('input[name^="precos["]');
        let hasPrice = false;
        precoInputs.forEach(input => {
            if (input.value && parseFloat(input.value) > 0) {
                hasPrice = true;
            }
            window.removeError(input); // Limpa erros de preços individuais
        });
        const precoRow = document.getElementById('preco_15min')?.closest('.row'); // Pega o contêiner da linha de preços
        if (!hasPrice) {
            if (precoRow) {
                window.showError(precoRow, 'Preencha pelo menos um preço.');
            } else {
                console.warn("ERRO JS: Contêiner de preços não encontrado para exibir erro geral.");
                isValid = false; // Garante que a validação falhe mesmo sem elemento para erro
            }
            isValid = false;
        } else if (precoRow) {
            window.removeError(precoRow); // Remove o erro geral se houver preço
        }

        // Validação de Foto da Capa
        if (coverPhotoInput) {
            if (!coverPhotoInput.files || coverPhotoInput.files.length === 0) {
                window.showError(coverPhotoUploadBox, 'É obrigatório enviar uma foto de capa.');
                isValid = false;
            } else {
                window.removeError(coverPhotoUploadBox);
            }
        }

        // Validação de Aparência (mínimo 1)
        if (!validateMinCheckboxes('aparencia-checkboxes', 'aparencia', 1)) {
            isValid = false;
        }

        // Validação de Idiomas (mínimo 1)
        if (!validateMinCheckboxes('idiomas-checkboxes', 'idiomas', 1)) {
            isValid = false;
        }

        // Validação de Locais de Atendimento (mínimo 1)
        if (!validateMinCheckboxes('locais-checkboxes', 'locais_atendimento', 1)) {
            isValid = false;
        }

        // Validação de Formas de Pagamento (mínimo 1)
        if (!validateMinCheckboxes('pagamentos-checkboxes', 'formas_pagamento', 1)) {
            isValid = false;
        }

        if (!isValid) {
            window.showFeedbackModal('error', 'Por favor, corrija os erros no formulário.');
            // Rola para o primeiro erro visível
            const firstInvalid = document.querySelector('.is-invalid, .invalid-feedback-custom');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Se a validação do cliente passou, procede com a submissão via AJAX
        if (btnSubmitAnuncio) {
            originalButtonHTML = window.activateButtonLoading(btnSubmitAnuncio, 'CRIANDO...');
        } else {
            console.warn("AVISO JS: Botão de submit não encontrado. A submissão continuará sem feedback visual.");
        }


        const formData = new FormData(this);

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (result.status) {
                window.showFeedbackModal('success', result.message);
                formCriarAnuncio.reset(); // Limpa todos os campos do formulário
                
                // Resetar previews de imagem/video/audio
                document.querySelectorAll('.photo-preview').forEach(preview => {
                    preview.src = '#';
                    preview.style.display = 'none';
                });
                document.querySelectorAll('.upload-placeholder').forEach(placeholder => {
                    placeholder.style.display = 'flex';
                });
                document.querySelectorAll('.btn-remove-photo').forEach(btn => {
                    btn.classList.add('d-none');
                });
                // Recarregar estados para resetar dropdowns de localização
                loadStates();
            } else {
                window.showFeedbackModal('error', result.message);
                if (result.errors) {
                    for (const fieldName in result.errors) {
                        // Tenta encontrar o elemento pelo name ou id
                        let inputElement = document.querySelector(`[name="${fieldName}"]`);
                        if (!inputElement) {
                            inputElement = document.getElementById(fieldName);
                        }
                        
                        if (inputElement) {
                            window.showError(inputElement, result.errors[fieldName]);
                        } else {
                            console.warn(`ERRO JS: Campo ${fieldName} não encontrado para exibir erro. Erro: ${result.errors[fieldName]}`);
                        }
                    }
                    const firstInvalid = document.querySelector('.is-invalid, .invalid-feedback-custom');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }
        } catch (error) {
            console.error('ERRO JS: Falha na submissão do formulário:', error);
            window.showFeedbackModal('error', 'Ocorreu um erro ao tentar criar o anúncio. Tente novamente.');
        } finally {
            if (btnSubmitAnuncio && originalButtonHTML) {
                window.deactivateButtonLoading(btnSubmitAnuncio, originalButtonHTML);
            }
        }
    }
};

// Se este script for carregado tradicionalmente (sem SPA inicial),
// você pode querer chamar initializeAnuncioPage no DOMContentLoaded
// MAS, como estamos focando em SPA, o dashboard_custom.js será responsável por isso.
// Mantenha o DOMContentLoaded se este script também puder ser carregado de forma independente
// em páginas que não usam a funcionalidade SPA do dashboard.

/* Removido DOMContentLoaded daqui, pois a chamada será gerenciada pelo dashboard_custom.js
document.addEventListener('DOMContentLoaded', function() {
    // Apenas para o caso de a página de anúncio ser carregada diretamente sem AJAX
    if (document.getElementById('formCriarAnuncio')) {
        window.initializeAnuncioPage();
    }
});
*/