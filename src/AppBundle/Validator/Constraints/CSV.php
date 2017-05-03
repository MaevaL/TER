<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CSV extends Constraint
{
    public $message =  'Le type du fichier est invalide. Seuls les CSV sont autorisés.';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}