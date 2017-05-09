<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => "Nom de la promotion",
            ))
            ->add('code', TextType::class, array(
                'label' => "Code de la promotion",
            ))
            ->add('ues', EntityType::class, array(
                'label' => "UEs de la promotion",
                'class' => 'AppBundle:UE',
                'choice_label' => 'name',
                'attr' => array(
                    'class' => 'chosen-select-ue'
                ),
                'multiple' => true,
            ))
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Promotion'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_promotion';
    }


}
