<?php

namespace UserBundle\EventListener;

use AppBundle\Service\RSAKeyManager;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class PasswordResettingListener implements EventSubscriberInterface {

    private $router;
    private $RSAKeyManager = null;
    private $currentUser;
    private $session;

    public function __construct(UrlGeneratorInterface $router, RSAKeyManager $RSAKeyManager, TokenStorage $context, Session $session)
    {
        $this->router = $router;
        $this->RSAKeyManager = $RSAKeyManager;
        $this->currentUser = $context->getToken()->getUser();
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::RESETTING_RESET_SUCCESS => 'onPasswordResettingSuccess',
        ];
    }

    public function onPasswordResettingSuccess(FormEvent $event)
    {
        $user = $event->getForm()->getData();
        $password = $user->getPlainPassword();

        $privateKey = $this->RSAKeyManager->getUserPrivateKey($user, $password);

        $this->session->set('userPrivateKey', $privateKey);

        return new RedirectResponse($this->router->generate('app_panel'));
    }
}