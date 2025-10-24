<?php
/**
 * SISTEMA DE LOGS AUTOMÁTICO
 * Recebe e armazena todos os logs de debug automaticamente
 */

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para salvar log
function salvarLog($dados) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = 'debug_logs_automatico.txt';
    
    $logEntry = "[$timestamp] " . json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n" . str_repeat("-", 80) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    return true;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    if ($dados) {
        salvarLog($dados);
        echo json_encode(['success' => true, 'message' => 'Log salvo com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    }
} else {
    // Mostrar logs salvos
    $logFile = 'debug_logs_automatico.txt';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        echo "<h1>🔍 LOGS AUTOMÁTICOS DE DEBUG</h1>";
        echo "<pre style='background: #f8f9fa; padding: 20px; border-radius: 5px; overflow-x: auto;'>";
        echo htmlspecialchars($logs);
        echo "</pre>";
    } else {
        echo "<h1>🔍 LOGS AUTOMÁTICOS DE DEBUG</h1>";
        echo "<p>Nenhum log encontrado ainda.</p>";
    }
}
?>

