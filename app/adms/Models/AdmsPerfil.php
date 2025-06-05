<?php

namespace Adms\Models;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Sts\Models\Helper\StsConn;
use PDO;
use PDOException;

class AdmsPerfil extends StsConn
{
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

        // --- AQUI É A LINHA QUE MUDOU! ---
        // Caminho do AdmsPerfil: C:\xampp\htdocs\nixcom\app\adms\Models\
        // Destino desejado:    C:\xampp\htdocs\nixcom\app\adms\assets\images\users\
        // Para ir de Models/ -> app/adms/ -> app/adms/assets/images/users/
        $fullUploadPath = __DIR__ . '/../' . $uploadDir;
        // Explicação:
        // __DIR__ é C:\xampp\htdocs\nixcom\app\adms\Models\
        // /../     -> C:\xampp\htdocs\nixcom\app\adms\
        // $uploadDir ('assets/images/users/') -> C:\xampp\htdocs\nixcom\app\adms\assets\images\users\
        // Note que no seu controller Perfil.php, $uploadDir deve ser 'assets/images/users/'
        // ou seja, sem a barra inicial, pois ele é relativo à pasta adms/
        // --- FIM DA LINHA QUE MUDOU! ---
        
        if (!is_dir($fullUploadPath)) {
            if (!mkdir($fullUploadPath, 0777, true)) {
                $response['message'] = 'Não foi possível criar o diretório de upload.';
                return $response;
            }
        }

        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExtension;
        $targetFilePath = $fullUploadPath . $newFileName;
        $databaseFileName = $newFileName; 

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            try {
                $conn = $this->connectDb(); 
                $query = "UPDATE usuarios SET foto = :foto WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':foto', $databaseFileName, PDO::PARAM_STR);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Foto de perfil atualizada com sucesso!';
                    $response['new_photo_path'] = $databaseFileName; 
                } else {
                    $response['message'] = 'Nenhuma alteração foi feita na foto de perfil. (Usuário não encontrado ou mesma foto?)';
                    if (file_exists($targetFilePath)) {
                        unlink($targetFilePath);
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro ao atualizar foto no DB (AdmsPerfil::processarUploadFoto): " . $e->getMessage());
                $response['message'] = 'Erro no banco de dados ao atualizar foto.';
                if (file_exists($targetFilePath)) {
                    unlink($targetFilePath);
                }
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
}