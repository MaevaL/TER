<?php

namespace UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Sinner\Phpseclib\Crypt\Crypt_RSA;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use UserBundle\Form\UserEditPasswordType;

class RegistrationController extends BaseController
{
    public function registerAction(Request $request)
    {
        $auth_checker = $this->get('security.authorization_checker');

        if ($auth_checker->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->generateUrl('app_panel'), 307);
        }

        /** @var $formFactory FactoryInterface */
        //$formFactory = $this->get('fos_user.registration.form.factory');
        /** @var $userManager UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->createForm('UserBundle\Form\UserRegistrationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                $user->setUsername($user->getEmail());

                //Création des clés RSA
                $rsaKeyManager = $this->get('app.rsa_key_manager');
                $rsaKeyManager->generateUserKeys($user);

                $userManager->updateUser($user);

                if (null === $response = $event->getResponse()) {
                    $url = $this->generateUrl('fos_user_registration_confirmed');
                    $response = new RedirectResponse($url);
                }

                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        return $this->render('UserBundle:Registration:register.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Lists all user entities.
     *
     * @Route("/account/register/{email}", name="user_registration")
     * @Method({"GET","POST"})
     */
    public function userRegistrationAction(Request $request, User $user)
    {
        if($user->isEnabled()){
            throw $this->createNotFoundException("Page non trouvée");
        }
        $form = $this->createForm(UserEditPasswordType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $userService = $this->get('app.user_manager');
            $password = $form->getData()->getPlainPassword();
            $userService->updatePrivateKeyPassword($user, null, $password);
            $user->setPlainPassword($password);
            $user->setEnabled(true);
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