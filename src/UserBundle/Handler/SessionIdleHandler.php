<?php

namespace UserBundle\Handler;

use UserBundle\Entity\User;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Handler permettant de déconnecter automatiquement un utilisateur après un certain temps (sa session expire)
 *
 * @package UserBundle\Handler
 */
class SessionIdleHandler
{
    /**
     * @var SessionInterface Session de l'utilisateur courant
     */
    protected $session;
    /**
     * @var TokenStorageInterface Token d'authentification de l'utilisateur
     */
    protected $securityToken;
    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @var int Durée de la session (en secondes)
     */
    protected $maxIdleTime;

    /**
     * SessionIdleHandler constructor.
     *
     * @param SessionInterface $session Session de l'utilisateur courant
     * @param TokenStorageInterface $securityToken Token d'authentification de l'utilisateur
     * @param RouterInterface $router
     * @param int $maxIdleTime Durée de la session (en secondes)
     */
    public function __construct(SessionInterface $session, TokenStorageInterface $securityToken, RouterInterface $router, $maxIdleTime = 0)
    {
        $this->session = $session;
        $this->securityToken = $securityToken;
        $this->router = $router;
        $this->maxIdleTime = $maxIdleTime;
    }

    /**
     * Fonction qui vérifie si la session a expiré
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        //Récupération de l'utilisateur courant
        $user = $this->securityToken->getToken()->getUser();
        if (!$user instanceof User) {
            return;
        }

        //Vérifie si la session a expiré
        if ($this->maxIdleTime > 0) {
            $this->session->start();
            $lapse = time() - $this->session->getMetadataBag()->getLastUsed();

            //La session a expiré
            if ($lapse > $this->maxIdleTime) {
                //Déconnexion de l'utilisateur
                $this->securityToken->setToken(null);

                //Redirection ver la page de connexion avec un message
                $this->session->getFlashBag()->set('info', 'La session a expiré, veuillez vous reconnecter.');
                $event->setResponse(new RedirectResponse($this->router->generate('fos_user_security_login')));
            }
        }
    }

}