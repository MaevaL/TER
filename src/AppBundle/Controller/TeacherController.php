<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Grade;
use AppBundle\Entity\GradeGroup;
use AppBundle\Entity\UE;
use AppBundle\Form\GradeFileType;
use AppBundle\Form\PasswordSecurityType;
use AppBundle\Form\TeacherEditGradeType;
use AppBundle\Service\RSAKeyManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sinner\Phpseclib\Crypt\Crypt_RSA;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

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
        $user = $this->getUser();

        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        $rsaManager = $this->get('app.rsa_key_manager');

        $em = $this->getDoctrine()->getManager();
        $ueRepository = $em->getRepository('AppBundle:UE');

        $uesDisplay = array();

        foreach ($user->getUes() as $ue) {
            $grades = $ueRepository->findGradesUE($ue);
            $ueResults = array(
                'ue' => $ue,
                'positive' => 0,
                'totalGrades' => 0,
                'percent' => 0,
            );

            foreach ($grades as $grade) {
                $gradefloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $grade));
                if($gradefloat >= 10)
                    $ueResults['positive']++;

                $ueResults['totalGrades']++;
            }

            if($ueResults['totalGrades'] > 0)
                $ueResults['percent'] = ($ueResults['positive'] / $ueResults['totalGrades']) * 100;

            $uesDisplay[] = $ueResults;
        }

        return $this->render("AppBundle:Teacher:index.html.twig", array(
            'uesDisplay' => $uesDisplay,
        ));
    }

    /**
     * @Route("/ue/{id}", name="teacher_panel_view_ue")
     * @Method("GET")
     */
    public function viewUEAction(Request $request, UE $ue)
    {
        $user = $this->getUser();

        $teacherUes = $user->getUes();

        //L'UE n'est pas gérée pas le prof courant
        if(!($teacherUes->contains($ue)))
            throw $this->createAccessDeniedException();

        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        $rsaManager = $this->get('app.rsa_key_manager');

        $em = $this->getDoctrine()->getManager();
        $gradeGroupRepository = $em->getRepository('AppBundle:GradeGroup');

        $gradeGroups = $gradeGroupRepository->findBy(array(
            'teacher' => $user,
            'ue' => $ue,
        ));

        $gradeGroupsDisplay = array();

        foreach ($gradeGroups as $gg) {
            $grades = $gradeGroupRepository->findGradesGradeGroup($gg);
            $gradeGroupResults = array(
                'gradeGroup' => $gg,
                'deleteForm' => $this->createDeleteGradeGroupForm($gg)->createView(),
                'positive' => 0,
                'totalGrades' => 0,
                'percent' => 0,
            );

            foreach ($grades as $grade) {
                $gradefloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $grade));
                if($gradefloat >= 10)
                    $gradeGroupResults['positive']++;

                $gradeGroupResults['totalGrades']++;
            }

            if($gradeGroupResults['totalGrades'] > 0)
                $gradeGroupResults['percent'] = ($gradeGroupResults['positive'] / $gradeGroupResults['totalGrades']) * 100;

            $gradeGroupsDisplay[] = $gradeGroupResults;
        }

        return $this->render("AppBundle:Teacher:viewUE.html.twig", array(
            'ue' => $ue,
            'gradeGroupsDisplay' => $gradeGroupsDisplay,
        ));
    }

    /**
     * @Route("/gradeGroup/{id}", name="teacher_panel_view_grade_group")
     * @Method("GET")
     */
    public function viewGradeGroupAction(Request $request, GradeGroup $gradeGroup)
    {
        $user = $this->getUser();

        $teacherUes = $user->getUes();

        //L'UE n'est pas gérée pas le prof courant
        if(!($teacherUes->contains($gradeGroup->getUe())))
            throw $this->createAccessDeniedException();

        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        $rsaManager = $this->get('app.rsa_key_manager');

        $em = $this->getDoctrine()->getManager();
        $gradeGroupRepository = $em->getRepository('AppBundle:GradeGroup');

        $gradesResult = $gradeGroupRepository->findGradesGradeGroup($gradeGroup);
        $gradesDisplay = array();
        foreach ($gradesResult as $gradeElement) {
            $gradefloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $gradeElement));
            $grade = array(
                'grade' => $gradeElement,
                'gradeFloat' => $gradefloat,
                'deleteForm' => $this->createDeleteGradeForm($gradeElement)->createView(),
            );

            $gradesDisplay[] = $grade;
        }

        return $this->render("AppBundle:Teacher:viewGradeGroup.html.twig", array(
            'gradeGroup' => $gradeGroup,
            'gradesDisplay' => $gradesDisplay,
        ));
    }

    /**
     * Deletes a grade entity.
     *
     * @Route("grade/{id}", name="grade_delete")
     * @Method("DELETE")
     */
    public function deleteGradeAction(Request $request, Grade $grade)
    {
        $form = $this->createDeleteGradeForm($grade);
        $form->handleRequest($request);

        $gradeGroup = $grade->getGradeGroup();

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($grade);
            $em->flush();
        }

        $this->addFlash('success', "La note a bien été supprimée !");
        return $this->redirectToRoute('teacher_panel_view_grade_group', array(
            'id' => $gradeGroup->getId(),
        ));
    }

    /**
     * Creates a form to delete a grade entity.
     *
     * @param Grade $grade The grade entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteGradeForm(Grade $grade)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('grade_delete', array('id' => $grade->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * Deletes a grade group entity.
     *
     * @Route("gradeGroup/{id}", name="grade_group_delete")
     * @Method("DELETE")
     */
    public function deleteGradeGroupAction(Request $request, GradeGroup $gradeGroup)
    {
        $form = $this->createDeleteGradeGroupForm($gradeGroup);
        $form->handleRequest($request);

        $ue = $gradeGroup->getUe();

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($gradeGroup);
            $em->flush();
        }

        $this->addFlash('success', "Le groupe de notes a bien été supprimée !");
        return $this->redirectToRoute('teacher_panel_view_ue', array(
            'id' => $ue->getId(),
        ));
    }

    /**
     * Creates a form to delete a grade group entity.
     *
     * @param GradeGroup $gradeGroup The grade group entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteGradeGroupForm(GradeGroup $gradeGroup)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('grade_group_delete', array('id' => $gradeGroup->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/grade/edit/{id}", name="teacher_panel_grade_edit")
     */
    public function teacherEditGradeAction(Request $request, Grade $grade)
    {
        $user = $this->getUser();

        $teacherUes = $user->getUes();

        //L'UE n'est pas gérée pas le prof courant
        if(!($teacherUes->contains($grade->getGradeGroup()->getUe())))
            throw $this->createAccessDeniedException();

        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        $rsaManager = $this->get('app.rsa_key_manager');

        $gradeFloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $grade));

        $form = $this->createForm(TeacherEditGradeType::class, null, ['gradeFloat' => $gradeFloat]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $newGradeFloat = $form->getData()['gradeFloat'];

            $grades = $rsaManager->cryptStudentGrade($grade->getStudent(), $this->getUser()->getPublicKey(), $userPrivateKey, $newGradeFloat);

            $grade->setGrade($grades['grade']);
            $grade->setGradeTeacher($grades['gradeTeacher']);

            $em = $this->getDoctrine()->getManager();
            $em->persist($grade);

            $em->flush();

            $this->addFlash('success', 'La note a bien été modifiée.');
            return $this->redirectToRoute('teacher_panel_view_grade_group', array(
                'id' => $grade->getGradeGroup()->getId(),
            ));
        }

        return $this->render("AppBundle:Teacher:editGrade.html.twig", array(
            'form' => $form->createView(),
            'grade' => $grade,
        ));
    }

    /**
     * @Route("/addGradeFile", name="teacher_panel_add_grade_file")
     */
    public function addGradeFileAction(Request $request)
    {
        $session = $this->get('session');

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
            $data = $CSVToArray->convert($path."/".$filename, ',', array(
                'firstname',
                'lastname',
                'numEtu',
                'email',
                'grade',
            ));

            //Suppression du fichier après analyse
            unlink($path."/".$filename);

            $notFounded = array();
            $toAdd = array();

            //Recherche des étudiants listés dans le fichier
            $em = $this->getDoctrine()->getManager();
            $userRepository = $em->getRepository('UserBundle:User');
            $userService = $this->get("app.user_manager");
            foreach ($data as $student) {
                $foundEtu = $userService->exist($student);

                //L'étudiant est déjà dans la base
                if($foundEtu != null) {
                    $toAdd[] =  array(
                        'student' => $foundEtu,
                        'grade' => $student['grade'],
                    );
                }
                //L'étudiant n'est pas dans la base
                else {
                    $notFounded[] = $student;
                }
            }

            //Sauvegarde des résultats de la recherche en session pour la prochaine étape
            $session->set('toAdd', $toAdd);
            $session->set('notFounded', $notFounded);
            $session->set('intitule', $form->getData()['intitule']);
            $session->set('ueId', $form->getData()['ue']->getId());

            //Affichage du résultat de l'analyse du fichier
            return $this->render("AppBundle:Teacher:addGradeFilePreview.html.twig", array(
                'toAdd' => $toAdd,
                'notFounded' => $notFounded,
            ));
        } else {
            //Suppression d'une éventuelle précedente session d'ajout de notes non terminée
            $session->remove('toAdd');
            $session->remove('notFounded');
            $session->remove('intitule');
            $session->remove('ueId');
        }

        //Affichage du formaulaire d'upload de notes
        return $this->render("AppBundle:Teacher:addGradeFile.html.twig", array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/addGradeFile/validate", name="teacher_panel_add_grade_file_validate")
     */
    public function addGradeFileValidateAction(Request $request)
    {
        //Récupération des données de l'analyse de fichier
        $session = $this->get('session');
        $toAdd = $session->get('toAdd');
        $notFounded = $session->get('notFounded');

        //Si aucune donnée retour au formulaire
        if(!is_array($toAdd) || !is_array($notFounded)) {
            $this->addFlash('warning', "Une erreur est survenue lors de l'enregistrement des données.");
            return $this->redirectToRoute('teacher_panel_add_grade_file');
        }

        //Créaton du formulaire de demande de mot de passe
        $formPassword = $this->createForm(PasswordSecurityType::class);
        $formPassword->handleRequest($request);
        if($formPassword->isSubmitted() && $formPassword->isValid()) {
            //Vérifie que le mot de passe est le bon
            $encoder_service = $this->get('security.encoder_factory');
            $encoder = $encoder_service->getEncoder($this->getUser());
            if ($encoder->isPasswordValid($this->getUser()->getPassword(), $formPassword->getData()['password'], $this->getUser()->getSalt())) {

                //Récupération du gestionaire RSA et du repo Utilisateur
                $rsaManager = $this->get('app.rsa_key_manager');
                $em = $this->getDoctrine()->getManager();
                $userRepository = $em->getRepository('UserBundle:User');

                $ueRepository = $em->getRepository('AppBundle:UE');
                $ue = $ueRepository->find($session->get('ueId'));

                //Création du groupe de note
                $gradeGroup = new GradeGroup();
                $gradeGroup->setTeacher($this->getUser());
                $gradeGroup->setName($session->get('intitule'));
                $gradeGroup->setUe($ue);

                $em->persist($gradeGroup);

                //Sauvegarde des notes dé étudiants déjà en base de données
                foreach ($toAdd as $element) {
                    $student = $userRepository->find($element['student']->getId());

                    if($student != null) {
                        $privateKey = $rsaManager->getUserPrivateKey($this->getUser(), $formPassword->getData()['password']);

                        $grades = $rsaManager->cryptStudentGrade($student, $this->getUser()->getPublicKey(), $privateKey, $element['grade']);

                        $grade = new Grade();
                        $grade->setTeacher($this->getUser());
                        $grade->setStudent($student);
                        $grade->setGrade($grades['grade']);
                        $grade->setGradeTeacher($grades['gradeTeacher']);
                        $grade->setGradeGroup($gradeGroup);

                        $em->persist($grade);
                    }
                }

                //Création des nouveaux étudiants et ajout de leur note
                $userManager = $this->get('fos_user.user_manager');
                $userService = $this->get("app.user_manager");
                foreach ($notFounded as $newUser) {
                    //Création de l'étudiant
                    $newUser['idpromotion'] = $gradeGroup->getUe()->getPromotion()->getCode();
                    $student = $userService->addUserToBDD($newUser);

                    //Récupération des clés RSA et sauvegarde de la note
                    $privateKey = $rsaManager->getUserPrivateKey($this->getUser(), $formPassword->getData()['password']);
                    $grades = $rsaManager->cryptStudentGrade($student, $this->getUser()->getPublicKey(), $privateKey, $element['grade']);

                    $grade = new Grade();
                    $grade->setTeacher($this->getUser());
                    $grade->setStudent($student);
                    $grade->setGrade($grades['grade']);
                    $grade->setGradeTeacher($grades['gradeTeacher']);
                    $grade->setGradeGroup($gradeGroup);

                    $em->persist($grade);
                }

                //Sauvegarde
                $em->flush();

                //Affichage du rapport de fin d'upload
                return $this->redirectToRoute('teacher_panel_add_grade_file_report');

            } else {
                $this->addFlash('warning', 'Mot de passe incorrect.');
            }
        }

        return $this->render("AppBundle:Teacher:addGradeFileValidate.html.twig", array(
            'form' => $formPassword->createView(),
        ));
    }

    /**
     * @Route("/addGradeFile/report", name="teacher_panel_add_grade_file_report")
     */
    public function addGradeFileReportAction(Request $request)
    {
        //Récupération des données de l'upload
        $session = $this->get('session');
        $toAdd = $session->get('toAdd');
        $notFounded = $session->get('notFounded');

        //Suppression des données en session pour finir la session d'ajout
        $session->remove('toAdd');
        $session->remove('notFounded');
        $session->remove('intitule');
        $session->remove('ueId');

        //Si aucune donnée retour à l'upload
        if(!is_array($toAdd) || !is_array($notFounded)) {
            return $this->redirectToRoute('teacher_panel_add_grade_file');
        }

        //Affichage du rapport
        $this->addFlash('success', "Le fichier de note a bien été importé");
        return $this->render("AppBundle:Teacher:addGradeFileReport.html.twig", array(
            'toAdd' => $toAdd,
            'notFounded' => $notFounded,
        ));
    }
}
