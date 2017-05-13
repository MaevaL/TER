<?php

namespace AppBundle\Twig;

use Doctrine\ORM\EntityManager;

class AppExtension extends \Twig_Extension
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function getName()
    {
        return 'app_extension';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('totalPasswordRequests', [$this, 'totalPasswordRequests'], ['is_safe' => ['html']]),
        ];
    }

    public function totalPasswordRequests()
    {
        $em = $this->entityManager;

        $repository = $em->getRepository('UserBundle:User');

        $reports = $repository->findUsersPasswordRequest();
        $count = count($reports);

        return $count;
    }

}