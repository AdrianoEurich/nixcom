# 🚀 Configuração Mercado Pago - Seu Setup

## 📋 Suas Chaves Configuradas

### ✅ Chaves de Produção (Já configuradas)
- **Access Token:** `APP_USR-8226898734680411-101115-9d226eccd0f3b50c836673d870c039f7-12767031`
- **Public Key:** `APP_USR-8f31d7e6-6105-45a9-9288-221ecdbda075`
- **Client ID:** `8226898734680411`
- **Client Secret:** `atwbUtqVD9KjVSYkmItdPgMqb35oRYCI`

## 🔧 Configuração do Webhook

### 1. URL do Webhook
```
https://seudominio.com/webhook/mercadopago.php
```

### 2. Eventos Selecionados
✅ **Pagamentos** (obrigatório)
- Este é o evento principal que precisamos

### 3. Configuração no Painel Mercado Pago
1. Acesse: https://www.mercadopago.com.br/developers
2. Vá em "Webhooks"
3. Clique em "Criar webhook"
4. Configure:
   - **URL:** `https://seudominio.com/webhook/mercadopago.php`
   - **Eventos:** Selecione apenas "Pagamentos"
   - **Modo:** Produção

## 🧪 Testando o Sistema

### 1. Para Testes (Sandbox)
Se quiser testar primeiro, edite `app/adms/Config/MercadoPagoConfig.php`:
```php
public const IS_SANDBOX = true; // Para testes
```

### 2. Para Produção
```php
public const IS_SANDBOX = false; // Para produção
```

### 3. URLs de Teste
- **Pagamento Basic:** `https://seudominio.com/adms/payment?plan=basic`
- **Pagamento Premium:** `https://seudominio.com/adms/payment?plan=premium`

## 🔍 Verificação do Setup

### 1. Testar Webhook
```bash
# Teste local com ngrok
ngrok http 80

# Use a URL fornecida pelo ngrok no webhook
```

### 2. Logs do Sistema
Os logs ficam em: `logs/mercadopago_webhook.log`

### 3. Verificar Status
- Acesse: `https://seudominio.com/adms/payment?plan=basic`
- Gere um pagamento PIX
- Verifique se o QR Code aparece
- Teste o pagamento

## 🚨 Importante

1. **HTTPS Obrigatório:** O webhook só funciona com HTTPS
2. **URL Pública:** O webhook deve ser acessível publicamente
3. **Logs:** Monitore os logs para debug
4. **Teste Primeiro:** Use sandbox antes de ir para produção

## 📞 Suporte

Se houver problemas:
1. Verifique os logs em `logs/mercadopago_webhook.log`
2. Teste a URL do webhook manualmente
3. Verifique se o HTTPS está funcionando
4. Confirme se as chaves estão corretas


