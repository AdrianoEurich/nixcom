# Configuração do Mercado Pago

## 1. Obter Chaves de Acesso

### Sandbox (Teste)
1. Acesse: https://www.mercadopago.com.br/developers
2. Faça login na sua conta
3. Vá em "Suas integrações" > "Teste"
4. Copie o **Access Token** de teste

### Produção
1. No mesmo painel, vá em "Produção"
2. Copie o **Access Token** de produção

## 2. Configurar no Sistema

Edite o arquivo `app/adms/Config/MercadoPagoConfig.php`:

```php
// Substitua pelos seus tokens reais
public const SANDBOX_ACCESS_TOKEN = 'SEU_TOKEN_DE_TESTE_AQUI';
public const PRODUCTION_ACCESS_TOKEN = 'SEU_TOKEN_DE_PRODUCAO_AQUI';

// Para produção, mude para false
public const IS_SANDBOX = true;
```

## 3. Configurar Webhook

1. No painel do Mercado Pago, vá em "Webhooks"
2. Adicione a URL: `https://seudominio.com/webhook/mercadopago.php`
3. Selecione os eventos: `payment`

## 4. Testar com ngrok (Desenvolvimento)

```bash
# Instalar ngrok
npm install -g ngrok

# Expor porta local
ngrok http 80

# Usar a URL fornecida no webhook
```

## 5. URLs do Sistema

- **Pagamento:** `https://seudominio.com/adms/payment?plan=basic`
- **Webhook:** `https://seudominio.com/webhook/mercadopago.php`

## 6. Status de Pagamento

- `pending` - Aguardando pagamento
- `paid_awaiting_admin` - Pago, aguardando aprovação
- `active` - Ativo (aprovado)
- `suspended` - Suspenso (expirado)


