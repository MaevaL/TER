<?php
namespace AppBundle\Service;

class UserManager
{

    public function __construct(RSAKeyManager $rsaManager)
    {
        $this->rsaManager = $rsaManager;
    }

    public function addUserToBDD($newUser)
    {
        $userManager = $this->get('fos_user.user_manager');
            //Création de l'étudiant


            $student = $userManager->createUser();
            $student->setEnabled(false);
            $student->setFirstname($newUser['firstname']);
            $student->setLastname($newUser['lastname']);
            $student->setNumEtu($newUser['numEtu']);
            $student->setEmail($newUser['email']);
            $student->setUsername($newUser['email']);
            $student->setPlainPassword(uniqid());
            $this->rsaManager->generateUserKeys($student);

            $userManager->updateUser($student);
            $this->sendEmail($student)
    }

    //TODO: envoi de mail pour finalisation du compte
    public function sendEmail($user)
    {

    }

}