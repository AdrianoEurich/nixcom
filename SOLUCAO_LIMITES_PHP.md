# SOLUÇÃO: Aumentar Limites de Upload do PHP

## Problema Identificado
O tamanho total do upload (aproximadamente 270 MB) excede o limite do PHP `post_max_size` (250 MB).
Quando isso acontece, o PHP descarta todos os dados e `$_POST` e `$_FILES` ficam vazios, causando o erro de validação.

## Solução

### 1. Localizar o arquivo php.ini
No XAMPP, o arquivo está em: `C:\xampp\php\php.ini`

### 2. Editar as seguintes configurações:
```ini
post_max_size = 500M
upload_max_filesize = 200M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

### 3. Salvar o arquivo e reiniciar o Apache
- Abra o XAMPP Control Panel
- Clique em "Stop" no Apache
- Aguarde alguns segundos
- Clique em "Start" no Apache

### 4. Verificar se as alterações foram aplicadas
Acesse: `http://localhost/nixcom/verificar_limites_php.php`

## Explicação dos Limites

- **post_max_size**: Tamanho máximo de dados POST (deve ser MAIOR que a soma de todos os arquivos)
- **upload_max_filesize**: Tamanho máximo de um arquivo individual
- **max_execution_time**: Tempo máximo de execução do script em segundos
- **max_input_time**: Tempo máximo para receber dados POST
- **memory_limit**: Limite de memória para o script PHP

## Tamanhos Aproximados por Plano

### FREE (até 2 fotos)
- Total: ~60 MB (vídeo confirmação + capa + 2 fotos galeria)

### BASIC (até 20 fotos)
- Total: ~130 MB (vídeo confirmação + capa + 20 fotos galeria)

### PREMIUM (até 20 fotos + 3 vídeos + 3 áudios)
- Total: ~270 MB (vídeo confirmação + capa + 20 fotos + 3 vídeos + 3 áudios)

## Alternativa: Limitar Tamanho dos Arquivos

Se não puder aumentar os limites do PHP, você pode:
1. Limitar o tamanho máximo de cada arquivo
2. Reduzir o número de arquivos permitidos
3. Implementar upload progressivo (enviar arquivos em partes)

