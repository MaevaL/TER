<?php

namespace UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\SecurityController as BaseController;

/**
 * Class SecurityController
 * Permet la connexion de l'utilisateur (Classe étendue du FOSUserBundle)
 * @package UserBundle\Controller
 */
class SecurityController extends BaseController {

    /**
     * Page de connexion
     *
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request) {
        //Si l'utilisateur est déjà connecté, redirection vers le panel
        $auth_checker = $this->get('security.authorization_checker');
        if ($auth_checker->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->generateUrl('app_panel'), 307);
        }

        //Traitement de la connexion pas le FOSUserBundle
        return parent::loginAction($request);
    }
}