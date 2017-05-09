<?php
namespace AppBundle\Service;

use AppBundle\Entity\Promotion;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManager;

class UserManager
{
    private $rsaManager;
    private $entityManager;
    private $userManager;

    public function __construct(RSAKeyManager $rsaManager, EntityManager $entityManager, $userManager)
    {
        $this->rsaManager = $rsaManager;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
    }

    public function addUserToBDD($newUser)
    {
            //Création de l'étudiant
            $student = $this->userManager->createUser();
            $student->setEnabled(false);
            $student->setFirstname($newUser['firstname']);
            $student->setLastname($newUser['lastname']);
            $student->setNumEtu($newUser['numEtu']);
            $student->setEmail($newUser['email']);
            $student->setUsername($newUser['email']);

            $promotionRepository = $this->entityManager->getRepository("AppBundle:Promotion");
            $promotion = $promotionRepository->findOneBy(array("code" => $newUser['idpromotion']));
            if($promotion == null)
            {
                $promotion = new Promotion();
                $promotion->setName($newUser['nompromotion']);
                $promotion->setCode($newUser['idpromotion']);

                $slugify = new Slugify();
                $promotion->setSlug($slugify->slugify($promotion->getName()));

                $this->entityManager->persist($promotion);
            }

            $student->setPromotion($promotion);


            $student->setPlainPassword(uniqid());
            $this->rsaManager->generateUserKeys($student);

            $this->userManager->updateUser($student);
            $this->entityManager->flush();
            $this->sendEmail($student);
    }

    //TODO: envoi de mail pour finalisation du compte
    public function sendEmail($user)
    {

    }

}