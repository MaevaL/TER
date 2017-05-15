<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * Formulaire permettant de demander le mot de passe de l'utilisateur
 *
 * @package AppBundle\Form
 */
class PasswordSecurityType extends AbstractType
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
            //Champs mot de passe
            ->add('password', PasswordType::class, array(
                'label' => "Entrez votre mot de passe",
            ))
        ;
    }

    /**
     * @return string Nom du formulaire
     */
    public function getName() {
        return 'password_security';
    }
}