<?php
// Endpoint direto para contato
define('C7E3L8K9E5', true);

// Configurar timezone para o Brasil
date_default_timezone_set('America/Sao_Paulo');

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Método não permitido']);
    exit;
}

// Verificar se todos os campos foram enviados
if (empty($_POST['nome']) || empty($_POST['email']) || empty($_POST['telefone']) || 
    empty($_POST['assunto']) || empty($_POST['mensagem'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Todos os campos são obrigatórios']);
    exit;
}

// Sanitizar dados
$nome = filter_var($_POST['nome'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$telefone = filter_var($_POST['telefone'], FILTER_SANITIZE_STRING);
$assunto = filter_var($_POST['assunto'], FILTER_SANITIZE_STRING);
$mensagem = filter_var($_POST['mensagem'], FILTER_SANITIZE_STRING);

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Email inválido']);
    exit;
}

// Log do contato
$log = [
    'data' => date('Y-m-d H:i:s'),
    'nome' => $nome,
    'email' => $email,
    'telefone' => $telefone,
    'assunto' => $assunto,
    'mensagem' => $mensagem,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido'
];

$logFile = __DIR__ . '/logs/contato.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

file_put_contents($logFile, json_encode($log) . "\n", FILE_APPEND | LOCK_EX);

// Retornar sucesso
header('Content-Type: application/json');
echo json_encode([
    'status' => 'sucesso',
    'mensagem' => 'Mensagem recebida com sucesso! Entraremos em contato em breve.'
]);
?>
