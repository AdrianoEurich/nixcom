<?php

namespace Sts\Controllers;

use PDO;
use PDOException;

class Anuncio
{
    private PDO $pdo;
    private array $data = [];

    public function __construct()
    {
        // Conexão direta para este script
        try {
            $host = 'localhost';
            $dbname = 'nixcom';
            $username = 'root';
            $password = '';
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("ERRO CONEXÃO ANUNCIO: " . $e->getMessage());
            $this->pdo = null;
        }
    }

    public function visualizar(): void
    {
        if (!$this->pdo) {
            $this->data['erro'] = 'Erro de conexão com o banco de dados';
            $loadView = new \Core\ConfigView("sts/Views/anuncio/visualizar", $this->data);
            $loadView->loadView();
            return;
        }
        
        // Obter ID da URL amigável
        $url = $_GET['url'] ?? '';
        $urlParts = explode('/', $url);
        
        if (count($urlParts) >= 3 && $urlParts[0] === 'anuncio' && $urlParts[1] === 'visualizar') {
            $id = (int)$urlParts[2];
        } else {
            $id = (int)($_GET['id'] ?? 0);
        }
        
        if (!$id) {
            $this->data['erro'] = 'ID do anúncio não fornecido';
            $loadView = new \Core\ConfigView("sts/Views/anuncio/visualizar", $this->data);
            $loadView->loadView();
            return;
        }
        
        try {
            // Buscar anúncio
            $stmt = $this->pdo->prepare("
                SELECT 
                    a.id,
                    a.service_name as nome,
                    a.description as descricao,
                    a.price_15min,
                    a.price_30min,
                    a.price_1h,
                    a.phone_number as telefone,
                    a.cover_photo_path as foto_principal,
                    a.status,
                    a.categoria,
                    a.plan_type,
                    a.age as idade,
                    a.height_m as altura,
                    a.weight_kg as peso,
                    a.gender as genero,
                    a.nationality as nacionalidade,
                    a.ethnicity as etnia,
                    a.eye_color as cor_olhos,
                    a.neighborhood_name as bairro,
                    c.Nome as cidade,
                    e.Nome as estado
                FROM anuncios a
                LEFT JOIN cidade c ON CAST(a.city_id AS UNSIGNED) = c.Codigo
                LEFT JOIN estado e ON a.state_id = e.Uf
                WHERE a.id = ?
            ");
            
            $stmt->execute([$id]);
            $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            
            if (!$anuncio) {
                $this->data['erro'] = 'Anúncio não encontrado ou inativo';
            } else {
                // Buscar todas as fotos da galeria
                $stmt_fotos = $this->pdo->prepare("
                    SELECT path, order_index 
                    FROM anuncio_fotos 
                    WHERE anuncio_id = ? 
                    ORDER BY order_index ASC, created_at ASC
                ");
                $stmt_fotos->execute([$id]);
                $fotos_galeria = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);
                
                // Buscar todos os áudios da galeria
                $stmt_audios = $this->pdo->prepare("
                    SELECT path, created_at 
                    FROM anuncio_audios 
                    WHERE anuncio_id = ? 
                    ORDER BY created_at ASC
                ");
                $stmt_audios->execute([$id]);
                $audios_galeria = $stmt_audios->fetchAll(PDO::FETCH_ASSOC);
                
                // Buscar todos os vídeos da galeria
                $stmt_videos = $this->pdo->prepare("
                    SELECT path, created_at 
                    FROM anuncio_videos 
                    WHERE anuncio_id = ? 
                    ORDER BY created_at ASC
                ");
                $stmt_videos->execute([$id]);
                $videos_galeria = $stmt_videos->fetchAll(PDO::FETCH_ASSOC);
                
                // Combinar foto principal com fotos da galeria
                $todas_fotos = [];
                
                // Adicionar foto principal primeiro (se existir)
                if (!empty($anuncio['foto_principal'])) {
                    // Adicionar URL base se não for uma URL completa
                    $fotoPath = $anuncio['foto_principal'];
                    if (!filter_var($fotoPath, FILTER_VALIDATE_URL)) {
                        $fotoPath = URL . $fotoPath;
                    }
                    // Adicionar cache-busting para imagens
                    $fotoPath .= '?t=' . time();
                    $todas_fotos[] = [
                        'path' => $fotoPath,
                        'order_index' => -1,
                        'is_cover' => true
                    ];
                }
                
                // Adicionar fotos da galeria
                foreach ($fotos_galeria as $foto) {
                    // Adicionar URL base se não for uma URL completa
                    $fotoPath = $foto['path'];
                    if (!filter_var($fotoPath, FILTER_VALIDATE_URL)) {
                        $fotoPath = URL . $fotoPath;
                    }
                    // Adicionar cache-busting para imagens
                    $fotoPath .= '?t=' . time();
                    $todas_fotos[] = [
                        'path' => $fotoPath,
                        'order_index' => $foto['order_index'],
                        'is_cover' => false
                    ];
                }
                
                // Adicionar URL base à foto principal se não for uma URL completa
                if (!empty($anuncio['foto_principal'])) {
                    if (!filter_var($anuncio['foto_principal'], FILTER_VALIDATE_URL)) {
                        $anuncio['foto_principal'] = URL . $anuncio['foto_principal'];
                    }
                    // Adicionar cache-busting para foto principal
                    $anuncio['foto_principal'] .= '?t=' . time();
                }
                
                // Adicionar URL base aos vídeos se não forem URLs completas
                foreach ($videos_galeria as &$video) {
                    if (!filter_var($video['path'], FILTER_VALIDATE_URL)) {
                        $video['path'] = URL . $video['path'];
                    }
                    // Adicionar cache-busting para vídeos
                    $video['path'] .= '?t=' . time();
                }
                
                // Adicionar URL base aos áudios se não forem URLs completas
                foreach ($audios_galeria as &$audio) {
                    if (!filter_var($audio['path'], FILTER_VALIDATE_URL)) {
                        $audio['path'] = URL . $audio['path'];
                    }
                    // Adicionar cache-busting para áudios
                    $audio['path'] .= '?t=' . time();
                }
                
                // Buscar aparências
                $stmt_aparencias = $this->pdo->prepare("
                    SELECT aparencia_item 
                    FROM anuncio_aparencias 
                    WHERE anuncio_id = ?
                ");
                $stmt_aparencias->execute([$id]);
                $aparencias = $stmt_aparencias->fetchAll(PDO::FETCH_COLUMN);
                
                // Buscar idiomas
                $stmt_idiomas = $this->pdo->prepare("
                    SELECT idioma_name 
                    FROM anuncio_idiomas 
                    WHERE anuncio_id = ?
                ");
                $stmt_idiomas->execute([$id]);
                $idiomas = $stmt_idiomas->fetchAll(PDO::FETCH_COLUMN);
                
                // Buscar locais de atendimento
                $stmt_locais = $this->pdo->prepare("
                    SELECT local_name 
                    FROM anuncio_locais_atendimento 
                    WHERE anuncio_id = ?
                ");
                $stmt_locais->execute([$id]);
                $locais = $stmt_locais->fetchAll(PDO::FETCH_COLUMN);
                
                // Buscar formas de pagamento
                $stmt_pagamentos = $this->pdo->prepare("
                    SELECT forma_name 
                    FROM anuncio_formas_pagamento 
                    WHERE anuncio_id = ?
                ");
                $stmt_pagamentos->execute([$id]);
                $pagamentos = $stmt_pagamentos->fetchAll(PDO::FETCH_COLUMN);
                
                // Buscar serviços oferecidos
                $stmt_servicos = $this->pdo->prepare("
                    SELECT servico_name 
                    FROM anuncio_servicos_oferecidos 
                    WHERE anuncio_id = ?
                ");
                $stmt_servicos->execute([$id]);
                $servicos = $stmt_servicos->fetchAll(PDO::FETCH_COLUMN);
                
                $anuncio['todas_fotos'] = $todas_fotos;
                $anuncio['audios'] = $audios_galeria;
                $anuncio['videos'] = $videos_galeria;
                $anuncio['aparencias'] = $aparencias;
                $anuncio['idiomas'] = $idiomas;
                $anuncio['locais'] = $locais;
                $anuncio['pagamentos'] = $pagamentos;
                $anuncio['servicos'] = $servicos;
                $this->data['anuncio'] = $anuncio;
            }
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar anúncio: " . $e->getMessage());
            $this->data['erro'] = 'Erro interno do servidor';
        }
        
        $loadView = new \Core\ConfigView("sts/Views/anuncio/visualizar", $this->data);
        $loadView->loadView();
    }
}