<?php

namespace AppBundle\Twig;

use Doctrine\ORM\EntityManager;
use Twig_Environment;

/**
 * Service Twig permettant de créer de nouvelles fonctions accessible depuis une vue Twig
 *
 * @package AppBundle\Twig
 */
class AppExtension extends \Twig_Extension
{
    /**
     * @var EntityManager Doctrine Entity Manager
     */
    protected $entityManager;

    /**
     * AppExtension constructor.
     *
     * @param EntityManager $entityManager Doctrine Entity Manager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return string Nom de l'extension Twig
     */
    public function getName()
    {
        return 'app_extension';
    }

    /**
     * @return array Listes des fonctions ajoutées à Twig
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('totalPasswordRequests', [$this, 'totalPasswordRequests'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renvoi le nombre de demande de nouveau mot de passe
     *
     * @return int Nombre de demandes
     */
    public function totalPasswordRequests()
    {
        //Récupération du Repository des utilisateurs
        $em = $this->entityManager;
        $repository = $em->getRepository('UserBundle:User');

        //Calcul du nombre de demandes
        $reports = $repository->findUsersPasswordRequest();
        $count = count($reports);

        return $count;
    }

    public function initRuntime(Twig_Environment $environment)
    {

    }

    public function getGlobals()
    {

    }


}