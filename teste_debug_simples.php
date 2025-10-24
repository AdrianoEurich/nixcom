<?php
/**
 * ARQUIVO DE TESTE SIMPLES PARA DEBUG DA EXCLUSÃO DE CONTA
 * Execute: http://localhost/nixcom/teste_debug_simples.php
 */

echo "<h1>🧪 TESTE DE DEBUG - EXCLUSÃO DE CONTA</h1>";
echo "<hr>";

// Teste 1: Verificar se o arquivo Usuario.php existe
echo "<h2>1. Verificando Arquivos</h2>";

$usuarioPath = 'app/adms/Controllers/Usuario.php';
if (file_exists($usuarioPath)) {
    echo "<p>✅ Arquivo Usuario.php existe</p>";
    
    // Verificar conteúdo
    $content = file_get_contents($usuarioPath);
    if (strpos($content, 'public function deleteAccount()') !== false) {
        echo "<p>✅ Método deleteAccount() existe</p>";
    } else {
        echo "<p>❌ Método deleteAccount() NÃO existe</p>";
    }
    
    // Verificar se há erro de sintaxe
    if (strpos($content, 'if (!$userIdToDelete) {') !== false) {
        echo "<p>✅ Verificação de userIdToDelete existe</p>";
    } else {
        echo "<p>❌ Verificação de userIdToDelete NÃO existe</p>";
    }
} else {
    echo "<p>❌ Arquivo Usuario.php NÃO existe</p>";
}

// Teste 2: Verificar banco de dados
echo "<h2>2. Verificando Banco de Dados</h2>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=nixcom', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Listar TODOS os usuários para ver quais IDs existem
    $stmt = $pdo->prepare("SELECT id, nome, nivel_acesso FROM usuarios ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>📋 TODOS OS USUÁRIOS NO BANCO:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Nível</th></tr>";
    
    foreach ($users as $user) {
        $nivel = $user['nivel_acesso'] == 'administrador' ? 'Admin' : 'Usuário';
        $cor = $user['nivel_acesso'] == 'administrador' ? 'background: #d4edda;' : '';
        echo "<tr style='$cor'>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['nome']) . "</td>";
        echo "<td>" . $nivel . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar se há admins (nivel_acesso = 'administrador')
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE nivel_acesso = 'administrador'");
    $stmt->execute();
    $adminCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>👑 Total de Admins:</strong> $adminCount</p>";
    
    // Verificar se há usuários normais
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE nivel_acesso = 'usuario'");
    $stmt->execute();
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>👤 Total de Usuários:</strong> $userCount</p>";
    
    // Criar um usuário admin se não existir
    if ($adminCount == 0) {
        echo "<h3>🔧 CRIANDO USUÁRIO ADMINISTRADOR</h3>";
        
        try {
            // Verificar estrutura da tabela usuarios
            $stmt = $pdo->prepare("DESCRIBE usuarios");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>📋 ESTRUTURA DA TABELA usuarios:</strong></p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td><td>" . $col['Null'] . "</td><td>" . $col['Key'] . "</td></tr>";
            }
            echo "</table>";
            
            // Criar admin com nivel_acesso = 'administrador'
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso, created) VALUES (?, ?, ?, ?, NOW())");
            $senhaHash = password_hash('admin123', PASSWORD_DEFAULT);
            $result = $stmt->execute(['Administrador', 'admin@nixcom.com', $senhaHash, 'administrador']);
            
            if ($result) {
                $adminId = $pdo->lastInsertId();
                echo "<p>✅ Admin criado com sucesso! ID: $adminId</p>";
                echo "<p><strong>📧 Email:</strong> admin@nixcom.com</p>";
                echo "<p><strong>🔑 Senha:</strong> admin123</p>";
            } else {
                echo "<p>❌ Erro ao criar admin</p>";
            }
        } catch (PDOException $e) {
            echo "<p>❌ Erro ao criar admin: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Erro de conexão com banco: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Simular sessão de admin para teste
session_start();
$_SESSION['user_id'] = 43; // ID do admin
$_SESSION['user_level_name'] = 'administrador';
$_SESSION['user_level_numeric'] = 3;

// Teste 3: Testar endpoint diretamente
echo "<h2>3. Testando Endpoint Diretamente</h2>";
echo "<p><strong>🔐 Sessão simulada:</strong> Admin ID 43 logado</p>";

echo "<button onclick='testarEndpoint()' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>🧪 Testar Endpoint</button>";
echo "<button onclick='limparLogs()' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>🗑️ Limpar Logs</button>";
echo "<div id='resultado' style='margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;'></div>";

?>

<script>
function testarEndpoint() {
    const resultado = document.getElementById('resultado');
    resultado.style.display = 'block';
    resultado.innerHTML = '⏳ Testando endpoint...';
    
    // Testar endpoint usuario/deleteAccount
    fetch('http://localhost/nixcom/adms/usuario/deleteAccount', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            user_id: 38,
            admin_action: true
        })
    })
    .then(response => {
        console.log('Status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Resposta bruta:', text);
        try {
            const data = JSON.parse(text);
            resultado.innerHTML = `
                <h3>📊 Resultado do Teste:</h3>
                <p><strong>Resposta JSON:</strong></p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        } catch (e) {
            resultado.innerHTML = `
                <h3>⚠️ Resposta não é JSON:</h3>
                <p><strong>Resposta bruta:</strong></p>
                <pre>${text}</pre>
            `;
        }
    })
    .catch(error => {
        resultado.innerHTML = `
            <h3>❌ Erro no Teste:</h3>
            <p><strong>Erro:</strong> ${error.message}</p>
        `;
    });
}

function limparLogs() {
    localStorage.removeItem('adminDeleteDebug');
    alert('Logs limpos!');
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
p { margin: 5px 0; }
pre { background: #f1f1f1; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>
