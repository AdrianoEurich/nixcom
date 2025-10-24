<?php
// Teste simples do endpoint MCP
header('Content-Type: application/json');

$testData = [
    'plano_id' => 1,
    'period' => '6_meses'
];

// Simular dados de entrada
$_POST = $testData;

// Incluir o endpoint MCP
include 'payment_mcp_real.php';
?>

