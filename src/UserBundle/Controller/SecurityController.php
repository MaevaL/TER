<?php

namespace UserBundle\Controller;
use FOS\UserBundle\Controller\SecurityController as BaseController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends BaseController {

    public function loginAction(Request $request) {
        $auth_checker = $this->get('security.authorization_checker');

        if ($auth_checker->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->generateUrl('app_panel'), 307);
        }

        return parent::loginAction($request);
    }
}