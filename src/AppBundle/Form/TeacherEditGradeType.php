<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class TeacherEditGradeType extends AbstractType
{
    private $gradeFloat;
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults( [
            'gradeFloat' => null,
        ] );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->gradeFloat = $options['gradeFloat'];
        $builder
            ->add('gradeFloat', NumberType::class, array(
                'label' => "Note de l'élève",
                'required' => true,
                'scale' => 2,
                'constraints' => array(
                    new GreaterThanOrEqual(array(
                        'value' => 0, 'message' => 'La note doit être supérieure ou égale à 0.')),
                    new LessThanOrEqual(array(
                        'value' => 20, 'message' => 'La note doit être inférieure ou égale à 20.'))
                ),
                'attr' => array(
                    'min' => 0,
                    'max' => 20,
                    'step' => 0.01,
                ),
                'data' => $this->gradeFloat,
            ))
        ;
    }

    public function getName()
    {
        return 'teacher_edit_grade';
    }
}