<?php

namespace AppBundle\Service;

class CSVToArray {

    public function convert($filename, $delimiter = ',', $head = null)
    {
        if(!file_exists($filename) || !is_readable($filename)) {
            return FALSE;
        }

        $header = true;

        $data = array();

        if (($handle = fopen($filename, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if($header && $head == null) {
                    $header = false;
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