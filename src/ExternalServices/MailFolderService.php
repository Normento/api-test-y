<?php

namespace Core\ExternalServices;

class MailFolderService
{
    protected $filePath;

    public function __construct()
    {
        $this->filePath = public_path(asset(env("MAIL_MOVE_FILE")));
         // Vérifiez si le fichier existe, sinon, créez-le avec le contenu spécifié
         if (!file_exists($this->filePath)) {
            $this->writeData(['message_folder' => []]);
        }
    }

    public function getFolder($messageId)
    {
        $data = $this->readData();
        return $data['message_folder'][$messageId] ?? null;
    }

    public function setFolder($messageId, $folderName)
    {
        $data = $this->readData();
        $data['message_folder'][$messageId] = $folderName;
        $this->writeData($data);
    }

    protected function readData()
    {
        $contents = file_get_contents($this->filePath);
        return json_decode($contents, true) ?: [];
    }

    protected function writeData($data)
    {
        $contents = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($this->filePath, $contents);
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function removeFolder($messageId)
    {
        $data = $this->readData();

        if (isset($data['message_folder'][$messageId])) {
            unset($data['message_folder'][$messageId]);
            $this->writeData($data);
            return true;
        }

        return false;
    }
}
