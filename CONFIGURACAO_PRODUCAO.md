# Configuração para Produção - Sistema PIX Mercado Pago

## 📋 Checklist de Produção

### ✅ 1. Configuração das Chaves do Mercado Pago

#### 1.1 Obter Credenciais de Produção
1. Acesse o [Painel do Mercado Pago](https://www.mercadopago.com.br/developers/panel)
2. Vá em **Suas integrações** > **Dados da integração**
3. Copie as credenciais de **Produção**:
   - Access Token (APP-...)
   - Public Key (APP-...)

#### 1.2 Configurar no Sistema
```php
// app/adms/Config/MercadoPagoConfig.php
public const IS_SANDBOX = false; // Mude para false
public const PRODUCTION_ACCESS_TOKEN = 'APP-USUARIO-1234567890-abcdefghijklmnopqrstuvwxyz-1234567890';
```

### ✅ 2. Configuração de SSL/HTTPS

#### 2.1 Certificado SSL
- **Obrigatório** para produção
- Use Let's Encrypt, Cloudflare ou certificado comercial
- Configure redirecionamento HTTP → HTTPS

#### 2.2 Atualizar URLs
```php
// Atualizar todas as URLs para HTTPS
const URL = 'https://seudominio.com/';
const URLADM = 'https://seudominio.com/adms/';
```

### ✅ 3. Configuração do Webhook

#### 3.1 URL do Webhook
```
https://seudominio.com/webhook/mercadopago.php
```

#### 3.2 Configurar no Mercado Pago
1. Acesse o painel do Mercado Pago
2. Vá em **Suas integrações** > **Webhooks**
3. Adicione a URL do webhook
4. Selecione os tópicos: `payment`, `order`

#### 3.3 Testar Webhook
```bash
# Teste manual via cURL
curl -X POST https://seudominio.com/webhook/mercadopago.php \
  -H "Content-Type: application/json" \
  -d '{"id":"test","type":"payment","action":"payment.created"}'
```

### ✅ 4. Monitoramento de Logs

#### 4.1 Logs de Pagamento
- Tabela `payment_logs` criada automaticamente
- Logs mantidos por 90 dias
- Acesse em: `/adms/financial-reports`

#### 4.2 Logs do Sistema
```php
// Verificar logs do PHP
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

#### 4.3 Alertas Recomendados
- Falhas de pagamento
- Webhook não respondendo
- Erros de API do Mercado Pago

### ✅ 5. Relatórios Financeiros

#### 5.1 Acesso aos Relatórios
- URL: `/adms/financial-reports`
- Apenas administradores
- Exportação em CSV

#### 5.2 Métricas Disponíveis
- Total de pagamentos
- Pagamentos aprovados/pendentes
- Receita por plano
- Receita por período
- Logs detalhados

### ✅ 6. Configurações de Segurança

#### 6.1 Headers de Segurança
```php
// Adicionar em index.php
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'');
```

#### 6.2 Validação de Dados
- Todos os inputs são sanitizados
- Validação de CPF/CNPJ
- Verificação de email único
- Proteção contra SQL Injection

#### 6.3 Rate Limiting
```php
// Implementar limite de tentativas
if ($attempts > 5) {
    // Bloquear por 15 minutos
}
```

### ✅ 7. Backup e Recuperação

#### 7.1 Backup do Banco de Dados
```bash
# Backup diário
mysqldump -u usuario -p banco_de_dados > backup_$(date +%Y%m%d).sql
```

#### 7.2 Backup dos Arquivos
```bash
# Backup dos arquivos
tar -czf backup_files_$(date +%Y%m%d).tar.gz /caminho/do/projeto
```

### ✅ 8. Testes de Produção

#### 8.1 Teste de Pagamento PIX
1. Criar conta de teste
2. Selecionar plano pago
3. Gerar PIX
4. Verificar webhook
5. Confirmar ativação do plano

#### 8.2 Teste de Webhook
```php
// Simular notificação
$testData = [
    'id' => 'test_' . uniqid(),
    'type' => 'payment',
    'action' => 'payment.created'
];
```

### ✅ 9. Monitoramento de Performance

#### 9.1 Métricas Importantes
- Tempo de resposta da API
- Taxa de sucesso dos pagamentos
- Tempo de processamento do webhook
- Uso de memória/CPU

#### 9.2 Ferramentas Recomendadas
- New Relic
- DataDog
- LogRocket
- Sentry

### ✅ 10. Documentação e Suporte

#### 10.1 Documentação Interna
- Fluxo de pagamento
- Troubleshooting
- Contatos de suporte
- Procedimentos de emergência

#### 10.2 Suporte Mercado Pago
- [Central de Ajuda](https://www.mercadopago.com.br/developers/support)
- [Status da API](https://status.mercadopago.com/)
- [Comunidade](https://community.mercadopago.com/)

## 🚀 Deploy para Produção

### 1. Preparação
```bash
# 1. Backup do ambiente atual
# 2. Testes em staging
# 3. Validação de todas as funcionalidades
```

### 2. Deploy
```bash
# 1. Upload dos arquivos
# 2. Configurar variáveis de ambiente
# 3. Executar migrações do banco
# 4. Configurar webhook
# 5. Testes finais
```

### 3. Pós-Deploy
```bash
# 1. Monitorar logs
# 2. Verificar webhook
# 3. Testar pagamentos
# 4. Validar relatórios
```

## 📞 Contatos de Emergência

### Suporte Técnico
- **Email**: suporte@seudominio.com
- **Telefone**: (11) 99999-9999
- **WhatsApp**: (11) 99999-9999

### Mercado Pago
- **Central de Ajuda**: https://www.mercadopago.com.br/developers/support
- **Status da API**: https://status.mercadopago.com/

## 🔧 Comandos Úteis

### Verificar Status do Sistema
```bash
# Verificar logs de erro
tail -f /var/log/apache2/error.log | grep -i "mercadopago\|pix\|payment"

# Verificar webhook
curl -I https://seudominio.com/webhook/mercadopago.php

# Verificar SSL
openssl s_client -connect seudominio.com:443 -servername seudominio.com
```

### Limpeza de Logs
```bash
# Limpar logs antigos (executar mensalmente)
php /caminho/do/projeto/app/adms/Models/AdmsPaymentLog.php cleanOldLogs
```

## 📊 Métricas de Sucesso

### KPIs Principais
- **Taxa de Conversão**: > 85%
- **Tempo de Resposta**: < 2 segundos
- **Disponibilidade**: > 99.9%
- **Taxa de Erro**: < 1%

### Relatórios Semanais
- Total de pagamentos
- Receita gerada
- Problemas identificados
- Melhorias implementadas

---

**Sistema pronto para produção com PIX real via Mercado Pago! 🎉**

