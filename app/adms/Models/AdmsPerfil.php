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
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; // Adicionei webp, se for permitido.

        if (!in_array($mimeType, $allowedMimeTypes)) {
            $response['message'] = 'Tipo de arquivo inválido. Apenas imagens JPEG, PNG, GIF e WEBP são permitidas.';
            return $response;
        }

        $fullUploadPath = __DIR__ . '/../../../' . $uploadDir; // Ajuste no caminho: 3 níveis acima para chegar na raiz do projeto /nixcom/app/adms/assets/
        // __DIR__ é C:\xampp\htdocs\nixcom\app\adms\Models\
        // /../ -> C:\xampp\htdocs\nixcom\app\adms\
        // /../ -> C:\xampp\htdocs\nixcom\app\
        // /../ -> C:\xampp\htdocs\nixcom\
        // $uploadDir ('app/adms/assets/images/users/') -> C:\xampp\htdocs\nixcom\app\adms\assets\images\users\
        // ALTERNATIVA MAIS SIMPLES: Definir a pasta base no ConfigAdm ou passar o caminho absoluto completo
        // Se a sua URLADM já é 'http://localhost/nixcom/adms/', e o diretório 'assets' está dentro de 'adms',
        // então o caminho *físico* no servidor deve ser construído a partir da raiz do seu projeto.
        // Considerando que a pasta `app/adms/assets/images/users/` é para imagens do ADM, 
        // a variável `$uploadDir` passada do controller deve ser `assets/images/users/`.
        // E para chegar nela a partir de `AdmsPerfil.php` (que está em `app/adms/Models/`),
        // precisamos subir dois níveis (`../../`) e então descer para `assets/images/users/`.
        // C:\xampp\htdocs\nixcom\app\adms\Models\ -> __DIR__
        // C:\xampp\htdocs\nixcom\app\adms\ -> __DIR__ . '/../'
        // C:\xampp\htdocs\nixcom\app\ -> __DIR__ . '/../../'
        // C:\xampp\htdocs\nixcom\ -> __DIR__ . '/../../../'
        // Então, $fullUploadPath = __DIR__ . '/../../../' . $uploadDir; estaria incorreto se $uploadDir = 'assets/images/users/'
        // O correto seria $fullUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/assets/images/users/';
        // OU, para manter relativo ao AdmsPerfil.php:
        // $fullUploadPath = __DIR__ . '/../assets/images/users/'; // Isso funcionaria se assets estivesse no mesmo nível que Models
        // MAS NÃO ESTÁ! assets está dentro de adms, Models também.
        // Então, o caminho correto relativo a 'app/adms/Models/' para 'app/adms/assets/images/users/' é:
        // C:\xampp\htdocs\nixcom\app\adms\Models\ -> C:\xampp\htdocs\nixcom\app\adms\assets\images\users\
        // Precisamos ir 'subir' Models, e 'descer' em assets.
        // __DIR__ . '/../assets/images/users/'
        // Esta linha estava *quase* certa, mas o $uploadDir que você passa no controller já contém 'assets/images/users/'.
        // Se o controller passar 'assets/images/users/', e o __DIR__ é `app/adms/Models/`, então:
        // `__DIR__ . '/../' . $uploadDir`
        // `C:\xampp\htdocs\nixcom\app\adms\Models\../assets/images/users/`
        // `C:\xampp\htdocs\nixcom\app\adms\assets\images\users\` -> Isso está correto para o $uploadDir que você passa!
        // A correção que você já tinha no controller: $uploadDir = 'assets/images/users/'; está certa.
        // A linha `$fullUploadPath = __DIR__ . '/../' . $uploadDir;` no modelo já está construindo o caminho físico correto
        // assumindo que `$uploadDir` do controller seja `assets/images/users/`.
        // Então, deixarei essa linha como está, pois parece correta para a sua estrutura.

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
        $databaseFileName = $newFileName; 

        // Antes de mover, obtenha o nome da foto atual para exclusão posterior
        $queryOldPhoto = "SELECT foto FROM usuarios WHERE id = :id LIMIT 1";
        $stmtOldPhoto = $this->connectDb()->prepare($queryOldPhoto);
        $stmtOldPhoto->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmtOldPhoto->execute();
        $oldPhotoFileName = $stmtOldPhoto->fetchColumn();

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            try {
                $conn = $this->connectDb(); 
                $query = "UPDATE usuarios SET foto = :foto WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':foto', $databaseFileName, PDO::PARAM_STR);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    // Se a atualização no DB foi bem-sucedida e a foto antiga não é a padrão, exclua a antiga
                    if (!empty($oldPhotoFileName) && $oldPhotoFileName !== 'usuario.png') {
                        $oldPhotoFullPath = $fullUploadPath . $oldPhotoFileName;
                        if (file_exists($oldPhotoFullPath)) {
                            unlink($oldPhotoFullPath);
                        }
                    }

                    $response['success'] = true;
                    $response['message'] = 'Foto de perfil atualizada com sucesso!';
                    $response['new_photo_path'] = $databaseFileName; 
                } else {
                    $response['message'] = 'Nenhuma alteração foi feita na foto de perfil. (Usuário não encontrado ou mesma foto?)';
                    // Se não houve alteração no DB, remova a foto recém-carregada para evitar lixo
                    if (file_exists($targetFilePath)) {
                        unlink($targetFilePath);
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro ao atualizar foto no DB (AdmsPerfil::processarUploadFoto): " . $e->getMessage());
                $response['message'] = 'Erro no banco de dados ao atualizar foto.';
                // Em caso de erro no DB, remova a foto recém-carregada
                if (file_exists($targetFilePath)) {
                    unlink($targetFilePath);
                }
            }
        } else {
            $response['message'] = 'Falha ao mover o arquivo de upload para o destino final.';
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
        $fullUploadPath = $_SERVER['DOCUMENT_ROOT'] . '/nixcom/app/adms/' . $uploadDir; // Caminho físico para o diretório de fotos

        // 1. Verificar se a foto atual não é a padrão (usuario.png)
        if ($currentFoto === 'usuario.png' || empty($currentFoto)) {
            $response['message'] = 'Não há foto de perfil para remover (já é a padrão ou vazia).';
            return $response;
        }

        // 2. Excluir o arquivo físico da foto (se existir e não for a padrão)
        $filePath = $fullUploadPath . $currentFoto;
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                $response['message'] = 'Erro ao excluir o arquivo da foto do servidor.';
                // Tenta prosseguir com a atualização do DB mesmo se a exclusão do arquivo falhar?
                // Decisão: para segurança, podemos parar aqui. Ou registrar o erro e continuar.
                // Por enquanto, vamos parar, pois o objetivo principal é remover a foto.
                return $response;
            }
        } else {
            // Se o arquivo não existe no sistema de arquivos, mas o DB aponta para ele,
            // isso é uma inconsistência. Podemos logar e prosseguir com a atualização do DB.
            error_log("Alerta: Arquivo de foto de perfil '{$currentFoto}' não encontrado em {$fullUploadPath} para o usuário ID {$userId}.");
        }

        // 3. Atualizar o nome da foto no banco de dados para 'usuario.png'
        try {
            $conn = $this->connectDb();
            $query = "UPDATE usuarios SET foto = :default_foto WHERE id = :id";
            $stmt = $conn->prepare($query);
            $defaultFoto = 'usuario.png';
            $stmt->bindParam(':default_foto', $defaultFoto, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Foto de perfil removida e redefinida para a padrão.';
            } else {
                $response['message'] = 'Não foi possível atualizar a foto no banco de dados. Usuário não encontrado ou já era a foto padrão.';
            }
        } catch (PDOException $e) {
            error_log("Erro no banco de dados ao remover foto de perfil (AdmsPerfil::removerFotoPerfil): " . $e->getMessage());
            $response['message'] = 'Erro no banco de dados ao remover a foto.';
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