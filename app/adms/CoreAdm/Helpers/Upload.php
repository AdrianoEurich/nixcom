<?php

namespace Adms\CoreAdm\Helpers;

if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Upload
{
    private array $file;
    private string $uploadPath;
    private string $newFileName;
    private array $msg = [];
    private bool $result = false;

    /**
     * Retorna o caminho do arquivo uploaded se for bem-sucedido.
     * @return string|false
     */
    public function getNewFileName(): string|false
    {
        return $this->result ? $this->newFileName : false;
    }

    /**
     * Retorna o resultado da operação de upload.
     * @return bool
     */
    public function getResult(): bool
    {
        return $this->result;
    }

    /**
     * Retorna mensagens de erro ou sucesso.
     * @return array
     */
    public function getMsg(): array
    {
        return $this->msg;
    }

    /**
     * Faz o upload de um único arquivo.
     *
     * @param array $file O array $_FILES para o arquivo (ex: $_FILES['foto_capa'])
     * @param string $uploadPath O diretório onde o arquivo será salvo (com barra final)
     * @return string|false O caminho relativo do arquivo salvo se sucesso, false caso contrário.
     */
    public function uploadFile(array $file, string $uploadPath): string|false
    {
        $this->file = $file;
        $this->uploadPath = rtrim($uploadPath, '/') . '/'; // Garante barra final

        // Validação básica do arquivo
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            $this->msg = ['type' => 'error', 'text' => 'Erro no upload: ' . $this->file['error']];
            $this->result = false;
            return false;
        }

        // Validação de tamanho (100MB para vídeos, 32MB para outros)
        $isVideo = strpos($this->file['type'], 'video/') === 0;
        $maxSize = $isVideo ? 100 * 1024 * 1024 : 32 * 1024 * 1024; // 100MB para vídeos, 32MB para outros
        $maxSizeMB = $isVideo ? 100 : 32;
        
        if ($this->file['size'] > $maxSize) {
            $this->msg = ['type' => 'error', 'text' => "Arquivo muito grande. Máximo {$maxSizeMB}MB."];
            $this->result = false;
            return false;
        }

        // Gera um nome único para o arquivo
        $extension = pathinfo($this->file['name'], PATHINFO_EXTENSION);
        $this->newFileName = uniqid() . '.' . strtolower($extension);
        $targetFile = $this->uploadPath . $this->newFileName;

        // Move o arquivo temporário para o destino final
        // Verifica se é um arquivo real de upload ou um arquivo de teste
        $isRealUpload = is_uploaded_file($this->file['tmp_name']);
        
        if ($isRealUpload) {
            // Arquivo real de upload HTTP
            if (move_uploaded_file($this->file['tmp_name'], $targetFile)) {
                $this->msg = ['type' => 'success', 'text' => 'Arquivo enviado com sucesso!'];
                $this->result = true;
                return $targetFile; // Retorna o caminho completo para salvar no DB
            } else {
                $this->msg = ['type' => 'error', 'text' => 'Falha ao mover o arquivo para o destino.'];
                $this->result = false;
                return false;
            }
        } else {
            // Arquivo de teste ou simulado - usar copy() em vez de move_uploaded_file()
            if (file_exists($this->file['tmp_name'])) {
                if (copy($this->file['tmp_name'], $targetFile)) {
                    $this->msg = ['type' => 'success', 'text' => 'Arquivo de teste copiado com sucesso!'];
                    $this->result = true;
                    return $targetFile; // Retorna o caminho completo para salvar no DB
                } else {
                    $this->msg = ['type' => 'error', 'text' => 'Falha ao copiar o arquivo de teste para o destino.'];
                    $this->result = false;
                    return false;
                }
            } else {
                $this->msg = ['type' => 'error', 'text' => 'Arquivo temporário não encontrado: ' . $this->file['tmp_name']];
                $this->result = false;
                return false;
            }
        }
    }
}