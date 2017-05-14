<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;

/**
 * Validator pour les fichier CSV dans les formulaires
 * @package AppBundle\Validator\Constraints
 */
class CSVValidator extends ConstraintValidator
{
    /**
     * Fonction de validation de la contrainte de fichier CSV
     *
     * @param mixed $value Valeur du formulaire
     * @param Constraint $constraint Contrainte associée
     */
    public function validate($value, Constraint $constraint)
    {
        //Liste des extensions autorisés
        $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');

        //Récupération de l'extension du fichier uploadé
        $finfo = new FileinfoMimeTypeGuesser();
        $mimetype = $finfo->guess($value);

        //Le fichier n'est pas un CSV
        if(!in_array($mimetype, $mimes)) {
            //Création du message d'erreur (violation de la contrainte)
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}