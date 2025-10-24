/**
 * ANÚNCIO FORMS - Manipulação de formulários
 * Versão: 3.0 (Modular Simples)
 */

console.log('📝 ANÚNCIO FORMS carregado');

window.AnuncioForms = {
    init: function(form, formMode, userRole, userPlanType) {
        console.log("✅ ANÚNCIO FORMS: Inicializando formulários");
        console.log("🔍 DEBUG: formMode:", formMode, "| userRole:", userRole, "| userPlanType:", userPlanType);
        this.setupFormSubmission(form, formMode, userRole, userPlanType);
    },

    setupFormSubmission: function(form, formMode, userRole, userPlanType) {
        if (form && !form.dataset.submitListenerAdded) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('🎯 DEBUG JS: Event listener de submit chamado!');
            console.log('INFO JS: submitAnuncioForm - Formulário submetido.');

            // Validar formulário antes de enviar
            if (window.AnuncioValidation && !window.AnuncioValidation.validateForm()) {
                console.log('❌ ANÚNCIO FORMS: Formulário inválido, cancelando envio');
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonHTML = window.activateButtonLoading(submitButton, 'Salvando...');

            window.showLoadingModal();

            const formData = new FormData(form);
            formData.append('form_mode', formMode);
            formData.append('user_plan_type', userPlanType);

            // Debug: Log dos dados do formulário
            console.log('🔍 ANÚNCIO FORMS: Dados do formulário:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}:`, value);
            }
            
            // Debug específico para arquivos
            console.log('🔍 ANÚNCIO FORMS: Verificando arquivos específicos:');
            console.log('  confirmation_video:', formData.get('confirmation_video'));
            console.log('  foto_capa:', formData.get('foto_capa'));
            console.log('  fotos_galeria_upload_0:', formData.get('fotos_galeria_upload_0'));
            console.log('  fotos_galeria_upload_1:', formData.get('fotos_galeria_upload_1'));
            
            // Verificar se os arquivos são realmente File objects
            const confirmationVideo = formData.get('confirmation_video');
            const fotoCapa = formData.get('foto_capa');
            const galeria0 = formData.get('fotos_galeria_upload_0');
            
            console.log('🔍 ANÚNCIO FORMS: Tipos dos arquivos:');
            console.log('  confirmation_video é File?', confirmationVideo instanceof File);
            console.log('  foto_capa é File?', fotoCapa instanceof File);
            console.log('  galeria0 é File?', galeria0 instanceof File);
            
            if (confirmationVideo instanceof File) {
                console.log('  confirmation_video size:', confirmationVideo.size, 'type:', confirmationVideo.type);
            }
            if (fotoCapa instanceof File) {
                console.log('  foto_capa size:', fotoCapa.size, 'type:', fotoCapa.type);
            }
            if (galeria0 instanceof File) {
                console.log('  galeria0 size:', galeria0.size, 'type:', galeria0.type);
            }
            
            // Debug específico para arquivos
            console.log('🔍 ANÚNCIO FORMS: Verificando arquivos específicos:');
            console.log('  confirmation_video:', formData.get('confirmation_video'));
            console.log('  foto_capa:', formData.get('foto_capa'));
            console.log('  fotos_galeria_upload_0:', formData.get('fotos_galeria_upload_0'));
            console.log('  fotos_galeria_upload_1:', formData.get('fotos_galeria_upload_1'));
            console.log('  fotos_galeria_upload_2:', formData.get('fotos_galeria_upload_2'));

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();
                console.log('🔍 DEBUG JS: Resposta do servidor:', result);
                    
                    // Debug: Log da resposta do servidor
                    console.log('🔍 ANÚNCIO FORMS: Resposta do servidor:', result);
                    if (result.errors) {
                        console.log('🔍 ANÚNCIO FORMS: Erros específicos:', result.errors);
                        console.log('🔍 ANÚNCIO FORMS: Detalhes dos erros:', JSON.stringify(result.errors, null, 2));
                    }
                    if (result.debug) {
                        console.log('🔍 ANÚNCIO FORMS: Debug do servidor:', result.debug);
                    }
                    if (result.message) {
                        console.log('🔍 ANÚNCIO FORMS: Mensagem do servidor:', result.message);
                    }

                    setTimeout(() => {
                        window.hideLoadingModal();
                        window.deactivateButtonLoading(submitButton, originalButtonHTML);

                        if (result.success) {
                            console.log('🎉 DEBUG JS: Sucesso detectado! Dados:', result);
                            
                            // Verificar se showFeedbackModal existe e está funcionando
                            if (typeof window.showFeedbackModal === 'function') {
                                try {
                                    console.log('INFO JS: Tentando mostrar modal de sucesso...');
                                    window.showFeedbackModal('success', result.message, 'Sucesso!', 2000);
                                    console.log('INFO JS: Modal de sucesso exibido com sucesso');
                                } catch (error) {
                                    console.error('ERRO JS: Erro ao mostrar modal de sucesso:', error);
                                    alert('Sucesso: ' + result.message);
                                }
                            } else {
                                console.warn('AVISO JS: showFeedbackModal não está disponível, usando alert');
                                alert('Sucesso: ' + result.message);
                            }
                            document.body.dataset.hasAnuncio = result.has_anuncio ? 'true' : 'false';
                            document.body.dataset.anuncioStatus = result.anuncio_status || 'not_found';
                            document.body.dataset.anuncioId = result.anuncio_id || '';
                            window.updateAnuncioSidebarLinks();

                            if (form.dataset.formMode === 'create' && result.anuncio_id) {
                                setTimeout(() => {
                                    window.location.href = `${window.URLADM}dashboard`;
                                }, 2000);
                            }
                        } else {
                            let errorMessage = result.message || 'Ocorreu um erro ao processar o anúncio.';
                            if (result.errors) {
                                for (const field in result.errors) {
                                    const feedbackElement = document.getElementById(`${field}-feedback`);
                                    if (feedbackElement) {
                                        feedbackElement.textContent = result.errors[field];
                                        feedbackElement.style.display = 'block';
                                    } else {
                                        errorMessage += `\n- ${result.errors[field]}`;
                                    }
                                }
                            }
                            // Verificar se showFeedbackModal existe e está funcionando
                            if (typeof window.showFeedbackModal === 'function') {
                                try {
                                    window.showFeedbackModal('error', errorMessage, 'Erro!');
                                } catch (error) {
                                    console.error('Erro ao mostrar modal de erro:', error);
                                    alert('Erro: ' + errorMessage);
                                }
                            } else {
                                alert('Erro: ' + errorMessage);
                            }
                        }
                    }, 2000);
                } catch (error) {
                    console.error('ERRO JS: Erro na requisição AJAX:', error);
                    setTimeout(() => {
                        window.hideLoadingModal();
                        window.deactivateButtonLoading(submitButton, originalButtonHTML);
                        // Verificar se showFeedbackModal existe e está funcionando
                        if (typeof window.showFeedbackModal === 'function') {
                            try {
                                window.showFeedbackModal('error', 'Erro de conexão. Por favor, tente novamente.', 'Erro de Rede');
                            } catch (error) {
                                console.error('Erro ao mostrar modal de conexão:', error);
                                alert('Erro de conexão. Por favor, tente novamente.');
                            }
                        } else {
                            alert('Erro de conexão. Por favor, tente novamente.');
                        }
                    }, 2000);
                }
            });
            form.dataset.submitListenerAdded = 'true';
            console.log("✅ ANÚNCIO FORMS: Listener de submissão configurado");
        }
    },

    populateFormFields: function(anuncioData, currentFormMode) {
        console.log("🔧 ANÚNCIO FORMS: Preenchendo campos do formulário");
        console.log("🔍 DEBUG: anuncioData:", anuncioData, "| formMode:", currentFormMode);

        if (!anuncioData) {
            console.log("⚠️ ANÚNCIO FORMS: Nenhum dado de anúncio fornecido");
            return;
        }

        // Preencher campos básicos
        const fields = [
            'service_name', 'age', 'phone_number', 'description', 'gender', 
            'nationality', 'ethnicity', 'eye_color', 'neighborhood_name',
            'state_id', 'city_id'
        ];

        fields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field && anuncioData[fieldName]) {
                field.value = anuncioData[fieldName];
                console.log(`DEBUG JS: Campo ${fieldName} preenchido com:`, anuncioData[fieldName]);
            }
        });

        // Preencher altura e peso
        if (anuncioData.height_m) {
            const heightField = document.getElementById('height_m');
            if (heightField) {
                heightField.value = anuncioData.height_m;
                console.log(`DEBUG JS: Campo height_m preenchido com: ${anuncioData.height_m} (anuncioData original: ${anuncioData.height_m})`);
            }
        }

        if (anuncioData.weight_kg) {
            const weightField = document.getElementById('weight_kg');
            if (weightField) {
                weightField.value = anuncioData.weight_kg;
                console.log(`DEBUG JS: Campo weight_kg preenchido com: ${anuncioData.weight_kg} (anuncioData: ${anuncioData.weight_kg})`);
            }
        }

        // Preencher preços
        const priceFields = ['price_15min', 'price_30min', 'price_1h'];
        priceFields.forEach(priceField => {
            const field = document.getElementById(priceField);
            if (field && anuncioData[priceField]) {
                // O valor vem do PHP já formatado com vírgula (ex: "15,00")
                // Precisamos converter para o formato que o inputmask espera
                // Converter do formato brasileiro para float corretamente
                let cleanValue;
                if (anuncioData[priceField].includes(',')) {
                    // Se tem vírgula, é o separador decimal brasileiro
                    const parts = anuncioData[priceField].split(',');
                    const integerPart = parts[0].replace(/\./g, ''); // Remove pontos de milhares
                    const decimalPart = parts[1] || '00';
                    cleanValue = integerPart + '.' + decimalPart;
                } else {
                    // Se não tem vírgula, pode ter pontos de milhares
                    cleanValue = anuncioData[priceField].replace(/\./g, '');
                }
                
                const numericValue = parseFloat(cleanValue);
                if (!isNaN(numericValue)) {
                    // Limpar o campo antes de definir o valor
                    field.value = '';
                    // Definir o valor numérico puro para o inputmask processar
                    field.value = numericValue.toString();
                    console.log(`DEBUG JS: Campo ${priceField} - anuncioData: ${anuncioData[priceField]}, cleanValue: ${cleanValue}, numericValue: ${numericValue}`);
                } else {
                    console.warn(`DEBUG JS: Valor inválido para ${priceField}: ${anuncioData[priceField]}`);
                }
            }
        });

        // Preencher checkboxes
        const checkboxGroups = [
            'aparencia', 'idiomas', 'locais_atendimento', 'formas_pagamento', 'servicos'
        ];

        checkboxGroups.forEach(groupName => {
            const checkboxes = document.querySelectorAll(`input[name="${groupName}[]"]`);
            const existingValues = anuncioData[groupName] || [];
            
            checkboxes.forEach(checkbox => {
                if (existingValues.includes(checkbox.value)) {
                    checkbox.checked = true;
                }
            });
            
            console.log(`DEBUG JS: Checkboxes para ${groupName} preenchidos. Valores existentes:`, existingValues);
        });

        // Preencher ID do anúncio se estiver editando
        if (currentFormMode === 'edit' && anuncioData.id) {
            const anuncioIdField = document.getElementById('anuncio_id');
            if (anuncioIdField) {
                anuncioIdField.value = anuncioData.id;
                console.log(`DEBUG JS: Campo anuncio_id preenchido com: ${anuncioData.id}`);
            }
        }

        console.log("✅ ANÚNCIO FORMS: Campos do formulário preenchidos");
    }
};

console.log("✅ ANÚNCIO FORMS: Módulo carregado e pronto");
