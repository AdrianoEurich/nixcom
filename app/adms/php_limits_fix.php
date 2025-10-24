<?php
// Script para verificar e ajustar limites do PHP
echo "🔍 VERIFICANDO LIMITES ATUAIS DO PHP:\n\n";

echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n\n";

echo "📋 RECOMENDAÇÕES:\n";
echo "- upload_max_filesize: 200M\n";
echo "- post_max_size: 250M\n";
echo "- max_execution_time: 300\n";
echo "- max_input_time: 300\n";
echo "- memory_limit: 512M\n";
echo "- max_file_uploads: 50\n\n";

echo "🔧 Para corrigir, adicione estas linhas ao php.ini:\n";
echo "upload_max_filesize = 200M\n";
echo "post_max_size = 250M\n";
echo "max_execution_time = 300\n";
echo "max_input_time = 300\n";
echo "memory_limit = 512M\n";
echo "max_file_uploads = 50\n\n";

echo "📍 Localização do php.ini: " . php_ini_loaded_file() . "\n";
?>

