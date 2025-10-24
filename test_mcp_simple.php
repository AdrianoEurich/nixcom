<?php
// Simular dados POST
$_POST = [
    'plano_id' => 1,
    'period' => '6_meses'
];

// Simular dados JSON
$jsonData = json_encode($_POST);
file_put_contents('php://input', $jsonData);

// Incluir o endpoint
include 'payment_mcp_simple.php';
?>

