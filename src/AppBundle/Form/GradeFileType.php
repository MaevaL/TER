<?php

namespace AppBundle\Form;

use AppBundle\Validator\Constraints\CSV;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;

class GradeFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gradeFile', FileType::class, array(
                'label' => 'Fichier de notes (CSV)',
                'constraints' => array(new CSV())
            ))
        ;
    }

    public function getName()
    {
        return 'grade_file';
    }
}