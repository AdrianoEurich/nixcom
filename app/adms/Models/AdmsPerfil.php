<?php
// app/adms/Models/AdmsPerfil.php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

// Classe de conexão herdada do seu sistema.
use Sts\Models\Helper\StsConn;
use PDO;
use PDOException;
use Exception;

/**
 * Modelo para gerenciar operações de perfil do usuário, como atualização de
 * foto, nome, senha e exclusão (soft delete) da conta.
 */
class AdmsPerfil extends StsConn
{
    /**
     * @var object A conexão com o banco de dados.
     */
    private object $conn;

    public function __construct()
    {
        // Obtém a conexão do banco de dados através da classe pai (StsConn).
        $this->conn = $this->connectDb();
    }

    /**
     * Processa o upload da foto de perfil e atualiza o banco de dados.
     * @param array $file Array $_FILES['input_name'] contendo os dados do arquivo.
     * @param int $userId ID do usuário.
     * @param string $uploadDir Caminho do diretório de upload relativo à raiz do projeto.
     * @return array Array associativo com 'success' (bool), 'message' (string) e 'new_photo_path' (string, se sucesso).
     */
    public function processarUploadFoto(array $file, int $userId, string $uploadDir): array
    {
        $response = ['success' => false, 'message' => ''];

        if (!isset($file['error']) || is_array($file['error'])) {
            $response['message'] = 'Erro ao processar o upload do arquivo.';
            return $response;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Erro no upload: ' . $file['error']; 
            return $response;
        }

        // Validação de tamanho (32MB)
        if ($file['size'] > 32 * 1024 * 1024) {
            $response['message'] = 'Arquivo muito grande. Máximo 32MB.';
            return $response;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            $response['message'] = 'Tipo de arquivo inválido. Apenas imagens JPEG, PNG, GIF e WEBP são permitidas.';
            return $response;
        }

        // Verificação adicional de conteúdo real da imagem
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $response['message'] = 'Arquivo não é uma imagem válida.';
            return $response;
        }

        $fullUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/' . $uploadDir;
        
        if (!is_dir($fullUploadPath)) {
            if (!mkdir($fullUploadPath, 0777, true)) {
                $response['message'] = 'Não foi possível criar o diretório de upload.';
                return $response;
            }
        }

        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExtension;
        $targetFilePath = $fullUploadPath . $newFileName;

        try {
            $queryOldPhoto = "SELECT foto FROM usuarios WHERE id = :id LIMIT 1";
            $stmtOldPhoto = $this->conn->prepare($queryOldPhoto);
            $stmtOldPhoto->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmtOldPhoto->execute();
            $oldPhotoFileName = $stmtOldPhoto->fetchColumn();

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                $query = "UPDATE usuarios SET foto = :foto WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':foto', $newFileName, PDO::PARAM_STR);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    if (!empty($oldPhotoFileName) && $oldPhotoFileName !== 'usuario.png' && file_exists($fullUploadPath . $oldPhotoFileName)) {
                        unlink($fullUploadPath . $oldPhotoFileName);
                    }
                    $response['success'] = true;
                    $response['message'] = 'Foto de perfil atualizada com sucesso!';
                    $response['new_photo_path'] = $newFileName;
                } else {
                    $response['message'] = 'Nenhuma alteração foi feita na foto de perfil.';
                    if (file_exists($targetFilePath)) {
                        unlink($targetFilePath);
                    }
                }
            } else {
                $response['message'] = 'Falha ao mover o arquivo de upload para o destino final.';
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar foto no DB (AdmsPerfil::processarUploadFoto): " . $e->getMessage());
            $response['message'] = 'Erro no banco de dados ao atualizar foto.';
        }
        return $response;
    }

    /**
     * Remove a foto de perfil do usuário, retornando-a para 'usuario.png'.
     * @param int $userId ID do usuário.
     * @param string $currentFoto Nome do arquivo da foto atual do usuário.
     * @param string $uploadDir Caminho do diretório de upload relativo à raiz do projeto.
     * @return array Array associativo com 'success' (bool) e 'message' (string).
     */
    public function removerFotoPerfil(int $userId, string $currentFoto, string $uploadDir): array
    {
        $response = ['success' => false, 'message' => ''];
        $fullUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/' . $uploadDir;

        if ($currentFoto === 'usuario.png' || empty($currentFoto)) {
            $response['message'] = 'Não há foto de perfil para remover.';
            return $response;
        }

        $filePath = $fullUploadPath . $currentFoto;
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                $response['message'] = 'Erro ao excluir o arquivo da foto do servidor.';
                return $response;
            }
        } else {
            error_log("Alerta: Arquivo de foto de perfil '{$currentFoto}' não encontrado em {$fullUploadPath} para o usuário ID {$userId}.");
        }

        try {
            $query = "UPDATE usuarios SET foto = :default_foto WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $defaultFoto = 'usuario.png';
            $stmt->bindParam(':default_foto', $defaultFoto, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Foto de perfil removida e redefinida para a padrão.';
            } else {
                $response['message'] = 'Não foi possível atualizar a foto no banco de dados.';
            }
        } catch (PDOException $e) {
            error_log("Erro no banco de dados ao remover foto de perfil (AdmsPerfil::removerFotoPerfil): " . $e->getMessage());
            $response['message'] = 'Erro no banco de dados ao remover a foto.';
        }
        return $response;
    }

    /**
     * Atualiza o nome do usuário no banco de dados.
     * @param int $userId ID do usuário.
     * @param string $novoNome Novo nome do usuário.
     * @return array Array associativo com 'success' (bool), 'message' (string) e 'changed' (bool).
     */
    public function atualizarNome(int $userId, string $novoNome): array
    {
        $response = ['success' => false, 'message' => '', 'changed' => false];
        try {
            $queryCheck = "SELECT nome FROM usuarios WHERE id = :id";
            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $currentName = $stmtCheck->fetchColumn();

            if ($currentName === $novoNome) {
                $response['success'] = true;
                $response['message'] = 'O nome não foi alterado (é o mesmo que o atual).';
                $response['changed'] = false;
                return $response;
            }

            $query = "UPDATE usuarios SET nome = :nome WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $novoNome, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Nome atualizado com sucesso!';
                $response['changed'] = true;
            } else {
                $response['message'] = 'Nenhuma alteração foi feita no nome.';
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar nome no modelo (AdmsPerfil::atualizarNome): " . $e->getMessage());
            $response['message'] = 'Erro no banco de dados ao atualizar o nome.';
        }
        return $response;
    }

    /**
     * Atualiza a senha do usuário.
     * @param int $userId ID do usuário.
     * @param string $senhaAtual A senha atual do usuário.
     * @param string $novaSenha A nova senha a ser salva.
     * @return array Array associativo com 'success' (bool) e 'message' (string).
     */
    public function atualizarSenha(int $userId, string $senhaAtual, string $novaSenha): array
    {
        $response = ['success' => false, 'message' => ''];
        try {
            $query = "SELECT senha FROM usuarios WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($senhaAtual, $user['senha'])) {
                $response['message'] = 'Senha atual incorreta.';
                return $response;
            }

            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            $queryUpdate = "UPDATE usuarios SET senha = :nova_senha WHERE id = :id";
            $stmtUpdate = $this->conn->prepare($queryUpdate);
            $stmtUpdate->bindParam(':nova_senha', $novaSenhaHash, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmtUpdate->execute();

            if ($stmtUpdate->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Senha atualizada com sucesso!';
            } else {
                $response['message'] = 'Nenhuma alteração foi feita na senha. Talvez a nova senha seja igual à antiga?';
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar senha (AdmsPerfil::atualizarSenha): " . $e->getMessage());
            $response['message'] = 'Erro no banco de dados ao atualizar a senha.';
        }
        return $response;
    }
    
    /**
     * Realiza a exclusão definitiva do usuário e de todos os seus anúncios.
     * Este método remove permanentemente o usuário e todos os dados relacionados.
     *
     * @param int $userId O ID do usuário a ser excluído definitivamente.
     * @return array Retorna um array com 'success' (bool) e 'message' (string).
     */
    public function deleteUserAndAnuncios(int $userId): array
    {
        $response = ['success' => false, 'message' => ''];
        try {
            // Inicia uma transação para garantir que todas as operações sejam bem-sucedidas ou que nenhuma seja executada.
            $this->conn->beginTransaction();

            // 1. Remove a foto de perfil antes de excluir o usuário
            $queryOldPhoto = "SELECT foto FROM usuarios WHERE id = :id LIMIT 1";
            $stmtOldPhoto = $this->conn->prepare($queryOldPhoto);
            $stmtOldPhoto->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmtOldPhoto->execute();
            $oldPhotoFileName = $stmtOldPhoto->fetchColumn();

            if (!empty($oldPhotoFileName) && $oldPhotoFileName !== 'usuario.png') {
                $uploadDir = 'assets/images/users/';
                $fullUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/' . $uploadDir;
                $oldPhotoFullPath = $fullUploadPath . $oldPhotoFileName;
                if (file_exists($oldPhotoFullPath)) {
                    unlink($oldPhotoFullPath);
                }
            }

            // 2. Remove todos os dados relacionados aos anúncios do usuário
            $this->deleteAnuncioRelatedData($userId);

            // 3. Remove todos os anúncios do usuário
            $queryAnuncios = "DELETE FROM anuncios WHERE user_id = :user_id";
            $stmtAnuncios = $this->conn->prepare($queryAnuncios);
            $stmtAnuncios->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtAnuncios->execute();

            // 4. Remove o usuário definitivamente
            $queryUser = "DELETE FROM usuarios WHERE id = :user_id";
            $stmtUser = $this->conn->prepare($queryUser);
            $stmtUser->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtUser->execute();
            
            // Confirma as operações no banco de dados
            $this->conn->commit();
            $response['success'] = true;
            $response['message'] = "Conta e todos os dados foram excluídos definitivamente.";

        } catch (PDOException $e) {
            // Em caso de erro, desfaz as operações
            $this->conn->rollBack();
            error_log("ERRO MODEL ADMSPERFIL: Falha na exclusão definitiva de usuário e anúncios. Detalhes: " . $e->getMessage());
            $response['message'] = "Erro interno ao excluir a conta. Tente novamente mais tarde.";
        }
        return $response;
    }

    /**
     * Remove todos os dados relacionados aos anúncios de um usuário.
     * Inclui fotos, vídeos, áudios e dados de relacionamento.
     *
     * @param int $userId O ID do usuário
     * @return void
     */
    private function deleteAnuncioRelatedData(int $userId): void
    {
        // Busca todos os anúncios do usuário para obter os IDs
        $queryAnuncios = "SELECT id FROM anuncios WHERE user_id = :user_id";
        $stmtAnuncios = $this->conn->prepare($queryAnuncios);
        $stmtAnuncios->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtAnuncios->execute();
        $anuncioIds = $stmtAnuncios->fetchAll(PDO::FETCH_COLUMN);

        if (empty($anuncioIds)) {
            return; // Não há anúncios para processar
        }

        $anuncioIdsPlaceholder = implode(',', array_fill(0, count($anuncioIds), '?'));

        // Remove fotos de galeria e arquivos físicos
        $queryFotos = "SELECT path FROM anuncio_fotos WHERE anuncio_id IN ($anuncioIdsPlaceholder)";
        $stmtFotos = $this->conn->prepare($queryFotos);
        $stmtFotos->execute($anuncioIds);
        $fotos = $stmtFotos->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($fotos as $foto) {
            $this->deleteFileIfExists($foto, 'anuncios/galeria/');
        }
        $this->conn->prepare("DELETE FROM anuncio_fotos WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);

        // Remove vídeos e arquivos físicos
        $queryVideos = "SELECT path FROM anuncio_videos WHERE anuncio_id IN ($anuncioIdsPlaceholder)";
        $stmtVideos = $this->conn->prepare($queryVideos);
        $stmtVideos->execute($anuncioIds);
        $videos = $stmtVideos->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($videos as $video) {
            $this->deleteFileIfExists($video, 'anuncios/videos/');
        }
        $this->conn->prepare("DELETE FROM anuncio_videos WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);

        // Remove áudios e arquivos físicos
        $queryAudios = "SELECT path FROM anuncio_audios WHERE anuncio_id IN ($anuncioIdsPlaceholder)";
        $stmtAudios = $this->conn->prepare($queryAudios);
        $stmtAudios->execute($anuncioIds);
        $audios = $stmtAudios->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($audios as $audio) {
            $this->deleteFileIfExists($audio, 'anuncios/audios/');
        }
        $this->conn->prepare("DELETE FROM anuncio_audios WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);

        // Remove dados de relacionamento
        $this->conn->prepare("DELETE FROM anuncio_aparencias WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);
        $this->conn->prepare("DELETE FROM anuncio_idiomas WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);
        $this->conn->prepare("DELETE FROM anuncio_locais_atendimento WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);
        $this->conn->prepare("DELETE FROM anuncio_formas_pagamento WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);
        $this->conn->prepare("DELETE FROM anuncio_servicos_oferecidos WHERE anuncio_id IN ($anuncioIdsPlaceholder)")->execute($anuncioIds);

        // Remove fotos de capa e vídeos de confirmação
        $queryCapaVideo = "SELECT cover_photo_path, confirmation_video_path FROM anuncios WHERE user_id = :user_id";
        $stmtCapaVideo = $this->conn->prepare($queryCapaVideo);
        $stmtCapaVideo->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtCapaVideo->execute();
        $capaVideoData = $stmtCapaVideo->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($capaVideoData as $data) {
            if (!empty($data['cover_photo_path'])) {
                $this->deleteFileIfExists($data['cover_photo_path'], 'anuncios/capas/');
            }
            if (!empty($data['confirmation_video_path'])) {
                $this->deleteFileIfExists($data['confirmation_video_path'], 'anuncios/confirmation_videos/');
            }
        }
    }

    /**
     * Remove um arquivo físico se ele existir.
     *
     * @param string $filePath Caminho do arquivo
     * @param string $subDir Subdiretório dentro de uploads
     * @return void
     */
    private function deleteFileIfExists(string $filePath, string $subDir): void
    {
        if (empty($filePath)) {
            return;
        }

        // Extrai apenas o nome do arquivo do caminho completo
        $fileName = basename($filePath);
        $uploadDir = 'app/public/uploads/' . $subDir;
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/' . $uploadDir . $fileName;
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
