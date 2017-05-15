<?php
namespace AppBundle\Form;

use AppBundle\Validator\Constraints\CSV;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * Formulaire permettant d'uploader un fichier contenant une liste d'UEs
 * @package AppBundle\Form
 */
class ListUEType extends AbstractType
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
            //Champs fichier avec la contrainte CSV
            ->add('listuecsv', FileType::class, array(
                'label' => 'Fichier CSV',
                'constraints' => array(new CSV())
            ));
    }

    /**
     * @return string Nom du formulaire
     */
    public function getName() {
        return 'list_ue';
    }
}