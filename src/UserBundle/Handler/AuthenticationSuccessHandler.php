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
    private  $RSAKeyManager = null;

    /**
     * Initialize authentication
     *
     * @param Router $router Router
     *
     * @return void
     */
    public function __construct(Router $router, RSAKeyManager $RSAKeyManager)
    {
        $this->router = $router;
        $this->RSAKeyManager = $RSAKeyManager;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $password = $request->get('_password');

        $privateKey = $this->RSAKeyManager->getUserPrivateKey($user, $password);

        $session = $request->getSession();
        $session->set('userPrivateKey', $privateKey);

        return new RedirectResponse($this->router->generate('app_panel'));
    }


}