<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Entity\User;
use UserBundle\Form\UserSetPasswordType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class RegistrationController extends Controller
{
    /**
     * Lists all user entities.
     *
     * @Route("/account/register/{activationToken}", name="user_registration")
     * @Method({"GET","POST"})
     */
    public function userRegistrationAction(Request $request, User $user)
    {
        if($user->isEnabled()){
            throw $this->createNotFoundException("Page non trouvée");
        }
        $form = $this->createForm(UserSetPasswordType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $userService = $this->get('app.user_manager');
            $password = $form->getData()['plainPassword'];
            $userService->updatePrivateKeyPassword($user, null, $password);
            $user->setPlainPassword($password);
            $user->setEnabled(true);
            $user->setActivationToken(null);
            $um = $this->get('fos_user.user_manager');
            $um->updateUser($user);

            $this->addFlash('success', 'Votre compte à bien été finalisé, veuillez vous connecter.');
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('UserBundle:Registration:setPassword.html.twig', array(
            'form' => $form->createView(),
        ));
    }


}