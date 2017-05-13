<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Grade;

class PanelController extends Controller
{
    /**
     * @Route("/panel", name="app_panel")
     */
    public function panelAction(Request $request)
    {
        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        $em = $this->getDoctrine()->getManager();
        $gradeRepository = $em->getRepository('AppBundle:Grade');

        $userGrades = $gradeRepository->findBy(array(
            'student' => $this->getUser()
        ));

        $rsaManager = $this->get('app.rsa_key_manager');

        $readableGrades = array();
        foreach ($userGrades as $grade) {
            $gradeText = $rsaManager->decryptGradeStudent($userPrivateKey, $grade);

            $readableGrades[] = array(
                'intitule' => $grade->getGradeGroup()->getName(),
                'ue' => $grade->getGradeGroup()->getUe(),
                'date' => $grade->getGradeGroup()->getDate(),
                'grade' => $gradeText,
            );
        }


        return $this->render("AppBundle:Panel:index.html.twig", array(
            'grades' => $readableGrades,
        ));
    }
}
