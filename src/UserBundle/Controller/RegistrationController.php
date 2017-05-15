<?php

namespace UserBundle\Controller;

use UserBundle\Entity\User;
use UserBundle\Form\UserSetPasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class RegistrationController
 * Permet à un utilisateur de finaliser son compte et de l'activer
 *
 * @package UserBundle\Controller
 */
class RegistrationController extends Controller
{
    /**
     * Affiche le formulaire de finalisation de compte en fonction du token en paramètre
     *
     * @Route("/account/register/{activationToken}", name="user_registration")
     * @Method({"GET","POST"})
     */
    public function userRegistrationAction(Request $request, User $user)
    {
        //Vérifie si l'utilisateur n'a pas déjà activé son compte
        if($user->isEnabled()) {
            throw $this->createNotFoundException("Page non trouvée");
        }

        //Création du formulaire
        $form = $this->createForm(UserSetPasswordType::class);

        //Récupération de la requête et vérifie si le formulaire est validé
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            //Récupération du service UserManager
            $userService = $this->get('app.user_manager');
            
            //Récupération du mot de passe défini dans le formulaire
            $password = $form->getData()['plainPassword'];

            //Mise à jour de la clé privé de l'utilisateur avec le mot de passe défini
            $userService->updatePrivateKeyPassword($user, null, $password);

            //Sauvegatrde u mot de passe et activation du compte
            $user->setPlainPassword($password);
            $user->setEnabled(true);
            $user->setActivationToken(null);
            $um = $this->get('fos_user.user_manager');
            $um->updateUser($user);

            //Redirection vers la page de connexion avec un message de succès
            $this->addFlash('success', 'Votre compte à bien été finalisé, veuillez vous connecter.');
            return $this->redirectToRoute('fos_user_security_login');
        }

        //Affichage du formulaire de finalisation du compte
        return $this->render('UserBundle:Registration:setPassword.html.twig', array(
            'form' => $form->createView(),
        ));
    }


}