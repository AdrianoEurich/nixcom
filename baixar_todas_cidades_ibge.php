<?php
/**
 * Script para baixar TODAS as cidades do Brasil via API do IBGE
 */

$host = 'localhost';
$dbname = 'nixcom';
$username = 'root';
$password = '';

echo "<h2>🇧🇷 BAIXANDO TODAS AS CIDADES DO BRASIL VIA API IBGE</h2>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Limpar tabela atual
    echo "<p>🧹 Limpando tabela cidade atual...</p>";
    $pdo->exec("DELETE FROM cidade");
    $pdo->exec("ALTER TABLE cidade AUTO_INCREMENT = 1");
    
    // Baixar dados da API do IBGE
    echo "<p>📡 Baixando dados da API do IBGE...</p>";
    
    $url = "https://servicodados.ibge.gov.br/api/v1/localidades/municipios";
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $json = file_get_contents($url, false, $context);
    
    if ($json === false) {
        throw new Exception("Erro ao baixar dados da API do IBGE");
    }
    
    $cidades = json_decode($json, true);
    
    if (empty($cidades)) {
        throw new Exception("Nenhuma cidade encontrada na API");
    }
    
    echo "<p>✅ Baixadas " . count($cidades) . " cidades da API do IBGE</p>";
    
    // Inserir no banco
    echo "<p>💾 Inserindo cidades no banco de dados...</p>";
    
    $adicionadas = 0;
    $erros = 0;
    
    foreach ($cidades as $cidade) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cidade (Codigo, Nome, Uf) VALUES (?, ?, ?)");
            $stmt->execute([
                $cidade['id'],
                $cidade['nome'],
                $cidade['microrregiao']['mesorregiao']['UF']['sigla']
            ]);
            $adicionadas++;
            
            if ($adicionadas % 500 == 0) {
                echo "<p>✅ Inseridas $adicionadas cidades...</p>";
            }
        } catch (PDOException $e) {
            $erros++;
            if ($erros <= 5) {
                echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>📊 Resumo Final:</h3>";
    echo "<p>✅ Cidades inseridas: $adicionadas</p>";
    echo "<p>❌ Erros: $erros</p>";
    
    // Verificar total
    $stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM cidade");
    $stmt_total->execute();
    $total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    echo "<p>📊 Total de cidades no banco: " . $total['total'] . "</p>";
    
    // Verificar alguns exemplos
    echo "<h3>🔍 Exemplos de cidades adicionadas:</h3>";
    $stmt_exemplos = $pdo->prepare("SELECT Codigo, Nome, Uf FROM cidade ORDER BY Uf, Nome LIMIT 10");
    $stmt_exemplos->execute();
    $exemplos = $stmt_exemplos->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Código</th><th>Nome</th><th>UF</th></tr>";
    foreach ($exemplos as $exemplo) {
        echo "<tr><td>" . $exemplo['Codigo'] . "</td><td>" . $exemplo['Nome'] . "</td><td>" . $exemplo['Uf'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>🎉 TODAS AS CIDADES DO BRASIL IMPORTADAS!</h3>";
    echo "<p><a href='http://localhost/nixcom/categorias/mulher' target='_blank'>Testar página de categorias</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . $e->getMessage() . "</p>";
    echo "<p>💡 Dica: Verifique sua conexão com a internet e tente novamente.</p>";
}
?>

