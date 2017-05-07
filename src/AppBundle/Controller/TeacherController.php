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
        $form = $this->createForm(GradeFileType::class, null, ['user' => $this->getUser()]);

        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            $file = $form->getData()['gradeFile'];

            //Sauvegarde temporaire du fichier
            $filename = uniqid().".".$file->getClientOriginalExtension();
            $path = __DIR__.'/../../../web/upload';
            $file->move($path, $filename);

            //Analyse du fichier
            $CSVToArray = $this->get('app.csvtoarray');
            $data = $CSVToArray->convert($path."/".$filename, ',');

            //Suppression du fichier après analyse
            unlink($path."/".$filename);

            var_dump($data);


            $this->addFlash('success', 'La liste de notes a bien été ajoutée.');
            /*
            return $this->redirectToRoute('teacher_panel');
            */
        }

        return $this->render("AppBundle:Teacher:addGradeFile.html.twig", array(
            'form' => $form->createView(),
        ));
    }
}
