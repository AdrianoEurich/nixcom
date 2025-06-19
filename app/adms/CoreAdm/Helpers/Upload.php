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

        // Gera um nome único para o arquivo
        $extension = pathinfo($this->file['name'], PATHINFO_EXTENSION);
        $this->newFileName = uniqid() . '.' . strtolower($extension);
        $targetFile = $this->uploadPath . $this->newFileName;

        // Move o arquivo temporário para o destino final
        if (move_uploaded_file($this->file['tmp_name'], $targetFile)) {
            $this->msg = ['type' => 'success', 'text' => 'Arquivo enviado com sucesso!'];
            $this->result = true;
            return $targetFile; // Retorna o caminho completo para salvar no DB
        } else {
            $this->msg = ['type' => 'error', 'text' => 'Falha ao mover o arquivo para o destino.'];
            $this->result = false;
            return false;
        }
    }
}