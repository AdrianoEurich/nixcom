<?php
// Script para atualizar os nomes das colunas de localização na tabela anuncios

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'nixcom';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao banco de dados com sucesso!\n";
    
    // Verificar se as colunas existem
    $stmt = $pdo->query("DESCRIBE anuncios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Colunas atuais na tabela anuncios:\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    // Renomear state_uf para state_id se existir
    if (in_array('state_uf', $columns)) {
        echo "\nRenomeando state_uf para state_id...\n";
        $pdo->exec("ALTER TABLE anuncios CHANGE COLUMN state_uf state_id VARCHAR(2) NOT NULL");
        echo "✅ state_uf renomeado para state_id\n";
    } else {
        echo "⚠️ Coluna state_uf não encontrada\n";
    }
    
    // Renomear city_code para city_id se existir
    if (in_array('city_code', $columns)) {
        echo "\nRenomeando city_code para city_id...\n";
        $pdo->exec("ALTER TABLE anuncios CHANGE COLUMN city_code city_id VARCHAR(10) NOT NULL");
        echo "✅ city_code renomeado para city_id\n";
    } else {
        echo "⚠️ Coluna city_code não encontrada\n";
    }
    
    // Verificar se neighborhood_name existe, se não, criar
    if (!in_array('neighborhood_name', $columns)) {
        echo "\nCriando coluna neighborhood_name...\n";
        $pdo->exec("ALTER TABLE anuncios ADD COLUMN neighborhood_name VARCHAR(100) DEFAULT NULL");
        echo "✅ Coluna neighborhood_name criada\n";
    } else {
        echo "✅ Coluna neighborhood_name já existe\n";
    }
    
    // Verificar a estrutura final
    echo "\nEstrutura final da tabela anuncios:\n";
    $stmt = $pdo->query("DESCRIBE anuncios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    
    echo "\n✅ Atualização do banco de dados concluída com sucesso!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro ao conectar/atualizar banco de dados: " . $e->getMessage() . "\n";
}
?>
