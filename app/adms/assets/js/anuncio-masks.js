/**
 * ANÚNCIO MASKS - Máscaras de entrada
 * Versão: 3.0 (Modular Simples)
 */

console.log('🎭 ANÚNCIO MASKS carregado');

window.AnuncioMasks = {
    init: function(form) {
        console.log("✅ ANÚNCIO MASKS: Inicializando máscaras");
        this.applyMasks(form);
    },

    applyMasks: function(form) {
        console.log('🎭 ANÚNCIO MASKS: Aplicando máscaras nos inputs');
        
        // Verificar se jQuery e Inputmask estão disponíveis
        if (typeof $ === 'undefined' || typeof $.fn.inputmask === 'undefined') {
            console.warn('⚠️ ANÚNCIO MASKS: jQuery ou Inputmask não estão disponíveis, aguardando...');
            console.log('DEBUG JS: jQuery disponível:', typeof $ !== 'undefined');
            console.log('DEBUG JS: $.fn.inputmask disponível:', typeof $ !== 'undefined' && typeof $.fn.inputmask !== 'undefined');
            
            // Usar a função global de espera se disponível
            if (typeof window.waitForJQuery === 'function') {
                console.log('🔄 ANÚNCIO MASKS: Usando waitForJQuery...');
                window.waitForJQuery(() => {
                    this.applyMasks(form);
                });
            } else {
                // Fallback: aguardar um pouco e tentar novamente
                setTimeout(() => {
                    this.applyMasks(form);
                }, 500);
            }
            return;
        }

        console.log('✅ ANÚNCIO MASKS: jQuery e Inputmask disponíveis, aplicando máscaras...');
        
        // Máscara de telefone com limite
        const phoneNumberInput = form.querySelector('#phone_number');
        if (phoneNumberInput) {
            $(phoneNumberInput).inputmask({ 
                "mask": "(99) 99999-9999",
                "maxLength": 15
            });
            // Adicionar limite adicional no HTML
            phoneNumberInput.setAttribute('maxlength', '15');
            console.log('✅ Máscara de telefone aplicada com limite');
        } else {
            console.warn('⚠️ ANÚNCIO MASKS: Campo de telefone não encontrado');
        }

        // Máscara de idade
        const ageInput = form.querySelector('#age');
        if (ageInput) {
            $(ageInput).inputmask("99", { numericInput: true, placeholder: "" });
            console.log('✅ Máscara de idade aplicada');
        } else {
            console.warn('⚠️ ANÚNCIO MASKS: Campo de idade não encontrado');
        }

        // Máscara de altura
        const heightInput = form.querySelector('#height_m');
        if (heightInput) {
            $(heightInput).inputmask({
                mask: "9,99",
                numericInput: false,
                placeholder: "0,00",
                rightAlign: false,
                onBeforeMask: function (value, opts) {
                    if (value !== null && value !== undefined && value !== '') {
                        let stringValue = String(value);
                        stringValue = stringValue.replace('.', ',');
                        if (stringValue.match(/^\d$/)) {
                            stringValue += ',00';
                        } else if (stringValue.match(/^\d,$/)) {
                            stringValue += '00';
                        } else if (stringValue.match(/^\d,\d$/)) {
                            stringValue += '0';
                        }
                        return stringValue;
                    }
                    return value;
                },
                onUnMask: function (maskedValue, unmaskedValue) {
                    if (!unmaskedValue) return '0.00';
                    let num = parseFloat(unmaskedValue) / 100;
                    return num.toFixed(2);
                }
            });
            console.log('✅ Máscara de altura aplicada');
        }

        // Máscara de peso
        const weightInput = form.querySelector('#weight_kg');
        if (weightInput) {
            $(weightInput).inputmask({
                mask: "999",
                numericInput: true,
                placeholder: "",
                rightAlign: false,
                clearMaskOnLostFocus: false,
                onBeforeMask: function (value, opts) {
                    return String(value).replace(/\D/g, '');
                },
                onUnMask: function (maskedValue, unmaskedValue) {
                    return unmaskedValue || '0';
                }
            });
            console.log('✅ Máscara de peso aplicada');
        }

        // Máscaras de preços
        const priceInputs = ['price_15min', 'price_30min', 'price_1h'];
        priceInputs.forEach(id => {
            const input = form.querySelector(`#${id}`);
            if (input) {
                $(input).inputmask({
                    alias: 'numeric',
                    groupSeparator: '.',
                    radixPoint: ',',
                    autoGroup: false, // Desabilitado para evitar formatação automática incorreta
                    digits: 2,
                    digitsOptional: false,
                    prefix: '', // Removido R$ pois já existe no HTML
                    placeholder: "0,00",
                    rightAlign: false,
                    clearMaskOnLostFocus: false,
                    max: 999999.99, // Suporte a valores até 999.999,99
                    onBeforeMask: function (value, opts) {
                        // Remove apenas R$ e espaços, mantém pontos e vírgulas
                        const cleanedValue = String(value).replace(/[R$\s]/g, '');
                        console.log(`DEBUG MASK: onBeforeMask - Input: ${value}, Output: ${cleanedValue}`);
                        return cleanedValue;
                    },
                    onUnMask: function (maskedValue, unmaskedValue) {
                        if (!unmaskedValue) return '0.00';
                        console.log(`DEBUG MASK: onUnMask - maskedValue: ${maskedValue}, unmaskedValue: ${unmaskedValue}`);
                        
                        // Se tem vírgula, é o separador decimal brasileiro
                        if (unmaskedValue.includes(',')) {
                            // Remove pontos de milhares (antes da vírgula) e converte vírgula para ponto
                            const parts = unmaskedValue.split(',');
                            const integerPart = parts[0].replace(/\./g, ''); // Remove pontos de milhares
                            const decimalPart = parts[1] || '00';
                            const result = parseFloat(integerPart + '.' + decimalPart).toFixed(2);
                            console.log(`DEBUG MASK: onUnMask - parts: [${parts.join(', ')}], integerPart: ${integerPart}, decimalPart: ${decimalPart}, result: ${result}`);
                            return result;
                        } else {
                            // Se não tem vírgula, pode ter pontos de milhares
                            const cleanValue = unmaskedValue.replace(/\./g, '');
                            const result = parseFloat(cleanValue).toFixed(2);
                            console.log(`DEBUG MASK: onUnMask - cleanValue: ${cleanValue}, result: ${result}`);
                            return result;
                        }
                    },
                    onComplete: function () {
                        // Formatar corretamente o valor quando o usuário termina de digitar
                        const value = this.val();
                        console.log(`DEBUG MASK: onComplete - Valor atual: ${value}`);
                        
                        if (value && value !== '0,00') {
                            let formattedValue = value;
                            
                            // Se o valor não tem vírgula, adicionar ,00
                            if (!value.includes(',')) {
                                formattedValue = value + ',00';
                            }
                            // Se o valor tem vírgula mas não tem 2 casas decimais, completar
                            else if (value.includes(',')) {
                                const parts = value.split(',');
                                if (parts[1] && parts[1].length === 1) {
                                    formattedValue = parts[0] + ',' + parts[1] + '0';
                                } else if (!parts[1]) {
                                    formattedValue = parts[0] + ',00';
                                }
                            }
                            
                            // Adicionar separadores de milhares se necessário
                            const parts = formattedValue.split(',');
                            if (parts[0].length > 3) {
                                const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                formattedValue = integerPart + ',' + parts[1];
                            }
                            
                            this.val(formattedValue);
                        }
                    }
                });
                console.log(`✅ Máscara de preço ${id} aplicada`);
            }
        });

        console.log("✅ ANÚNCIO MASKS: Todas as máscaras aplicadas");
        console.log("🎉 ANÚNCIO MASKS: Inicialização completa!");
    }
};

console.log("✅ ANÚNCIO MASKS: Módulo carregado e pronto");
