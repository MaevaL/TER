<?php

namespace UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\ChangePasswordController as cpc;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ChangePasswordController
 * Permet à un utilisateur de changer son mot de passe
 *
 * @package UserBundle\Controller
 */
class ChangePasswordController extends cpc
{
    /**
     * Change le mot de passe de l'utilisateur (Classe override du FOSUserBundle)
     *
     * @param Request $request
     * @return Response
     */
    public function changePasswordAction(Request $request)
    {
        //Récupération de l'utilisateur
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        /** @var $formFactory FactoryInterface */
        $formFactory = $this->get('fos_user.change_password.form.factory');

        //Création du formulaire avec les données de l'utilisateur
        $form = $formFactory->createForm();
        $form->setData($user);

        //Récupération de la requête et vérifie si le formulaire est validé
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $userManager UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_SUCCESS, $event);

            //Changement du mot de passe de la clé privée
            $userManagerService = $this->get('app.user_manager');
            $userManagerService->updatePrivateKeyPassword($user, $request->get("fos_user_change_password_form")["current_password"], $user->getPlainPassword());

            //Sauvegarde de l'utilisateur
            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('fos_user_profile_show');
                $response = new RedirectResponse($url);
            }

            $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
        }

        //Affichage du formulaire de changement de mot de passe
        return $this->render('@FOSUser/ChangePassword/change_password.html.twig', array(
            'form' => $form->createView(),
        ));
    }

}