/**
 * ANÚNCIO LOCATIONS - Estados e cidades
 * Versão: 3.0 (Modular Simples)
 */

console.log('🌍 ANÚNCIO LOCATIONS carregado');

window.AnuncioLocations = {
    // Cache para dados JSON
    cache: {
        states: null,
        cities: null,
        neighborhoods: null
    },
    
    init: async function(form, formMode, anuncioData) {
        console.log("✅ ANÚNCIO LOCATIONS: Inicializando localizações");
        console.log("🔍 DEBUG: formMode:", formMode);
        console.log("🔍 DEBUG: form:", form);
        console.log("🔍 DEBUG: anuncioData:", anuncioData);
        await this.loadAndPopulateLocations(form, anuncioData);
    },

    loadAndPopulateLocations: async function(form, anuncioData) {
        console.log('🔍 ANÚNCIO LOCATIONS: Procurando elementos de localização...');
        const ufSelect = form.querySelector('#state_id');
        const citySelect = form.querySelector('#city_id');
        const neighborhoodInput = form.querySelector('#neighborhood_name');

        console.log('🔍 ANÚNCIO LOCATIONS: Elementos encontrados:');
        console.log('  - ufSelect (state_id):', !!ufSelect, ufSelect);
        console.log('  - citySelect (city_id):', !!citySelect, citySelect);
        console.log('  - neighborhoodInput (neighborhood_name):', !!neighborhoodInput, neighborhoodInput);

        if (!ufSelect || !citySelect) {
            console.warn('❌ ANÚNCIO LOCATIONS: Elementos de localização não encontrados:');
            console.warn('  - ufSelect (state_id):', !!ufSelect);
            console.warn('  - citySelect (city_id):', !!citySelect);
            return;
        }
        
        console.log('✅ DEBUG JS: Elementos de localização encontrados, iniciando carregamento...');

        const initialUf = anuncioData?.state_id;
        const initialCityCode = anuncioData?.city_id;
        const initialNeighborhoodName = anuncioData?.neighborhood_name;

        console.log('DEBUG JS: loadAndPopulateLocations - Initial UF:', initialUf);
        console.log('DEBUG JS: loadAndPopulateLocations - Initial City Code:', initialCityCode);
        console.log('DEBUG JS: loadAndPopulateLocations - Initial Neighborhood Name:', initialNeighborhoodName);
        console.log('DEBUG JS: loadAndPopulateLocations - anuncioData completo:', anuncioData);

        try {
            const statesUrl = `${window.URLADM}assets/js/data/states.json`;
            console.log('DEBUG JS: Fetching states from URL:', statesUrl);
            const responseStates = await fetch(statesUrl);

            if (!responseStates.ok) {
                const errorText = await responseStates.text();
                console.error(`ERRO JS: loadAndPopulateLocations - Falha ao carregar estados. Status: ${responseStates.status}, Texto: ${errorText}`);
                throw new Error(`Falha ao carregar estados: ${responseStates.statusText}`);
            }
            const dataStates = await responseStates.json();

            if (!dataStates || !dataStates.data || !Array.isArray(dataStates.data)) {
                console.error('ERRO JS: loadAndPopulateLocations - Estrutura de dados de estados inválida:', dataStates);
                throw new Error('Estrutura de dados de estados inválida.');
            }

            console.log('INFO JS: Estados carregados e populados.');
            this.populateStatesSelect(ufSelect, dataStates.data, initialUf);

            // Event listener para mudança de estado
            ufSelect.addEventListener('change', async (event) => {
                const selectedUf = event.target.value;
                console.log('🌍 Estado selecionado:', selectedUf);
                console.log('🔍 ANÚNCIO LOCATIONS: Event listener de mudança de estado disparado');
                
                // Limpar e habilitar cidade
                citySelect.innerHTML = '<option value="">Selecione a Cidade</option>';
                citySelect.disabled = false;
                console.log('🔍 ANÚNCIO LOCATIONS: Campo de cidade limpo e habilitado');
                
                if (selectedUf) {
                    console.log('🔍 ANÚNCIO LOCATIONS: Carregando cidades para UF:', selectedUf);
                    try {
                        await this.loadCitiesForState(citySelect, selectedUf);
                        console.log('✅ ANÚNCIO LOCATIONS: Cidades carregadas com sucesso');
                    } catch (error) {
                        console.error('❌ ANÚNCIO LOCATIONS: Erro ao carregar cidades:', error);
                    }
                }
            });

            // Event listener para mudança de cidade
            citySelect.addEventListener('change', (event) => {
                const selectedCity = event.target.value;
                console.log('🏙️ Cidade selecionada:', selectedCity);
                
                // Habilitar campo de bairro quando cidade for selecionada (se o campo existir)
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

            // Pré-selecionar valores se estiver no modo de edição
            if (formMode === 'edit' && initialUf) {
                ufSelect.value = initialUf;
                ufSelect.dispatchEvent(new Event('change'));
                
                if (initialCityCode) {
                    setTimeout(async () => {
                        console.log('🏙️ ANÚNCIO LOCATIONS: Carregando cidades para estado:', initialUf, 'e pré-selecionando cidade:', initialCityCode);
                        await this.loadCitiesForState(citySelect, initialUf, initialCityCode);
                    }, 1000);
                }
                
                if (neighborhoodInput && initialNeighborhoodName) {
                    neighborhoodInput.value = initialNeighborhoodName;
                    neighborhoodInput.disabled = false;
                    console.log('✅ ANÚNCIO LOCATIONS: Bairro pré-selecionado:', initialNeighborhoodName);
                }
            }

        } catch (error) {
            console.error('ERRO JS: loadAndPopulateLocations - Erro geral ao carregar localizações:', error);
            window.showFeedbackModal('error', 'Não foi possível carregar os dados de localização. Verifique os caminhos dos arquivos JSON e os logs do navegador.', 'Erro de Localização');
            throw error;
        }
        console.log('INFO JS: loadAndPopulateLocations - Localização carregada e populada.');
    },

    populateStatesSelect: function(ufSelect, statesData, initialUf) {
        console.log('📝 Populando estados...');
        ufSelect.innerHTML = '<option value="">Selecione o estado</option>';
        
        statesData.forEach(state => {
            const option = document.createElement('option');
            option.value = state.Uf;
            option.textContent = state.Nome;
            ufSelect.appendChild(option);
        });
        
        console.log('✅ Estados populados:', ufSelect.children.length - 1, 'opções');
    },

    loadCitiesForState: async function(citySelect, uf, initialCityCode = null) {
        try {
            const citiesUrl = `${window.URLADM}assets/js/data/cities.json`;
            console.log('🔍 ANÚNCIO LOCATIONS: Carregando cidades para UF:', uf);
            console.log('🔍 ANÚNCIO LOCATIONS: URL das cidades:', citiesUrl);
            const responseCities = await fetch(citiesUrl);

            if (!responseCities.ok) {
                const errorText = await responseCities.text();
                console.error(`❌ ANÚNCIO LOCATIONS: Falha ao carregar cidades para UF ${uf}. Status: ${responseCities.status}, Texto: ${errorText}`);
                throw new Error(`Falha ao carregar cidades: ${responseCities.statusText}`);
            }
            const dataCities = await responseCities.json();
            console.log('🔍 ANÚNCIO LOCATIONS: Dados de cidades carregados:', dataCities ? 'SIM' : 'NÃO');

            if (!dataCities || !dataCities.data || !Array.isArray(dataCities.data)) {
                console.error('❌ ANÚNCIO LOCATIONS: Estrutura de dados de cidades inválida:', dataCities);
                throw new Error('Estrutura de dados de cidades inválida.');
            }

            const stateCities = dataCities.data.filter(city => city.Uf === uf);
            console.log(`🔍 ANÚNCIO LOCATIONS: Cidades encontradas para ${uf}:`, stateCities.length);
            console.log(`🔍 ANÚNCIO LOCATIONS: Primeiras 3 cidades:`, stateCities.slice(0, 3));
            
            // Limpar o select antes de adicionar as opções
            citySelect.innerHTML = '<option value="">Selecione a Cidade</option>';
            
            stateCities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.Codigo;
                option.textContent = city.Nome;
                citySelect.appendChild(option);
            });
            
            console.log(`✅ ANÚNCIO LOCATIONS: Cidades carregadas: ${stateCities.length} opções`);
            console.log(`🔍 ANÚNCIO LOCATIONS: Total de opções no select:`, citySelect.children.length);
            
            // Pré-selecionar cidade se fornecida
            if (initialCityCode) {
                console.log('🏙️ ANÚNCIO LOCATIONS: Pré-selecionando cidade:', initialCityCode);
                citySelect.value = initialCityCode;
                citySelect.dispatchEvent(new Event('change'));
                console.log('✅ ANÚNCIO LOCATIONS: Cidade pré-selecionada com sucesso');
            }
            
        } catch (error) {
            console.error('❌ ANÚNCIO LOCATIONS: Erro ao carregar cidades:', error);
            throw error;
        }
    }
};

console.log("✅ ANÚNCIO LOCATIONS: Módulo carregado e pronto");
