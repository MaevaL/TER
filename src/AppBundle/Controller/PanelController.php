<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PanelController
 * Fonctionnalité du panel (accueil d'un utilisateur connecté)
 *
 * @package AppBundle\Controller
 */
class PanelController extends Controller
{
    /**
     * Accueil d'un utilisateur connecté
     * Affiche les notes déchiffrées d'un étudiant avec possibilité de les filtrer
     *
     * @Route("/panel", name="app_panel")
     * @Method("GET")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function panelAction(Request $request)
    {
        //Récupération de la clé privé RSA de l'utilisateur en session
        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        //Récupération du repository des notes
        $em = $this->getDoctrine()->getManager();
        $gradeRepository = $em->getRepository('AppBundle:Grade');

        //Récupération des notes de l'utilisateur courant
        $userGrades = $gradeRepository->findBy(array(
            'student' => $this->getUser()
        ));

        //Récupération du service de clé RSA
        $rsaManager = $this->get('app.rsa_key_manager');

        //Déchiffrement des notes et organisation des données pour l'affichage
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
