<?php

namespace AppBundle\Form;

use AppBundle\Validator\Constraints\CSV;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use UserBundle\Entity\User;

/**
 * Formulaire permettant d'uploader une liste de notes par un prof
 *
 * @package AppBundle\Form
 */
class GradeFileType extends AbstractType
{
    /**
     * @var User Utilisateur courant (professeur)
     */
    private $currentUser;

    /**
     * @param OptionsResolver $resolver Récupération de l'utilisateur courant passé en paramètre lors de la construction du formulaire
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults( [
            'user' => null,
        ] );
    }

    /**
     * Construction du formulaire
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //Récupération de l'utilisateur courant
        $this->currentUser = $options['user'];
        $builder
            //Champs fichier avec la contrainte CSV
            ->add('gradeFile', FileType::class, array(
                'label' => 'Fichier de notes (CSV)',
                'constraints' => array(new CSV())
            ))
            //Toutes les ues de l'utilisateur courant
            ->add('ue', EntityType::class, array(
                'label' => "Sélectionnez l'UE correspondante",
                'class' => "AppBundle:UE",
                'choices' => $this->currentUser->getUes(),
                'choice_label' => "name",
            ))
            //Texte de l'intitulé de la note
            ->add('intitule', TextType::class, array(
                'label' => "Intitulé de la note",
            ))
        ;
    }

    /**
     * @return string Nom du formulaire
     */
    public function getName() {
        return 'grade_file';
    }
}