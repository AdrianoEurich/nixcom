<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDO;
use PDOException;
use Exception; // Para tratamento de exceções gerais

class AdmsPerfil extends StsConn
{
    private object $conn; // Adicionado para armazenar a conexão PDO
    private AdmsUser $admsUser; // Instância do modelo AdmsUser
    private AdmsAnuncio $admsAnuncio; // Instância do modelo AdmsAnuncio

    public function __construct()
    {
        $this->conn = $this->connectDb();
        $this->admsUser = new AdmsUser(); // Inicializa AdmsUser
        $this->admsAnuncio = new AdmsAnuncio(); // Inicializa AdmsAnuncio
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

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif']; 

        if (!in_array($mimeType, $allowedMimeTypes)) {
            $response['message'] = 'Tipo de arquivo inválido. Apenas imagens JPEG, PNG e GIF são permitidas.';
            return $response;
        }

        // Caminho completo para o diretório de upload.
        // Assumindo que $uploadDir é 'assets/images/users/' e PATH_ROOT é a raiz do projeto.
        // Ex: C:\xampp\htdocs\nixcom\assets\images\users\
        // A constante PATH_ROOT deve ser definida no seu arquivo de configuração principal.
        $projectRoot = defined('PATH_ROOT') ? PATH_ROOT : $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'nixcom' . DIRECTORY_SEPARATOR; // Fallback
        $fullUploadPath = $projectRoot . $uploadDir;
        
        if (!is_dir($fullUploadPath)) {
            if (!mkdir($fullUploadPath, 0777, true)) {
                $response['message'] = 'Não foi possível criar o diretório de upload.';
                return $response;
            }
        }

        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExtension;
        $targetFilePath = $fullUploadPath . $newFileName;
        $databaseFileName = $newFileName; // Apenas o nome do arquivo para o DB

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            try {
                // Não deleta a foto antiga do sistema de arquivos.
                // Apenas atualiza o nome no banco de dados.
                $query = "UPDATE usuarios SET foto = :foto WHERE id = :id";
                $stmt = $this->conn->prepare($query); // Usa $this->conn
                $stmt->bindParam(':foto', $databaseFileName, PDO::PARAM_STR);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Foto de perfil atualizada com sucesso!';
                    $response['new_photo_path'] = $databaseFileName; // Retorna apenas o nome do arquivo
                } else {
                    $response['message'] = 'Nenhuma alteração foi feita na foto de perfil. (Usuário não encontrado ou mesma foto?)';
                    // Se não houve alteração no DB, e o arquivo foi movido, pode ser um caso de "mesma foto".
                    // Neste cenário, mantemos o arquivo no servidor.
                }
            } catch (PDOException $e) {
                error_log("Erro ao atualizar foto no DB (AdmsPerfil::processarUploadFoto): " . $e->getMessage());
                $response['message'] = 'Erro no banco de dados ao atualizar foto.';
                // Em caso de erro no DB, o arquivo já está no servidor. Não o deletamos.
            }
        } else {
            $response['message'] = 'Falha ao mover o arquivo de upload para o destino final.';
        }

        return $response;
    }

    public function atualizarNome(int $userId, string $novoNome): array
    {
        $response = ['success' => false, 'message' => '', 'changed' => false];
        try {
            $conn = $this->connectDb();

            $queryCheck = "SELECT nome FROM usuarios WHERE id = :id";
            $stmtCheck = $conn->prepare($queryCheck);
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
            $stmt = $conn->prepare($query);
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

    public function atualizarSenha(int $userId, string $senhaAtual, string $novaSenha): array
    {
        $response = ['success' => false, 'message' => ''];
        try {
            $conn = $this->connectDb();

            $query = "SELECT senha FROM usuarios WHERE id = :id LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($senhaAtual, $user['senha'])) {
                $response['message'] = 'Senha atual incorreta.';
                return $response;
            }

            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            $queryUpdate = "UPDATE usuarios SET senha = :nova_senha WHERE id = :id";
            $stmtUpdate = $conn->prepare($queryUpdate);
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
     * Realiza o soft delete da conta de um usuário.
     * Marca o usuário como deletado na tabela 'usuarios' e, se existir,
     * marca o anúncio associado como 'deleted' na tabela 'anuncios'.
     * As mídias físicas são MANTIDAS.
     *
     * @param int $userId O ID do usuário a ser soft-deletado.
     * @return array Array associativo com 'success' (bool) e 'message' (string).
     */
    public function softDeleteUserAccount(int $userId): array
    {
        $response = ['success' => false, 'message' => ''];
        try {
            $this->conn->beginTransaction(); // Inicia a transação

            // 1. Soft delete do usuário
            $userSoftDeleteResult = $this->admsUser->softDeleteUser($userId);
            if (!$userSoftDeleteResult) {
                $this->conn->rollBack();
                $response['message'] = 'Erro ao desativar a conta do usuário.';
                error_log("ERRO PERFIL: Falha no soft delete do usuário ID: {$userId}");
                return $response;
            }
            error_log("DEBUG PERFIL: Usuário ID: {$userId} soft-deletado com sucesso.");

            // 2. Verificar se o usuário possui um anúncio e soft deletá-lo também
            $anuncio = $this->admsAnuncio->getAnuncioByUserId($userId); // Busca anúncio ATIVO
            if ($anuncio) {
                error_log("DEBUG PERFIL: Anúncio encontrado para o usuário ID: {$userId}. Anúncio ID: " . $anuncio['id']);
                $anuncioSoftDeleteResult = $this->admsAnuncio->updateAnuncioStatus($anuncio['id'], 'deleted', $userId);
                if (!$anuncioSoftDeleteResult) {
                    $this->conn->rollBack();
                    $response['message'] = 'Conta desativada, mas houve um erro ao desativar o anúncio associado.';
                    error_log("ERRO PERFIL: Falha no soft delete do anúncio ID: " . $anuncio['id'] . " para o usuário ID: {$userId}");
                    return $response;
                }
                error_log("DEBUG PERFIL: Anúncio ID: " . $anuncio['id'] . " soft-deletado com sucesso.");
            } else {
                error_log("DEBUG PERFIL: Nenhum anúncio ativo encontrado para o usuário ID: {$userId}.");
            }

            $this->conn->commit(); // Confirma a transação
            $response['success'] = true;
            $response['message'] = 'Sua conta foi desativada com sucesso.';
            error_log("DEBUG PERFIL: Transação de soft delete de conta e anúncio concluída com sucesso para o usuário ID: {$userId}.");

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $response['message'] = 'Erro no banco de dados ao desativar sua conta. Tente novamente.';
            error_log("ERRO PDO PERFIL: Exceção na transação de soft delete de conta: " . $e->getMessage());
        } catch (Exception $e) {
            $this->conn->rollBack();
            $response['message'] = 'Ocorreu um erro inesperado ao desativar sua conta. Tente novamente.';
            error_log("ERRO GERAL PERFIL: Exceção na transação de soft delete de conta: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
        }
        return $response;
    }
}
