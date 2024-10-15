<?php

namespace Core\Modules\Trash;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;

class TrashRepository
{
    public function __construct()
    {
    }

    public   function getTrashed()
    {
        $modulesPath = base_path() . '/src/Modules'; // Chemin vers le répertoire Modules
        $softDeletedData = [];
        // Obtenez la liste de tous les fichiers de modèle
        $modelFiles = File::allFiles($modulesPath);
        foreach ($modelFiles as $modelFile) {
            $modelFilePath = $modelFile->getRealPath();
            $modelContent = file_get_contents($modelFilePath);
            // Utilisez l'expression régulière pour extraire le namespace et le nom de la classe
            $pattern = '/namespace (.*?);.*?class (.*?) /s';
            if (preg_match($pattern, $modelContent, $matches)) {
                $namespace = $matches[1];
                $className = $matches[2];
                $fullClassName = $namespace . '\\' . $className;
                // Chargez dynamiquement la classe du modèle
                if (class_exists($fullClassName) && is_subclass_of($fullClassName, 'Illuminate\Database\Eloquent\Model')) {
                    $modelInstance = new $fullClassName;
                    // Vérifiez si le modèle utilise SoftDeletes
                    if (in_array(SoftDeletes::class, class_uses($modelInstance))) {
                        $softDeletedData[$fullClassName] = $fullClassName::onlyTrashed()->with('deletedBy')->get();

                    }
                }
            }
        }
        return $softDeletedData;
    }


    public function getAllModels()
    {
        $modelesFichiers = $this->finModelsFolder();

        return response(['files' => $modelesFichiers]);
    }

    public function finModelsFolder()
    {
        $modulesPath = base_path() . '/src/Modules'; // Chemin vers le répertoire Modules
        $softDeletedData = [];

        // Obtenez la liste de tous les fichiers de modèle
        $modelFiles = File::allFiles($modulesPath);

        foreach ($modelFiles as $modelFile) {
            $modelFilePath = $modelFile->getRealPath();
            $modelContent = file_get_contents($modelFilePath);

            // Utilisez l'expression régulière pour extraire le namespace et le nom de la classe
            $pattern = '/namespace (.*?);.*?class (.*?) /s';
            if (preg_match($pattern, $modelContent, $matches)) {
                $namespace = $matches[1];
                $className = $matches[2];
                $fullClassName = $namespace . '\\' . $className;

                // Chargez dynamiquement la classe du modèle
                if (class_exists($fullClassName) && is_subclass_of($fullClassName, 'Illuminate\Database\Eloquent\Model')) {
                    $modelInstance = new $fullClassName;

                    // Vérifiez si le modèle utilise SoftDeletes
                    if (in_array(SoftDeletes::class, class_uses($modelInstance))) {
                        if ($className != "ProfessionalPunctualServices") {
                            $softDeletedData[$fullClassName] = $fullClassName::onlyTrashed()->get();
                        }
                    }
                }
            }
        }
        return $softDeletedData;
    }
}
