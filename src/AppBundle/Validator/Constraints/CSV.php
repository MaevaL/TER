<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte pour les formulaires permettant de vérifier que le fichier uploadé est un ficher CSV
 *
 * @package AppBundle\Validator\Constraints
 */
class CSV extends Constraint
{
    /**
     * @var string Message d'erreur si la contrainte n'est pas respectée
     */
    public $message =  'Le type du fichier est invalide. Seuls les CSV sont autorisés.';

    /**
     * @return string Renvoi le nom du validator à utiliser
     */
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}