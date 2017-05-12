<?php
namespace AppBundle\Service;

use AppBundle\Entity\Promotion;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManager;
use UserBundle\Entity\User;

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

    public function updatePrivateKeyPassword(User $user, $oldPassword, $newPassword)
    {
        $rsa = $this->rsaManager;

        $key = $rsa->decryptByPassword($user->getPrivateKey(),$oldPassword);
        $key = $rsa->cryptByPassword($key, $newPassword);

        $user->setPrivateKey($key);
    }

    public function addUserToBDD($newUser)
    {

        $foundEtu = $this->exist($newUser);
        if($foundEtu == null) {
            //Création de l'étudiant
            $student = $this->userManager->createUser();
            $student->setEnabled(false);
            $student->setFirstname($newUser['firstname']);
            $student->setLastname($newUser['lastname']);
            $student->setNumEtu($newUser['numEtu']);
            $student->setEmail($newUser['email']);
            $student->setUsername($newUser['email']);
            $student->setPlainPassword(uniqid());

            $promotionRepository = $this->entityManager->getRepository("AppBundle:Promotion");
            $promotion = $promotionRepository->findOneBy(array("code" => $newUser['idpromotion']));
            if ($promotion == null) {
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
            return $student;
        }
    }

    public function exist($newUser){
        //Verification de l'existance d'un étudiant
        //par numero etudiant
        $userRepository = $this->entityManager->getRepository('UserBundle:User');
        $foundEtu = $userRepository->findOneBy(array(
            'numEtu' => $newUser['numEtu'],
        ));

        //par email
        if($foundEtu == null) {
            $foundEtu = $userRepository->findOneBy(array(
                'email' => $newUser['email'],
            ));
        }

        return $foundEtu;
    }

//TODO: envoi de mail pour finalisation du compte
    public function sendEmail($user)
    {

    }

}