<?php
namespace UserBundle\Handler;

use Symfony\Component\Routing\Router;
use AppBundle\Service\RSAKeyManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Handler qui permet de sauvegarder en session la clé privé RSA déchiffrée de l'utilisateur lors de sa connexion à l'application grâce à son mot de passe
 *
 * @package UserBundle\Handler
 */
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface {

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RSAKeyManager Service de gestion des clés RSA
     */
    private $RSAKeyManager;

    /**
     * AuthenticationSuccessHandler constructor.
     *
     * @param Router $router
     * @param RSAKeyManager $RSAKeyManager Service de gestion des clés RSA
     */
    public function __construct(Router $router, RSAKeyManager $RSAKeyManager)
    {
        $this->router = $router;
        $this->RSAKeyManager = $RSAKeyManager;
    }

    /**
     * Récupère la clé privée RSA de l'utilisateur qui se connecte et la déchiffre grâce à son mot de passe
     *
     * @param Request $request
     * @param TokenInterface $token Token d'authentification
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        //Récupération de l'utilisateur courant et de son mot de passe
        $user = $token->getUser();
        $password = $request->get('_password');

        //Récupération de la clé privée RSA et déchiffrement
        $privateKey = $this->RSAKeyManager->getUserPrivateKey($user, $password);

        //Sauvegarde en session de la clé
        $session = $request->getSession();
        $session->set('userPrivateKey', $privateKey);

        //Redirection vers le panel
        return new RedirectResponse($this->router->generate('app_panel'));
    }


}