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
    private $mailerService;

    public function __construct(RSAKeyManager $rsaManager, EntityManager $entityManager, $userManager, MailerService $mailerService)
    {
        $this->rsaManager = $rsaManager;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
        $this->mailerService = $mailerService;
    }

    public function updatePrivateKeyPassword(User $user, $oldPassword, $newPassword)
    {
        $rsa = $this->rsaManager;
        if($oldPassword != null)
            $key = $rsa->decryptByPassword($user->getPrivateKey(),$oldPassword);
        else
            $key = $user->getPrivateKey();
        $key = $rsa->cryptByPassword($key, $newPassword);

        $user->setPrivateKey($key);
    }

    public function addStudentToBDD($newUser)
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


            $this->rsaManager->generateUserKeys($student);

            $this->userManager->updateUser($student);
            $this->entityManager->flush();
            $this->sendEmail($student);
            return $student;
        }
    }

    public function addProfToBDD($newUser)
    {

        //Création de l'enseignant
        $prof = $this->userManager->createUser();
        $prof->setEnabled(false);
        $prof->setFirstname($newUser['firstname']);
        $prof->setLastname($newUser['lastname']);
        $prof->setEmail($newUser['email']);
        $prof->setUsername($newUser['email']);
        $prof->setPlainPassword(uniqid());
        $prof->addRole("ROLE_ADMIN");

        //TODO: gerer les UE affilié au profs

        $this->rsaManager->generateUserKeys($prof);

        $this->userManager->updateUser($prof);
        $this->entityManager->flush();
        $this->sendEmail($prof);
        return $prof;

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

    public function sendEmail(User $user)
    {
        $this->mailerService->sendActivation($user);
    }


}