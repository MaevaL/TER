<?php

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Service permettant de convertir un fichier CSV en un tableau PHP
 *
 * @package AppBundle\Service
 */
class CSVToArray {

    /**
     * Fonction de conversion
     *
     * @param $filename File Chemin du fichier à convertir
     * @param string $delimiter Caractère qui délimite chaque information
     * @param null $head Nom des différentes colonnes
     * @return array|bool Tableau du fichier CSV
     */
    public function convert($filename, $delimiter = ',', $head = null)
    {
        //Vérifier que le fichier existe et est lisible
        if(!file_exists($filename) || !is_readable($filename)) {
            return FALSE;
        }

        //Récupération des données dans le fichier
        $header = true;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                //Si pas de head en paramètre récupération de la première ligne comme header
                if($header) {
                    $header = false;
                    if($head == null)
                        $head = $row;
                } else {
                    $data[] = array_combine($head, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

}