<?php

namespace UserBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCreationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, array(
                'label' => 'Email',
            ))
            ->add('firstname', TextType::class, array(
                'label' => 'Prénom',
            ))
            ->add('lastname', TextType::class, array(
                'label' => 'Nom',
            ))
            ->add('numEtu', TextType::class, array(
                'label' => 'Numéro étudiant',
                'required' => false,
            ))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passes doivent être identiques.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Vérification du mot de passse'),
            ))
            ->add('roles', ChoiceType::class, array(
                'label' => "Rôle de l'utilisateur",
                'choices'   => array(
                    'Etudiant'   => 'ROLE_USER',
                    'Professeur'        => 'ROLE_ADMIN',
                    'Super Utilisateur'        => 'ROLE_SUPER_ADMIN',
                ),
                'multiple' => true,
            ))
            ->add('ues', EntityType::class, array(
                'label' => "UEs associées",
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
            'data_class' => 'UserBundle\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'userbundle_user';
    }


}