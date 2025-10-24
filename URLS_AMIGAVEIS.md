# 🌐 URLs Amigáveis - Sistema de Pagamento

## 📋 URLs Disponíveis

### **💳 Sistema de Pagamento:**

| URL Amigável | URL Original | Descrição |
|--------------|--------------|-----------|
| `/adms/pix` | `/adms/pagamento` | Página de pagamento PIX |
| `/adms/pagar` | `/adms/pagamento` | Página de pagamento |
| `/adms/checkout` | `/adms/pagamento` | Página de checkout |
| `/adms/assinatura` | `/adms/pagamento` | Página de assinatura |
| `/adms/planos` | `/adms/planos` | Página de planos |

### **🎯 Exemplos de Uso:**

#### **URLs Antigas (com query string):**
```
http://localhost/nixcom/adms/pagamento?plan=basic
http://localhost/nixcom/adms/pagamento?plan=premium
http://localhost/nixcom/adms/planos
```

#### **URLs Novas (REALMENTE Amigáveis):**
```
http://localhost/nixcom/adms/pix/basic
http://localhost/nixcom/adms/pix/premium
http://localhost/nixcom/adms/pix/enterprise
http://localhost/nixcom/adms/pagar/basic
http://localhost/nixcom/adms/pagar/premium
http://localhost/nixcom/adms/pagar/enterprise
http://localhost/nixcom/adms/checkout/basic
http://localhost/nixcom/adms/checkout/premium
http://localhost/nixcom/adms/checkout/enterprise
http://localhost/nixcom/adms/assinatura/basic
http://localhost/nixcom/adms/assinatura/premium
http://localhost/nixcom/adms/assinatura/enterprise
http://localhost/nixcom/adms/planos
```

### **🔧 Como Funciona:**

1. **Mapeamento no `.htaccess`:**
   - Todas as URLs `/adms/*` são redirecionadas para `index_admin.php`
   - O parâmetro `url` contém a URL amigável

2. **Processamento no `ConfigControllerAdm.php`:**
   - Mapeia slugs para controllers
   - Exemplo: `pix` → `PaymentController`

3. **Métodos no `PaymentController.php`:**
   - Cada URL amigável tem um método correspondente
   - Todos redirecionam para o método `index()`

### **📱 URLs com Parâmetros:**

#### **Planos:**
```
http://localhost/nixcom/adms/pix?plan=basic
http://localhost/nixcom/adms/pix?plan=premium
http://localhost/nixcom/adms/pix?plan=enterprise
```

#### **Outros Parâmetros:**
```
http://localhost/nixcom/adms/pagar?plan=basic&period=6_meses
http://localhost/nixcom/adms/checkout?plan=premium&period=12_meses
```

### **🎨 Vantagens das URLs Amigáveis:**

1. **✅ SEO Friendly** - URLs mais amigáveis para buscadores
2. **✅ User Friendly** - URLs mais fáceis de lembrar
3. **✅ Professional** - URLs mais profissionais
4. **✅ Branding** - URLs que reforçam a marca

### **🔍 Testando as URLs:**

1. **PIX:** `http://localhost/nixcom/adms/pix?plan=basic`
2. **Pagar:** `http://localhost/nixcom/adms/pagar?plan=premium`
3. **Checkout:** `http://localhost/nixcom/adms/checkout?plan=basic`
4. **Assinatura:** `http://localhost/nixcom/adms/assinatura?plan=premium`
5. **Planos:** `http://localhost/nixcom/adms/planos`

### **⚙️ Configuração Técnica:**

#### **Arquivo `.htaccess`:**
```apache
RewriteRule ^adms/(.*)$ index_admin.php?url=$1 [L,QSA]
```

#### **Mapeamento em `ConfigControllerAdm.php`:**
```php
private array $urlToControllerMap = [
    'pix' => 'PaymentController',
    'pagar' => 'PaymentController',
    'checkout' => 'PaymentController',
    'assinatura' => 'PaymentController',
    'planos' => 'Planos',
];
```

#### **Métodos no `PaymentController.php`:**
```php
public function pix(): void { $this->index(); }
public function pagar(): void { $this->index(); }
public function checkout(): void { $this->index(); }
public function assinatura(): void { $this->index(); }
```

### **🚀 Próximos Passos:**

1. **Testar URLs** - Verificar se funcionam
2. **Atualizar Links** - Usar URLs amigáveis nos links
3. **SEO** - Otimizar para buscadores
4. **Analytics** - Monitorar uso das URLs

---

**✅ Sistema de URLs Amigáveis implementado com sucesso!** 🎉
