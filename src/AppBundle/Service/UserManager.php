<?php
namespace AppBundle\Service;

use UserBundle\Entity\User;
use AppBundle\Entity\Promotion;
use Doctrine\ORM\EntityManager;

/**
 * Service qui se charge de certaines opération récurentes sur les utilisateurs en base de données
 *
 * @package AppBundle\Service
 */
class UserManager
{
    /**
     * @var RSAKeyManager Service de gestion des clés RSA
     */
    private $rsaManager;

    /**
     * @var EntityManager Entity manager
     */
    private $entityManager;

    /**
     * @var \FOS\UserBundle\Model\UserManager UserManager de FOS
     */
    private $userManager;

    /**
     * @var MailerService Service d'envoi de mails
     */
    private $mailerService;

    /**
     * UserManager constructor.
     *
     * @param RSAKeyManager $rsaManager
     * @param EntityManager $entityManager
     * @param \FOS\UserBundle\Model\UserManager $userManager
     * @param MailerService $mailerService
     */
    public function __construct(RSAKeyManager $rsaManager, EntityManager $entityManager, \FOS\UserBundle\Model\UserManager $userManager, MailerService $mailerService)
    {
        $this->rsaManager = $rsaManager;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
        $this->mailerService = $mailerService;
    }

    /**
     * Permet de mettre à jour la clé privée d'un utilisateur avec un nouveau mot de passe
     *
     * @param User $user Utilisateur à qui on met à jour la clé privée
     * @param $oldPassword string Ancien mot de passe de l'utilisateur
     * @param $newPassword string Nouveau mot de passe de l'utilisateur
     */
    public function updatePrivateKeyPassword(User $user, $oldPassword, $newPassword)
    {
        //Récupération du service de clés RSA et décryptage de la clé privée avec l'ancien mot de passe
        $rsa = $this->rsaManager;
        if($oldPassword != null)
            $key = $rsa->decryptByPassword($user->getPrivateKey(),$oldPassword);
        else
            $key = $user->getPrivateKey();

        //Cryptage avec le nouveau mot de passe et mise à jour de l'utilisateur
        $key = $rsa->cryptByPassword($key, $newPassword);
        $user->setPrivateKey($key);
    }

    /**
     * Ajoute un utilisateur (étudiant) en base de données à partie des données en paramètre
     *
     * @param $newUser array Données pour créer l'utilisateur
     * @return \FOS\UserBundle\Model\UserInterface|mixed Utilisateur créé
     */
    public function addStudentToBDD($newUser)
    {
        //Recherche si l'utilisateur existe déjà
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

            //Création de sa promotion si demmandé et qu'elle n'existe pas
            $promotionRepository = $this->entityManager->getRepository("AppBundle:Promotion");
            $promotion = $promotionRepository->findOneBy(array("code" => $newUser['idpromotion']));
            if ($promotion == null) {
                $promotion = new Promotion();
                $promotion->setName($newUser['nompromotion']);
                $promotion->setCode($newUser['idpromotion']);

                $this->entityManager->persist($promotion);
            }

            $student->setPromotion($promotion);

            //Génération des clés RSA de l'utilisateur
            $this->rsaManager->generateUserKeys($student);

            //Sauvegarde des données
            $this->userManager->updateUser($student);
            $this->entityManager->flush();

            //Envoi d'un mail d'activation
            $this->sendEmail($student);
            return $student;
        }
        return null;
    }

    /**
     * Ajoute un utilisateur (professeur) en base de données à partie des données en paramètre
     *
     * @param $newUser array Données pour créer l'utilisateur
     * @return \FOS\UserBundle\Model\UserInterface|mixed Utilisateur créé
     */
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

        //Génération des clés RSA de l'utilisateur
        $this->rsaManager->generateUserKeys($prof);

        //Sauvegarde des données
        $this->userManager->updateUser($prof);
        $this->entityManager->flush();

        //Envoi d'un mail d'activation
        $this->sendEmail($prof);
        return $prof;

    }

    /**
     * Fonction qui cherche si l'utilisateur en paramètre existe déjà
     * @param $newUser array Données à rechercher
     * @return null|object|User
     */
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

    /**
     * Envoi un mail d'activation a l'utlisateur en paramètre
     *
     * @param User $user Utilisateur ç qui envoyer le mail
     */
    public function sendEmail(User $user)
    {
        $this->mailerService->sendActivation($user);
    }


}