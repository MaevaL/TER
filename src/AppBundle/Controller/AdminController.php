<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class AdminController extends Controller
{
    /**
     * @Route("/passwordRequests", name="admin_password_requests")
     * @Method("GET")
     */
    public function passwordRequestsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userRepository = $em->getRepository('UserBundle:User');

        $users = $userRepository->findUsersPasswordRequest();

        return $this->render("AppBundle:Admin:passwordRequests.html.twig", array(
            'users' => $users,
        ));
    }
}
