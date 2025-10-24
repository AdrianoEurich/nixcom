<?php
/**
 * Script para corrigir URLs de mídia nos anúncios
 * Remove a URL base dos caminhos que estão salvos com URL completa
 */

// Configurações do banco
$host = 'localhost';
$dbname = 'nixcom';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 CORRIGINDO URLs DE MÍDIA NOS ANÚNCIOS...\n\n";
    
    // URL base que deve ser removida
    $baseUrl = 'http://localhost/nixcom/';
    
    // Corrigir cover_photo_path
    echo "📸 Corrigindo cover_photo_path...\n";
    $stmt = $pdo->prepare("
        UPDATE anuncios 
        SET cover_photo_path = REPLACE(cover_photo_path, :baseUrl, '') 
        WHERE cover_photo_path LIKE :baseUrlPattern
    ");
    $stmt->execute([
        'baseUrl' => $baseUrl,
        'baseUrlPattern' => $baseUrl . '%'
    ]);
    $coverPhotosUpdated = $stmt->rowCount();
    echo "✅ $coverPhotosUpdated cover_photo_path corrigidos\n\n";
    
    // Corrigir confirmation_video_path
    echo "🎥 Corrigindo confirmation_video_path...\n";
    $stmt = $pdo->prepare("
        UPDATE anuncios 
        SET confirmation_video_path = REPLACE(confirmation_video_path, :baseUrl, '') 
        WHERE confirmation_video_path LIKE :baseUrlPattern
    ");
    $stmt->execute([
        'baseUrl' => $baseUrl,
        'baseUrlPattern' => $baseUrl . '%'
    ]);
    $videosUpdated = $stmt->rowCount();
    echo "✅ $videosUpdated confirmation_video_path corrigidos\n\n";
    
    // Verificar resultados
    echo "🔍 VERIFICAÇÃO FINAL:\n";
    
    // Verificar anúncios com URLs completas
    $stmt = $pdo->prepare("
        SELECT id, cover_photo_path, confirmation_video_path 
        FROM anuncios 
        WHERE cover_photo_path LIKE :baseUrlPattern 
           OR confirmation_video_path LIKE :baseUrlPattern
    ");
    $stmt->execute(['baseUrlPattern' => $baseUrl . '%']);
    $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($remaining)) {
        echo "✅ Todos os caminhos foram corrigidos!\n";
    } else {
        echo "⚠️ Ainda existem $remaining caminhos com URLs completas:\n";
        foreach ($remaining as $row) {
            echo "  - Anúncio ID {$row['id']}: {$row['cover_photo_path']} | {$row['confirmation_video_path']}\n";
        }
    }
    
    echo "\n🎉 CORREÇÃO CONCLUÍDA!\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>

