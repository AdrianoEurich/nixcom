<?php
/**
 * TESTE DE DEBUG - EXCLUSÃO DE CONTA NA PÁGINA DE EDIÇÃO
 * Este arquivo simula o processo completo de exclusão de conta
 */

// Iniciar sessão
session_start();

// Simular sessão de admin
$_SESSION['user_id'] = 43; // Admin ID
$_SESSION['user_level_name'] = 'administrador';
$_SESSION['user_level_numeric'] = 3;

echo "<h1>🔍 TESTE DE DEBUG - EXCLUSÃO DE CONTA NA PÁGINA DE EDIÇÃO</h1>";

// 1. Verificar dados da sessão
echo "<h2>1. 📋 DADOS DA SESSÃO</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Campo</th><th>Valor</th></tr>";
echo "<tr><td>user_id</td><td>" . ($_SESSION['user_id'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>user_level_name</td><td>" . ($_SESSION['user_level_name'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>user_level_numeric</td><td>" . ($_SESSION['user_level_numeric'] ?? 'N/A') . "</td></tr>";
echo "</table>";

// 2. Verificar usuários no banco
echo "<h2>2. 👥 USUÁRIOS NO BANCO</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=nixcom', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, nome, email, nivel_acesso, status FROM usuarios ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Nível</th><th>Status</th></tr>";
    foreach ($users as $user) {
        $nivel = $user['nivel_acesso'] == 'administrador' ? 'Admin' : 'Usuário';
        $cor = $user['nivel_acesso'] == 'administrador' ? 'background: #d4edda;' : '';
        echo "<tr style='$cor'>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $nivel . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p>❌ Erro de conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 3. Simular dados do anúncio
echo "<h2>3. 📝 DADOS DO ANÚNCIO (SIMULADO)</h2>";
$anuncioData = [
    'id' => 34,
    'user_id' => 38, // ID do usuário que será excluído
    'service_name' => 'teste',
    'status' => 'active'
];

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Campo</th><th>Valor</th></tr>";
foreach ($anuncioData as $key => $value) {
    echo "<tr><td>$key</td><td>$value</td></tr>";
}
echo "</table>";

// 4. Testar endpoint com dados corretos
echo "<h2>4. 🧪 TESTE DO ENDPOINT</h2>";
echo "<p><strong>🎯 Objetivo:</strong> Admin (ID 43) excluir usuário (ID 38)</p>";
echo "<p><strong>📡 Endpoint:</strong> /adms/usuario/deleteAccount</p>";
echo "<p><strong>📤 Dados enviados:</strong> user_id: 38, admin_action: true</p>";

echo "<button onclick='testarExclusao()' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>🧪 Testar Exclusão</button>";
echo "<button onclick='limparLogs()' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>🗑️ Limpar Logs</button>";
echo "<div id='resultado' style='margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;'></div>";

// 5. Verificar arquivos JavaScript
echo "<h2>5. 📁 ARQUIVOS JAVASCRIPT</h2>";
$jsFiles = [
    'app/adms/assets/js/anuncio-admin.js',
    'app/adms/assets/js/dashboard_custom.js',
    'app/adms/assets/js/general-utils.js'
];

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file - Existe</p>";
        
        // Verificar se contém função processAdminDeleteAccount
        $content = file_get_contents($file);
        if (strpos($content, 'processAdminDeleteAccount') !== false) {
            echo "<p style='color: green; margin-left: 20px;'>✅ Função processAdminDeleteAccount encontrada</p>";
        } else {
            echo "<p style='color: red; margin-left: 20px;'>❌ Função processAdminDeleteAccount NÃO encontrada</p>";
        }
        
        if (strpos($content, 'usuario/deleteAccount') !== false) {
            echo "<p style='color: green; margin-left: 20px;'>✅ Endpoint usuario/deleteAccount encontrado</p>";
        } else {
            echo "<p style='color: red; margin-left: 20px;'>❌ Endpoint usuario/deleteAccount NÃO encontrado</p>";
        }
    } else {
        echo "<p>❌ $file - NÃO existe</p>";
    }
}

// 6. Verificar controlador
echo "<h2>6. 🎮 CONTROLADOR</h2>";
$controllerFile = 'app/adms/Controllers/Usuario.php';
if (file_exists($controllerFile)) {
    echo "<p>✅ $controllerFile - Existe</p>";
    
    $content = file_get_contents($controllerFile);
    if (strpos($content, 'deleteAccount') !== false) {
        echo "<p style='color: green; margin-left: 20px;'>✅ Método deleteAccount encontrado</p>";
    } else {
        echo "<p style='color: red; margin-left: 20px;'>❌ Método deleteAccount NÃO encontrado</p>";
    }
    
    if (strpos($content, 'softDeleteUser') !== false) {
        echo "<p style='color: green; margin-left: 20px;'>✅ Chamada para softDeleteUser encontrada</p>";
    } else {
        echo "<p style='color: red; margin-left: 20px;'>❌ Chamada para softDeleteUser NÃO encontrada</p>";
    }
} else {
    echo "<p>❌ $controllerFile - NÃO existe</p>";
}

?>

<script>
function testarExclusao() {
    const resultado = document.getElementById('resultado');
    resultado.style.display = 'block';
    resultado.innerHTML = '⏳ Testando exclusão de conta...';
    
    console.log('🔍 DEBUG: Iniciando teste de exclusão');
    console.log('🔍 DEBUG: Admin ID:', <?php echo $_SESSION['user_id']; ?>);
    console.log('🔍 DEBUG: User ID a ser excluído: 38');
    
    // Testar endpoint usuario/deleteAccount
    fetch('http://localhost/nixcom/adms/usuario/deleteAccount', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            user_id: 38, // ID do usuário a ser excluído
            admin_action: true
        })
    })
    .then(response => {
        console.log('🔍 DEBUG: Status da resposta:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('🔍 DEBUG: Resposta bruta:', text);
        try {
            const data = JSON.parse(text);
            console.log('🔍 DEBUG: Dados parseados:', data);
            
            if (data.success) {
                resultado.innerHTML = `
                    <div style="color: green; font-weight: bold;">
                        ✅ SUCESSO!<br>
                        <strong>Mensagem:</strong> ${data.message}<br>
                        <strong>Redirecionamento:</strong> ${data.redirect || 'N/A'}
                    </div>
                `;
            } else {
                resultado.innerHTML = `
                    <div style="color: red; font-weight: bold;">
                        ❌ ERRO!<br>
                        <strong>Mensagem:</strong> ${data.message}
                    </div>
                `;
            }
        } catch (e) {
            console.error('🔍 DEBUG: Erro ao parsear JSON:', e);
            resultado.innerHTML = `
                <div style="color: red; font-weight: bold;">
                    ❌ ERRO DE PARSE!<br>
                    <strong>Resposta bruta:</strong><br>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;">${text}</pre>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('🔍 DEBUG: Erro na requisição:', error);
        resultado.innerHTML = `
            <div style="color: red; font-weight: bold;">
                ❌ ERRO DE CONEXÃO!<br>
                <strong>Erro:</strong> ${error.message}
            </div>
        `;
    });
}

function limparLogs() {
    document.getElementById('resultado').style.display = 'none';
    console.clear();
}
</script>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f8f9fa;
}

h1, h2 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

table {
    width: 100%;
    margin: 10px 0;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background: #007bff;
    color: white;
}

button {
    transition: all 0.3s ease;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>

