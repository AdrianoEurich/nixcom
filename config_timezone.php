<?php
/**
 * Configuração de Timezone para o Brasil
 * Este arquivo deve ser incluído no início de todas as páginas
 */

// Definir timezone para São Paulo (Brasil)
date_default_timezone_set('America/Sao_Paulo');

// Verificar se a configuração foi aplicada
echo "<!-- DEBUG TIMEZONE: " . date_default_timezone_get() . " -->";
echo "<!-- DEBUG HORA ATUAL: " . date('Y-m-d H:i:s') . " -->";
?>
