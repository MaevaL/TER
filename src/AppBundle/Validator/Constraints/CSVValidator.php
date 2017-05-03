<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class CSVValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');

        $finfo = new FileinfoMimeTypeGuesser();
        $mimetype = $finfo->guess($value);

        if(!in_array($mimetype, $mimes)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}