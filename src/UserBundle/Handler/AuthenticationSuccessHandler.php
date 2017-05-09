<?php
namespace UserBundle\Handler;

use AppBundle\Service\RSAKeyManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface {

    /**
     * @var Router
     */
    protected $router;

    /**
     * Initialize authentication
     *
     * @param Router $router Router
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $password = $request->get('_password');

        $rsaManager = new RSAKeyManager();
        $privateKey = $rsaManager->getUserPrivateKey($user, $password);

        $session = $request->getSession();
        $session->set('userPrivateKey', $privateKey);

        return new RedirectResponse($this->router->generate('app_panel'));
    }


}