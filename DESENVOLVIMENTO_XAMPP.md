# 🚀 Guia de Desenvolvimento - XAMPP

## 📋 Configuração do Ambiente

### ✅ 1. XAMPP Configurado
- **Apache**: Porta 80
- **MySQL**: Porta 3306
- **PHP**: Versão 8.0+
- **Projeto**: `C:\xampp\htdocs\nixcom`

### ✅ 2. Banco de Dados
```sql
-- Criar banco de dados
CREATE DATABASE nixcom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Importar estrutura (se necessário)
-- Arquivo: database_structure.sql
```

### ✅ 3. Configuração do Mercado Pago
```php
// app/adms/Config/MercadoPagoConfig.php
public const IS_SANDBOX = true; // Modo desenvolvimento
public const SANDBOX_ACCESS_TOKEN = 'TEST-SUA-CHAVE-DE-TESTE';
```

## 🔧 Configuração do ngrok

### 1. Instalar ngrok
```bash
# Windows
# Baixar de: https://ngrok.com/download
# Extrair para: C:\ngrok\

# Linux/Mac
sudo snap install ngrok
```

### 2. Configurar ngrok
```bash
# 1. Criar conta gratuita em: https://ngrok.com/
# 2. Obter authtoken
# 3. Configurar:
ngrok config add-authtoken SEU_AUTHTOKEN
```

### 3. Expor aplicação
```bash
# No terminal (navegar para pasta do ngrok)
cd C:\ngrok\

# Expor porta 80
ngrok http 80

# Copiar URL HTTPS gerada
# Exemplo: https://abc123.ngrok.io
```

### 4. Atualizar configuração
```php
// app/adms/Config/MercadoPagoConfig.php
public const WEBHOOK_URL_SANDBOX = 'https://abc123.ngrok.io/webhook/mercadopago.php';
```

## 🧪 Testes de Desenvolvimento

### 1. Teste de Pagamento PIX
```bash
# 1. Acessar: http://localhost/nixcom/adms/
# 2. Criar conta de teste
# 3. Selecionar plano pago
# 4. Gerar PIX
# 5. Verificar webhook
# 6. Confirmar ativação
```

### 2. Teste de Webhook
```bash
# Teste manual via cURL
curl -X POST https://abc123.ngrok.io/webhook/mercadopago.php \
  -H "Content-Type: application/json" \
  -d '{"id":"test","type":"payment","action":"payment.created"}'
```

### 3. Teste de Relatórios
```bash
# Acessar: http://localhost/nixcom/adms/financial-reports
# Verificar gráficos e estatísticas
# Testar exportação CSV
```

## 📊 Funcionalidades Disponíveis

### ✅ Sistema de Pagamento
- **PIX Sandbox**: Funcionando
- **QR Code**: Gerado corretamente
- **Webhook**: Via ngrok
- **Logs**: Registrados no banco

### ✅ Mudança de Planos
- **Botão**: "Mudar Plano" no dashboard
- **Validação**: Upgrades/downgrades
- **Confirmação**: Antes da mudança
- **Redirecionamento**: Para pagamento

### ✅ Gestão Administrativa
- **Status**: Atualização manual
- **Ativação**: Automática de funcionalidades
- **Notificações**: Para usuários
- **Logs**: Auditoria completa

### ✅ Relatórios Financeiros
- **Dashboard**: Métricas em tempo real
- **Gráficos**: Receita por plano/período
- **Exportação**: CSV
- **Filtros**: Por data

### ✅ Configuração de Webhook
- **Interface**: Configuração visual
- **Teste**: Notificações de teste
- **Validação**: HTTPS obrigatório
- **Logs**: Webhook detalhados

## 🔍 Debug e Logs

### 1. Logs do Sistema
```php
// Verificar logs do PHP
tail -f C:\xampp\apache\logs\error.log

// Logs da aplicação
tail -f C:\xampp\htdocs\nixcom\logs\app.log
```

### 2. Logs de Pagamento
```sql
-- Verificar logs de pagamento
SELECT * FROM payment_logs ORDER BY created_at DESC LIMIT 10;

-- Estatísticas
SELECT status, COUNT(*) as total FROM payment_logs GROUP BY status;
```

### 3. Debug do Webhook
```php
// Verificar webhook
error_log("Webhook recebido: " . json_encode($_POST));

// Logs detalhados
error_log("Payment ID: " . $paymentId);
error_log("Status: " . $status);
```

## 🚀 Comandos Úteis

### 1. Iniciar Serviços
```bash
# XAMPP Control Panel
# Start Apache
# Start MySQL
```

### 2. Iniciar ngrok
```bash
# Terminal
cd C:\ngrok\
ngrok http 80
```

### 3. Verificar Status
```bash
# Verificar Apache
curl -I http://localhost/nixcom/

# Verificar MySQL
mysql -u root -p

# Verificar ngrok
curl -I https://abc123.ngrok.io/
```

## 📝 Checklist de Desenvolvimento

### ✅ Ambiente Configurado
- [ ] XAMPP funcionando
- [ ] Banco de dados criado
- [ ] Projeto acessível
- [ ] ngrok configurado

### ✅ Mercado Pago
- [ ] Conta sandbox criada
- [ ] Chaves de teste configuradas
- [ ] Webhook configurado
- [ ] Teste de pagamento realizado

### ✅ Funcionalidades
- [ ] Sistema de pagamento PIX
- [ ] Mudança de planos
- [ ] Gestão administrativa
- [ ] Relatórios financeiros
- [ ] Configuração de webhook

### ✅ Testes
- [ ] Pagamento PIX sandbox
- [ ] Webhook funcionando
- [ ] Logs sendo registrados
- [ ] Relatórios atualizando
- [ ] Interface responsiva

## 🐛 Troubleshooting

### Problema: Webhook não funciona
```bash
# Verificar ngrok
ngrok http 80

# Verificar URL
curl -I https://abc123.ngrok.io/webhook/mercadopago.php

# Verificar logs
tail -f C:\xampp\apache\logs\error.log
```

### Problema: PIX não gera
```php
// Verificar chaves
var_dump(MercadoPagoConfig::getAccessToken());

// Verificar logs
error_log("PIX Error: " . $error);
```

### Problema: Banco de dados
```sql
-- Verificar conexão
SHOW DATABASES;

-- Verificar tabelas
SHOW TABLES;

-- Verificar logs
SELECT * FROM payment_logs LIMIT 5;
```

## 📞 Suporte de Desenvolvimento

### Logs Importantes
- **Apache**: `C:\xampp\apache\logs\error.log`
- **MySQL**: `C:\xampp\mysql\data\*.err`
- **PHP**: `C:\xampp\php\logs\php_error_log`

### URLs de Teste
- **Aplicação**: http://localhost/nixcom/
- **Admin**: http://localhost/nixcom/adms/
- **Webhook**: https://abc123.ngrok.io/webhook/mercadopago.php
- **Relatórios**: http://localhost/nixcom/adms/financial-reports

### Comandos de Debug
```bash
# Verificar status dos serviços
netstat -an | findstr :80
netstat -an | findstr :3306

# Verificar processos
tasklist | findstr httpd
tasklist | findstr mysqld
```

---

**Ambiente de desenvolvimento configurado e pronto para uso! 🎉**

**Próximo passo**: Configurar ngrok e testar o webhook

