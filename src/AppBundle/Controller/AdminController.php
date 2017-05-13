<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sinner\Phpseclib\Crypt\Crypt_RSA;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Entity\User;

/**
 * @Route("/admin")
 */
class AdminController extends Controller
{
    /**
     * @Route("/passwordRequests", name="admin_password_requests")
     * @Method("GET")
     */
    public function passwordRequestsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userRepository = $em->getRepository('UserBundle:User');

        $users = $userRepository->findUsersPasswordRequest();

        return $this->render("AppBundle:Admin:passwordRequests.html.twig", array(
            'users' => $users,
        ));
    }

    /**
     * @Route("/user/{id}/generateRandomPassword", name="admin_admin_generate_random_password")
     * @Method("GET")
     */
    public function userGenerateRandomAction(Request $request, User $user)
    {
        if($user->getPasswordRequestedAt() == null) {
            $this->addFlash('warning', "Cet utilisateur n'a pas demandé de nouveau mot de passe.");
        }

        $randomPassword = strtoupper(bin2hex(random_bytes(4)));
        $userManager = $this->get('fos_user.user_manager');

        $session = $this->get('session');
        $adminPrivateKey = $session->get('userPrivateKey');

        $userPrivateKey = utf8_decode($user->getPrivateKeyAdmin());
        $crypt_rsa = new Crypt_RSA();
        $crypt_rsa->loadKey($adminPrivateKey);
        $userPrivateKey = $crypt_rsa->decrypt($userPrivateKey);

        $user->setPlainPassword($randomPassword);

        $rsa = $this->get('app.rsa_key_manager');
        $userPrivateKey = utf8_encode($rsa->cryptByPassword($userPrivateKey, $user->getPlainPassword()));

        $user->setPrivateKey($userPrivateKey);
        $user->setPasswordRequestedAt(null);
        $user->setEnabled(true);

        $mailerService = $this->get('app.mailer_service');
        $mailerService->sendPasswordMail($user, $randomPassword);

        $userManager->updateUser($user);

        $this->addFlash('success', 'Un nouveau mot de passe a été généré pour cet utilisateur, un mail vient de lui être envoyé.');
        return $this->redirectToRoute('admin_password_requests');
    }
}
