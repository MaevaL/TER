<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

/**
 * Formulaire permettant à un professeur d'éditer la note d'un élève
 *
 * @package AppBundle\Form
 */
class TeacherEditGradeType extends AbstractType
{
    /**
     * @var float Note de l'élève
     */
    private $gradeFloat;

    /**
     * Récupération de la note de l'élève donnée en paramètre
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults( [
            'gradeFloat' => null,
        ] );
    }

    /**
     * Construction du formulaire
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        //Récupération de la note de l'élève
        $this->gradeFloat = $options['gradeFloat'];
        $builder
            //Note de l'élève (0 >= note <= 20) et arrondi à 2 après la virgule
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

    /**
     * @return string Nom du formulaire
     */
    public function getName() {
        return 'teacher_edit_grade';
    }
}