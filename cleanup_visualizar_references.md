# 🧹 Limpeza de Referências ao visualizar_anuncio.js

## 📊 Referências Encontradas:

### ✅ **JÁ CORRIGIDAS:**
- `app/adms/assets/js/dashboard_custom.js` - ✅ Comentadas

### ❌ **AINDA PRECISAM SER CORRIGIDAS:**

#### **1. Arquivos de View (Podem ser removidos):**
- `app/adms/Views/anuncio/visualizar_anuncio.php` - ❌ Não usado mais
- `app/adms/Views/anuncio/visualizar_anuncio_melhorado.php` - ❌ Não usado mais

#### **2. Links que ainda apontam para visualizarAnuncio:**
- `app/adms/Views/dashboard/content_dashboard.php:289` - ❌ Link na tabela
- `app/adms/Views/include/sidebar.php:44` - ❌ Link na sidebar
- `app/adms/assets/js/notificacoes.js:198,308` - ❌ Função JavaScript

#### **3. Controller (Mantém redirecionamento):**
- `app/adms/Controllers/Anuncio.php:628` - ✅ Mantém redirecionamento para STS

#### **4. Arquivos de Teste (Podem ser removidos):**
- `test_visualizar_anuncio.php` - ❌ Não usado mais
- `test_visualizar_melhorado.php` - ❌ Não usado mais
- `MELHORIAS_VISUALIZACAO_ANUNCIOS.md` - ❌ Documentação obsoleta

## 🛠️ Ações Recomendadas:

### **1. Atualizar Links para STS:**
- Dashboard: `anuncio/visualizarAnuncio` → `anuncio/visualizar/{id}`
- Sidebar: `anuncio/visualizarAnuncio` → `anuncio/visualizar/{id}`
- Notificações: `visualizarAnuncio()` → redirecionar para STS

### **2. Remover Arquivos Obsoletos:**
- `app/adms/Views/anuncio/visualizar_anuncio.php`
- `app/adms/Views/anuncio/visualizar_anuncio_melhorado.php`
- `test_visualizar_anuncio.php`
- `test_visualizar_melhorado.php`
- `MELHORIAS_VISUALIZACAO_ANUNCIOS.md`

### **3. Manter:**
- `app/adms/Controllers/Anuncio.php` - Mantém redirecionamento
- `app/adms/assets/js/visualizar_anuncio.js` - Pode ser removido se não usado

## 🎯 Status:
- **Referências encontradas:** 16 arquivos
- **Já corrigidas:** 1 arquivo
- **Precisam correção:** 15 arquivos
- **Arquivos para remoção:** 5 arquivos
