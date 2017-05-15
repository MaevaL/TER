<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Formulaire permettant de créer/modifier une UE
 *
 * @package AppBundle\Form
 */
class UEType extends AbstractType
{
    /**
     * Construction du formulaire
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //Nom de l'ue
            ->add('name', TextType::class, array(
                'label' => "Nom de l'UE",
            ))
            //Nombre de crédits de l'UE
            ->add('credits', IntegerType::class, array(
                'label' => "Nombre de crédits de l'UE",
            ))
            //Promotion associée à l'UE
            ->add('promotion', EntityType::class, array(
                'label' => "Promotion liée à l'UE",
                'class' => 'AppBundle:Promotion',
                'choice_label' => 'name',
                'attr' => array(
                    'class' => 'chosen-select-promotion'
                ),
                'multiple' => false,
            ))
        ;
    }

    /**
     * Sélectionne le type d'entité UE
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\UE'
        ));
    }

    /**
     * @return string Nom du formulaire
     */
    public function getBlockPrefix() {
        return 'appbundle_ue';
    }


}
