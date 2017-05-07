<?php

namespace AppBundle\Form;

use AppBundle\Validator\Constraints\CSV;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class GradeFileType extends AbstractType
{
    private $currentUser;
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults( [
            'user' => null,
        ] );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->currentUser = $options['user'];
        $builder
            ->add('gradeFile', FileType::class, array(
                'label' => 'Fichier de notes (CSV)',
                'constraints' => array(new CSV())
            ))
            ->add('ue', EntityType::class, array(
                'label' => "Sélectionnez l'UE correspondante",
                'class' => "AppBundle:UE",
                'choices' => $this->currentUser->getUes(),
                'choice_label' => "name",
            ))
        ;
    }

    public function getName()
    {
        return 'grade_file';
    }
}