<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sinner\Phpseclib\Crypt\Crypt_RSA;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Entity\User;

/**
 * Class AdminController
 * Fonctionnalités du super administrateur
 *
 * @package AppBundle\Controller
 * @Route("/admin")
 */
class AdminController extends Controller
{
    /**
     * Affiche la liste de toutes les demandes de nouveau mot de passe
     * (Les utilisateurs qui ont cliqué sur "Mot de passe oublié ?" et validé le formulaire)
     *
     * @Route("/passwordRequests", name="admin_password_requests")
     * @Method("GET")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function passwordRequestsAction(Request $request)
    {
        //Récupération du repository User
        $em = $this->getDoctrine()->getManager();
        $userRepository = $em->getRepository('UserBundle:User');

        //Récupération de toutes les demandes
        $users = $userRepository->findUsersPasswordRequest();

        return $this->render("AppBundle:Admin:passwordRequests.html.twig", array(
            'users' => $users,
        ));
    }

    /**
     * Génération d'un mot de passe pour un utilisateur choisi s'il a fait une demande de nouveau mot de passe
     *
     * @Route("/user/{id}/generateRandomPassword", name="admin_admin_generate_random_password")
     * @Method("GET")
     *
     * @param Request $request
     * @param User $user Utilisateur à qui on génère le mot de passe
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function userGenerateRandomAction(Request $request, User $user)
    {
        //Vérifie que l'utilisateur a fait une demande de nouveau mot de passe
        if($user->getPasswordRequestedAt() == null) {
            $this->addFlash('warning', "Cet utilisateur n'a pas demandé de nouveau mot de passe.");
        }

        //Génération aléatoire d'un mot de passe à 8 caractères
        $randomPassword = strtoupper(bin2hex(random_bytes(4)));

        //Récupération du user manager de FOSUser
        $userManager = $this->get('fos_user.user_manager');

        //Récupération de la clé privé RSA de l'utilisateur (super admin) en session
        $session = $this->get('session');
        $adminPrivateKey = $session->get('userPrivateKey');

        //Déchiffrement de la clé privé de l'utilisateur choisi
        $userPrivateKey = utf8_decode($user->getPrivateKeyAdmin());
        $crypt_rsa = new Crypt_RSA();
        $crypt_rsa->loadKey($adminPrivateKey);
        $userPrivateKey = $crypt_rsa->decrypt($userPrivateKey);

        //Définition du nouveau mot de passe à l'utilisateur
        $user->setPlainPassword($randomPassword);

        //Chiffrement et sauvegarde de la clé privée RSA avec le nouveau mot de passe
        $rsa = $this->get('app.rsa_key_manager');
        $userPrivateKey = utf8_encode($rsa->cryptByPassword($userPrivateKey, $user->getPlainPassword()));
        $user->setPrivateKey($userPrivateKey);

        //Suppresion de la demande de nouveau mot de passe
        $user->setPasswordRequestedAt(null);
        $user->setEnabled(true);

        //Envoi d'un mail à l'utilisateur contenant son nouveau mot de passe
        $mailerService = $this->get('app.mailer_service');
        $mailerService->sendPasswordMail($user, $randomPassword);

        //Sauvegarde de l'utilisateur
        $userManager->updateUser($user);

        //Redirection (avec message de succès)
        $this->addFlash('success', 'Un nouveau mot de passe a été généré pour cet utilisateur, un mail vient de lui être envoyé.');
        return $this->redirectToRoute('admin_password_requests');
    }
}
