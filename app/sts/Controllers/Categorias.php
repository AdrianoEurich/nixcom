<?php

namespace Sts\Controllers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PDO;

class Categorias
{
    private array $data;
    private ?PDO $pdo;

    public function __construct()
    {
        try {
            $host = 'localhost';
            $dbname = 'nixcom';
            $username = 'root';
            $password = '';
            
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("ERRO CONEXÃO CATEGORIAS: " . $e->getMessage());
            $this->pdo = null;
        }
    }

    public function index(): void
    {
        $this->logDebug("DEBUG CATEGORIAS: Método index() chamado!");
        // Verificar se é uma rota amigável (categorias/mulher, categorias/homem, etc.)
        $url = $_GET['url'] ?? '';
        $urlParts = explode('/', $url);
        
        if (count($urlParts) >= 2 && $urlParts[0] === 'categorias') {
            $categoria = $urlParts[1];
        } else {
            $categoria = $_GET['categoria'] ?? 'mulher';
        }
        
        $this->logDebug("DEBUG CATEGORIAS: index() - categoria='$categoria', url='$url'");
        $this->processarCategoria($categoria);
    }
    
    // Método para processar qualquer categoria (mulher, homem, trans)
    public function mulher(): void
    {
        $this->logDebug("DEBUG CATEGORIAS: Método mulher() chamado!");
        $this->processarCategoria('mulher');
    }
    
    public function homem(): void
    {
        $this->logDebug("DEBUG CATEGORIAS: Método homem() chamado!");
        $this->processarCategoria('homem');
    }
    
    public function trans(): void
    {
        $this->logDebug("DEBUG CATEGORIAS: Método trans() chamado!");
        $this->processarCategoria('trans');
    }
    
    private function processarCategoria(string $categoria): void
    {
        
        // Validar categoria
        if (!in_array($categoria, ['mulher', 'homem', 'trans'])) {
            $categoria = 'mulher';
        }
        
        // Log para debug
        error_log("DEBUG CATEGORIAS: categoria='$categoria'");
        
        $anuncios = $this->buscarAnunciosPorCategoria($categoria);
        
        $this->data = [
            'categoria' => $categoria,
            'anuncios' => $anuncios
        ];
        
        // Debug adicional
        $this->logDebug("DEBUG CATEGORIAS: processarCategoria - categoria='$categoria'");
        $this->logDebug("DEBUG CATEGORIAS: processarCategoria - data['categoria']='{$this->data['categoria']}'");
        
        $loadView = new \Core\ConfigView("sts/Views/home/categorias", $this->data);
        $loadView->loadView();
    }

    private function buscarAnunciosPorCategoria(string $categoria): array
    {
        if (!$this->pdo) {
            error_log("DEBUG CATEGORIAS: PDO não disponível");
            return [];
        }

        try {
            // Mapear categoria para gênero
            $genero = $this->mapearCategoriaParaGenero($categoria);
            error_log("DEBUG CATEGORIAS: Buscando anúncios para categoria '$categoria' -> gênero '$genero'");
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    a.id,
                    a.service_name as nome,
                    a.description as descricao,
                    a.price_1h as preco,
                    a.phone_number as telefone,
                    a.cover_photo_path as foto_principal,
                    a.neighborhood_name as bairro,
                    a.status,
                    a.gender,
                    c.Nome as cidade,
                    e.Nome as estado
                FROM anuncios a
                LEFT JOIN cidade c ON CAST(a.city_id AS UNSIGNED) = c.Codigo
                LEFT JOIN estado e ON a.state_id = e.Uf
                WHERE a.gender = ? 
                AND a.status = 'active'
                ORDER BY a.created_at DESC
                LIMIT 50
            ");

            $stmt->execute([$genero]);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG CATEGORIAS: Encontrados " . count($resultado) . " anúncios para gênero '$genero'");
            
            // Processar URLs das fotos para adicionar URL base
            foreach ($resultado as &$anuncio) {
                if (!empty($anuncio['foto_principal'])) {
                    // Adicionar URL base se não for uma URL completa
                    if (!filter_var($anuncio['foto_principal'], FILTER_VALIDATE_URL)) {
                        $anuncio['foto_principal'] = URL . $anuncio['foto_principal'];
                    }
                    // Adicionar cache-busting para imagens
                    $anuncio['foto_principal'] .= '?t=' . time();
                }
            }
            
            if (count($resultado) > 0) {
                error_log("DEBUG CATEGORIAS: Primeiro anúncio: " . json_encode($resultado[0]));
            }
            
            return $resultado;

        } catch (PDOException $e) {
            error_log("ERRO BUSCAR ANÚNCIOS CATEGORIA: " . $e->getMessage());
            return [];
        }
    }

    private function mapearCategoriaParaGenero(string $categoria): string
    {
        $mapeamento = [
            "mulher" => "Feminino",
            "homem" => "Masculino", 
            "trans" => "Trans"
        ];
        return $mapeamento[$categoria] ?? "Feminino";
    }
    
    private function logDebug(string $message): void
    {
        $log_file = 'debug_categorias.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
