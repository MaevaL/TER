<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AppController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $securityContext = $this->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('app_panel');
        }

        return $this->render("AppBundle:App:index.html.twig");
    }

    /**
     * @Route("/panel", name="app_panel")
     */
    public function panelAction(Request $request)
    {
        $pass = "test";
        $test = strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($pass), serialize($this->getUser()->getPrivateKey()), MCRYPT_MODE_CBC, md5(md5($pass)))), '+/=', '-_,');

        $test2 = unserialize(rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($pass), base64_decode(strtr($test, '-_,', '+/=')), MCRYPT_MODE_CBC, md5(md5($pass))), "\0"));
        //$test = "";
        return $this->render("AppBundle:Panel:index.html.twig", array(
            'user' => $this->getUser(),
            'test' => $test,
            'test2' => $test2,
        ));
    }
}
