<?php

namespace AppBundle\Controller;

use AppBundle\Form\GradeFileType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/panel/teacher")
 */
class TeacherController extends Controller
{
    /**
     * @Route("/", name="teacher_panel")
     */
    public function indexAction(Request $request)
    {
        return $this->render("AppBundle:Teacher:index.html.twig");
    }

    /**
     * @Route("/addGradeFile", name="teacher_panel_add_grade_file")
     */
    public function addGradeFileAction(Request $request)
    {
        $form = $this->createForm(GradeFileType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            var_dump("formulaire validé");

            $this->addFlash('success', 'La liste de notes a bien été ajoutée.');
            return $this->redirectToRoute('teacher_panel');
        }

        return $this->render("AppBundle:Teacher:addGradeFile.html.twig", array(
            'form' => $form->createView(),
        ));
    }
}
