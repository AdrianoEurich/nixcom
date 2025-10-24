/**
 * MÁSCARAS FUNCIONAIS v4.0 - Versão Ultra Simplificada
 */

console.log('✨ mascaras_funcionais.js v4.0 carregado! ✨');

// ========================================
// FUNÇÃO PRINCIPAL DE INICIALIZAÇÃO
// ========================================

// Flag para evitar inicialização dupla
let formInitialized = false;

function setupCompleteForm() {
    console.log('🚀 Inicializando formulário completo...');
    
    // Verificar se já foi inicializado
    if (formInitialized) {
        console.log('⚠️ Formulário já inicializado, pulando...');
        return;
    }
    
    // Pular em páginas que não precisam (ex.: pagamento)
    const isPaymentPage = document.getElementById('paymentContent') || window.location.href.includes('pagamento');
    if (isPaymentPage) {
        console.log('🧾 Página de pagamento detectada - mascaras_funcionais.js não interfere');
        return;
    }

    // Verificar se estamos na página de anúncio - se sim, pular inicialização
    const isAnuncioPage = document.querySelector('form[id="formAnuncio"]') || 
                         document.querySelector('form[data-form-mode]') ||
                         window.location.href.includes('anuncio') ||
                         window.location.href.includes('create') ||
                         window.location.href.includes('edit');
    
    if (isAnuncioPage) {
        console.log('📝 Página de anúncio detectada - mascaras_funcionais.js não interfere');
        return;
    }
    
    // Aguardar jQuery estar disponível
    if (typeof $ === 'undefined') {
        console.error('ERRO: jQuery não está disponível');
        setTimeout(setupCompleteForm, 100);
        return;
    }
    
    // Aguardar Inputmask estar disponível
    if (typeof $.fn.inputmask === 'undefined') {
        console.error('ERRO: jQuery.Inputmask não está disponível');
        setTimeout(setupCompleteForm, 100);
        return;
    }
    
    console.log('✅ jQuery e Inputmask disponíveis, aplicando máscaras...');
    
    // Marcar como inicializado
    formInitialized = true;
    
    // Aplicar máscaras
    applyMasks();
    
    // Configurar uploads - REMOVIDO para evitar duplicação com anuncio.js
    // setupUploads();
    
    // Configurar estados e cidades - APENAS se não estiver no modo de edição
    // No modo de edição, o anuncio.js já carrega os dados corretos do banco
    const isEditMode = document.querySelector('form[data-form-mode="edit"]') || 
                      document.querySelector('input[name="form_mode"][value="edit"]') ||
                      window.location.href.includes('editarAnuncio');
    
    if (!isEditMode) {
        console.log('🌍 mascaras_funcionais.js - Configurando estados e cidades (modo criação)');
        setTimeout(() => {
            setupStatesAndCities();
        }, 200);
    } else {
        console.log('🌍 mascaras_funcionais.js - Modo edição detectado, pulando configuração de estados/cidades (anuncio.js já carrega do banco)');
    }
    
    console.log('✅ Formulário inicializado com sucesso!');
}

// ========================================
// FUNÇÕES DE MÁSCARAS
// ========================================

function applyMasks() {
    console.log('📝 Aplicando máscaras...');
    
    // Máscara de telefone
    const phone = document.getElementById('phone_number');
    if (phone) {
        $(phone).inputmask({ "mask": "(99) 99999-9999" });
        console.log('📞 Telefone configurado');
    }
    
    // Máscara de idade
    const age = document.getElementById('age');
    if (age) {
        $(age).inputmask("99", { numericInput: true, placeholder: "" });
        console.log('🎂 Idade configurada');
    }
    
    // Máscara de altura
    const height = document.getElementById('height_m');
    if (height) {
        $(height).inputmask({
            mask: "9,99",
            placeholder: "0,00",
            onBeforeMask: function (value) {
                return value.replace('.', ',');
            }
        });
        console.log('📏 Altura configurada');
    }
    
    // Máscara de peso
    const weight = document.getElementById('weight_kg');
    if (weight) {
        $(weight).inputmask({
            mask: "999",
            numericInput: true,
            placeholder: ""
        });
        console.log('⚖️ Peso configurado');
    }
    
    // Máscaras de preços
    ['price_15min', 'price_30min', 'price_1h'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            $(input).inputmask({
                alias: 'numeric',
                groupSeparator: '.',
                radixPoint: ',',
                digits: 2,
                autoGroup: true
            });
            console.log(`💰 Preço ${id} configurado`);
        }
    });
}

// ========================================
// FUNÇÕES DE UPLOAD
// ========================================

function setupUploads() {
    console.log('📁 Configurando uploads...');
    
    // Foto da capa
    const coverInput = document.getElementById('foto_capa_input');
    const coverBox = document.getElementById('coverPhotoUploadBox');
    const coverPreview = document.getElementById('coverPhotoPreview');
    
    if (coverInput && coverBox && coverPreview) {
        // Verificar se já foi configurado
        if (coverInput.dataset.configured === 'true') {
            console.log('⚠️ Upload da capa já configurado, pulando...');
            return;
        }
        
        coverInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                coverPreview.src = url;
                coverPreview.style.display = 'block';
                const placeholder = coverBox.querySelector('.upload-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                console.log('📸 Foto da capa selecionada');
            }
        });
        
        coverBox.addEventListener('click', function(e) {
            if (e.target !== coverInput) {
                console.log('📸 Clique na caixa da capa');
                coverInput.click();
            }
        });
        
        coverInput.dataset.configured = 'true';
        console.log('✅ Upload da capa configurado');
    }
    
    // Galeria de fotos
    for (let i = 0; i < 20; i++) {
        const input = document.getElementById(`gallery_photo_input_${i}`);
        const box = input?.closest('.photo-upload-box');
        const preview = document.getElementById(`galleryPhotoPreview_${i}`);
        
        if (input && box && preview) {
            // Verificar se já foi configurado
            if (input.dataset.configured === 'true') {
                continue;
            }
            
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.style.display = 'block';
                    const placeholder = box.querySelector('.upload-placeholder');
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    console.log(`📸 Galeria ${i} selecionada`);
                }
            });
            
            box.addEventListener('click', function(e) {
                if (e.target !== input) {
                    console.log(`📸 Clique na galeria ${i}`);
                    input.click();
                }
            });
            
            input.dataset.configured = 'true';
        }
    }
    
    // Vídeo de confirmação
    const confInput = document.getElementById('confirmation_video_input');
    const confBox = document.getElementById('video-confirmation-box') || confInput?.closest('.photo-upload-box');
    const confPreview = document.getElementById('confirmationVideoPreview');
    
    console.log('🔍 Elementos do vídeo de confirmação:', {
        input: !!confInput,
        box: !!confBox,
        preview: !!confPreview
    });
    
    if (confInput && confBox && confPreview) {
        // Verificar se já foi configurado
        if (confInput.dataset.configured === 'true') {
            console.log('⚠️ Upload de confirmação já configurado, pulando...');
            return;
        }
        
        confInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                confPreview.src = url;
                confPreview.style.display = 'block';
                confPreview.controls = true;
                const placeholder = confBox.querySelector('.upload-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                console.log('🎥 Vídeo de confirmação selecionado');
            }
        });
        
        confBox.addEventListener('click', function(e) {
            if (e.target !== confInput) {
                console.log('🎥 Clique na caixa do vídeo de confirmação');
                confInput.click();
            }
        });
        
        confInput.dataset.configured = 'true';
        console.log('✅ Upload de confirmação configurado');
    } else {
        console.warn('⚠️ Elementos do vídeo de confirmação não encontrados');
    }
}

// ========================================
// FUNÇÕES DE ESTADOS E CIDADES
// ========================================

let statesData = null;
let citiesData = null;

async function setupStatesAndCities() {
    console.log('🌍 Configurando estados e cidades...');
    
    // Procurar elementos de estado e cidade primeiro; se não existirem, não carregar dados
    let stateSelect = document.getElementById('state_id');
    let citySelect = document.getElementById('city_id');
    if (!stateSelect) {
        stateSelect = document.querySelector('select[name="state_id"]');
    }
    if (!citySelect) {
        citySelect = document.querySelector('select[name="city_id"]');
    }
    if (!stateSelect || !citySelect) {
        console.log('⚠️ Elementos de estado/cidade não presentes na página atual - abortando carga de dados');
        return;
    }

    // Carregar dados dos arquivos JSON
    try {
        console.log('📡 Carregando estados...');
        const statesResponse = await fetch(`${window.URLADM}assets/js/data/states.json`);
        if (!statesResponse.ok) {
            throw new Error(`Erro HTTP: ${statesResponse.status}`);
        }
        const statesJson = await statesResponse.json();
        statesData = statesJson.data; // O JSON tem uma propriedade 'data'
        console.log('✅ Estados carregados:', statesData.length);
        
        console.log('📡 Carregando cidades...');
        const citiesResponse = await fetch(`${window.URLADM}assets/js/data/cities.json`);
        if (!citiesResponse.ok) {
            throw new Error(`Erro HTTP: ${citiesResponse.status}`);
        }
        const citiesJson = await citiesResponse.json();
        citiesData = citiesJson.data; // O JSON tem uma propriedade 'data'
        console.log('✅ Cidades carregadas:', citiesData.length);
        
    } catch (error) {
        console.error('ERRO ao carregar dados:', error);
        console.log('🔄 Usando dados de fallback...');
        
        // Fallback com dados básicos
        statesData = [
            { Id: 1, Nome: "Acre", Uf: "AC" },
            { Id: 2, Nome: "Alagoas", Uf: "AL" },
            { Id: 3, Nome: "Amapá", Uf: "AP" },
            { Id: 4, Nome: "Amazonas", Uf: "AM" },
            { Id: 5, Nome: "Bahia", Uf: "BA" },
            { Id: 6, Nome: "Ceará", Uf: "CE" },
            { Id: 7, Nome: "Distrito Federal", Uf: "DF" },
            { Id: 8, Nome: "Espírito Santo", Uf: "ES" },
            { Id: 9, Nome: "Goiás", Uf: "GO" },
            { Id: 10, Nome: "Maranhão", Uf: "MA" },
            { Id: 11, Nome: "Mato Grosso", Uf: "MT" },
            { Id: 12, Nome: "Mato Grosso do Sul", Uf: "MS" },
            { Id: 13, Nome: "Minas Gerais", Uf: "MG" },
            { Id: 14, Nome: "Pará", Uf: "PA" },
            { Id: 15, Nome: "Paraíba", Uf: "PB" },
            { Id: 16, Nome: "Paraná", Uf: "PR" },
            { Id: 17, Nome: "Pernambuco", Uf: "PE" },
            { Id: 18, Nome: "Piauí", Uf: "PI" },
            { Id: 19, Nome: "Rio de Janeiro", Uf: "RJ" },
            { Id: 20, Nome: "Rio Grande do Norte", Uf: "RN" },
            { Id: 21, Nome: "Rio Grande do Sul", Uf: "RS" },
            { Id: 22, Nome: "Rondônia", Uf: "RO" },
            { Id: 23, Nome: "Roraima", Uf: "RR" },
            { Id: 24, Nome: "Santa Catarina", Uf: "SC" },
            { Id: 25, Nome: "São Paulo", Uf: "SP" },
            { Id: 26, Nome: "Sergipe", Uf: "SE" },
            { Id: 27, Nome: "Tocantins", Uf: "TO" }
        ];
        
        citiesData = [
            { Id: 1, Nome: "Rio Branco", Uf: "AC" },
            { Id: 2, Nome: "Maceió", Uf: "AL" },
            { Id: 3, Nome: "Macapá", Uf: "AP" },
            { Id: 4, Nome: "Manaus", Uf: "AM" },
            { Id: 5, Nome: "Salvador", Uf: "BA" },
            { Id: 6, Nome: "Fortaleza", Uf: "CE" },
            { Id: 7, Nome: "Brasília", Uf: "DF" },
            { Id: 8, Nome: "Vitória", Uf: "ES" },
            { Id: 9, Nome: "Goiânia", Uf: "GO" },
            { Id: 10, Nome: "São Luís", Uf: "MA" },
            { Id: 11, Nome: "Cuiabá", Uf: "MT" },
            { Id: 12, Nome: "Campo Grande", Uf: "MS" },
            { Id: 13, Nome: "Belo Horizonte", Uf: "MG" },
            { Id: 14, Nome: "Belém", Uf: "PA" },
            { Id: 15, Nome: "João Pessoa", Uf: "PB" },
            { Id: 16, Nome: "Curitiba", Uf: "PR" },
            { Id: 17, Nome: "Recife", Uf: "PE" },
            { Id: 18, Nome: "Teresina", Uf: "PI" },
            { Id: 19, Nome: "Rio de Janeiro", Uf: "RJ" },
            { Id: 20, Nome: "Natal", Uf: "RN" },
            { Id: 21, Nome: "Porto Alegre", Uf: "RS" },
            { Id: 22, Nome: "Porto Velho", Uf: "RO" },
            { Id: 23, Nome: "Boa Vista", Uf: "RR" },
            { Id: 24, Nome: "Florianópolis", Uf: "SC" },
            { Id: 25, Nome: "São Paulo", Uf: "SP" },
            { Id: 26, Nome: "Aracaju", Uf: "SE" },
            { Id: 27, Nome: "Palmas", Uf: "TO" }
        ];
        
        console.log('✅ Dados de fallback carregados');
    }
    
    console.log('🔍 Elementos encontrados:', {
        stateSelect: !!stateSelect,
        citySelect: !!citySelect,
        stateId: stateSelect?.id || stateSelect?.name,
        cityId: citySelect?.id || citySelect?.name
    });
    
    if (!stateSelect || !citySelect) {
        console.log('⚠️ Elementos de estado/cidade não encontrados ainda - será configurado quando o formulário carregar');
        console.log('🔍 Todos os selects na página:', document.querySelectorAll('select'));
        return;
    }
    
    // Popular estados
    console.log('📝 Populando estados...');
    stateSelect.innerHTML = '<option value="">Selecione o estado</option>';
    
    statesData.forEach(state => {
        const option = document.createElement('option');
        option.value = state.Uf;
        option.textContent = state.Nome;
        stateSelect.appendChild(option);
    });
    
    console.log('✅ Estados populados:', stateSelect.children.length - 1, 'opções');
    
    // Event listener para mudança de estado
    stateSelect.addEventListener('change', function() {
        const selectedState = this.value;
        console.log('🌍 Estado selecionado:', selectedState);
        
        // Limpar e habilitar cidade
        citySelect.innerHTML = '<option value="">Selecione a cidade</option>';
        citySelect.disabled = false;
        
        if (selectedState) {
            const stateCities = citiesData.filter(city => city.Uf === selectedState);
            console.log(`🔍 Cidades encontradas para ${selectedState}:`, stateCities.length);
            
            stateCities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.Id;
                option.textContent = city.Nome;
                citySelect.appendChild(option);
            });
            
            console.log(`✅ Cidades carregadas: ${stateCities.length} opções`);
        }
    });
    
    // Event listener para mudança de cidade
    citySelect.addEventListener('change', function() {
        const selectedCity = this.value;
        console.log('🏙️ Cidade selecionada:', selectedCity);
        
        // Habilitar campo de bairro quando cidade for selecionada
        const neighborhoodInput = document.getElementById('neighborhood_name');
        if (neighborhoodInput) {
            if (selectedCity) {
                neighborhoodInput.disabled = false;
                neighborhoodInput.placeholder = 'Digite o nome do bairro';
                console.log('✅ Campo de bairro habilitado');
            } else {
                neighborhoodInput.disabled = true;
                neighborhoodInput.placeholder = 'Selecione a cidade primeiro';
                console.log('⚠️ Campo de bairro desabilitado');
            }
        }
    });
    
    console.log('✅ Estados e cidades configurados com sucesso!');
}

// ========================================
// INICIALIZAÇÃO AUTOMÁTICA
// ========================================

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCompleteForm);
} else {
    setupCompleteForm();
}

// Função para resetar inicialização (para SPA)
function resetFormInitialization() {
    formInitialized = false;
    console.log('🔄 Flag de inicialização resetada');
}

// Tornar funções globais para SPA
window.setupCompleteForm = setupCompleteForm;
window.resetFormInitialization = resetFormInitialization;