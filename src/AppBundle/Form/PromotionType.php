<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Formulaire permettant de créer/modifier une promotion
 *
 * @package AppBundle\Form
 */
class PromotionType extends AbstractType
{
    /**
     * Construction du formulaire
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            //Nom de la promotion
            ->add('name', TextType::class, array(
                'label' => "Nom de la promotion",
            ))
            //Code de la promotion
            ->add('code', TextType::class, array(
                'label' => "Code de la promotion",
            ))
        ;
    }

    /**
     * Sélectionne le type d'entité Promotion
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Promotion'
        ));
    }

    /**
     * @return string Nom du formulaire
     */
    public function getBlockPrefix() {
        return 'appbundle_promotion';
    }


}
