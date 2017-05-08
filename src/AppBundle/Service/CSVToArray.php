<?php

namespace AppBundle\Service;

class CSVToArray {

    public function convert($filename, $delimiter = ',')
    {
        if(!file_exists($filename) || !is_readable($filename)) {
            return FALSE;
        }

        $header = true;
        $head = array(
            'firstname',
            'lastname',
            'numEtu',
            'email',
            'grade',
        );
        $data = array();

        if (($handle = fopen($filename, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if($header) {
                    $header = false;
                } else {
                    $data[] = array_combine($head, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

}