<?php

namespace AppBundle\Controller;

use AppBundle\Entity\UE;
use AppBundle\Entity\Grade;
use AppBundle\Entity\GradeGroup;
use AppBundle\Form\GradeFileType;
use AppBundle\Form\TeacherEditGradeType;
use AppBundle\Form\PasswordSecurityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class TeacherController
 * Fonctionnalités d'un professeur
 *
 * @package AppBundle\Controller
 *
 * @Route("/panel/teacher")
 */
class TeacherController extends Controller
{
    /**
     * Accueil de la gestion des notes d'un professeur
     * Affiche les UEs auquelles il est associé avec leurs statistiques associées
     *
     * @Route("/", name="teacher_panel")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        //Réupération de l'utilisateur courant (professeur
        $user = $this->getUser();

        //Récupération de la clé privé de l'utilisateur courant
        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        //Récupération du service de clés RSA
        $rsaManager = $this->get('app.rsa_key_manager');

        //Récupération du repository d'UEs
        $em = $this->getDoctrine()->getManager();
        $ueRepository = $em->getRepository('AppBundle:UE');

        //Récupération des UEs de l'utilisateur courant et calcul de leurs statistiques
        $uesDisplay = array();

        //Parcours des ues
        foreach ($user->getUes() as $ue) {
            //Recherche des notes de l'UE
            $grades = $ueRepository->findGradesUE($ue);

            //Valeurs par défaut
            $ueResults = array(
                'ue' => $ue,
                'positive' => 0,
                'totalGrades' => 0,
                'percent' => 0,
            );

            //Calcul du poucentage de notes positives et négatives
            foreach ($grades as $grade) {
                $gradefloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $grade));
                if($gradefloat >= 10)
                    $ueResults['positive']++;

                $ueResults['totalGrades']++;
            }

            if($ueResults['totalGrades'] > 0)
                $ueResults['percent'] = ($ueResults['positive'] / $ueResults['totalGrades']) * 100;

            //Sauvegarde du pourcentage de l'ue
            $uesDisplay[] = $ueResults;
        }

        //Affichage des données
        return $this->render("AppBundle:Teacher:index.html.twig", array(
            'uesDisplay' => $uesDisplay,
        ));
    }

    /**
     * Affichage des Groupes de Notes (Ensembles de notes, par exemple un partiel) avec leurs statistiques associées
     *
     * @Route("/ue/{id}", name="teacher_panel_view_ue")
     * @Method("GET")
     *
     * @param Request $request
     * @param UE $ue UE dont on affiche les groupes de notes
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewUEAction(Request $request, UE $ue)
    {
        //Récupération de l'utilisateur courant (professeur) et de ses UEs
        $user = $this->getUser();
        $teacherUes = $user->getUes();

        //L'UE n'est pas gérée pas le prof courant => ERREUR
        if(!($teacherUes->contains($ue)))
            throw $this->createAccessDeniedException();

        //Récupération de la clé privé de l'utilisateur courant
        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        //Récupération du service de clés RSA
        $rsaManager = $this->get('app.rsa_key_manager');

        //Récupération du repository de groupe de notes
        $em = $this->getDoctrine()->getManager();
        $gradeGroupRepository = $em->getRepository('AppBundle:GradeGroup');

        //Récupération des groupes de notes de l'UE demandée
        $gradeGroups = $gradeGroupRepository->findBy(array(
            'teacher' => $user,
            'ue' => $ue,
        ));

        //Calcul des statistiques des groupes de notes
        $gradeGroupsDisplay = array();
        foreach ($gradeGroups as $gg) {
            //Récupération des notes des groupes de notes
            $grades = $gradeGroupRepository->findGradesGradeGroup($gg);

            //Valeurs par défaut + ajout du formulaire permettant de supprimer un groupe de note
            $gradeGroupResults = array(
                'gradeGroup' => $gg,
                'deleteForm' => $this->createDeleteGradeGroupForm($gg)->createView(),
                'positive' => 0,
                'totalGrades' => 0,
                'percent' => 0,
            );

            //Calcul du poucentage de notes positives et négatives
            foreach ($grades as $grade) {
                $gradefloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $grade));
                if($gradefloat >= 10)
                    $gradeGroupResults['positive']++;

                $gradeGroupResults['totalGrades']++;
            }

            if($gradeGroupResults['totalGrades'] > 0)
                $gradeGroupResults['percent'] = ($gradeGroupResults['positive'] / $gradeGroupResults['totalGrades']) * 100;

            //Sauvegarde du pourcentage du groupe de notes
            $gradeGroupsDisplay[] = $gradeGroupResults;
        }

        //Affichage des données
        return $this->render("AppBundle:Teacher:viewUE.html.twig", array(
            'ue' => $ue,
            'gradeGroupsDisplay' => $gradeGroupsDisplay,
        ));
    }

    /**
     * Affichage de la liste des notes d'un groupe de notes
     *
     * @Route("/gradeGroup/{id}", name="teacher_panel_view_grade_group")
     * @Method("GET")
     *
     * @param Request $request
     * @param GradeGroup $gradeGroup Groupe de notes à afficher
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewGradeGroupAction(Request $request, GradeGroup $gradeGroup)
    {
        //Récupération de l'utilisateur courant (professeur) et de ses UEs
        $user = $this->getUser();
        $teacherUes = $user->getUes();

        //L'UE n'est pas gérée pas le prof courant => ERREUR
        if(!($teacherUes->contains($gradeGroup->getUe())))
            throw $this->createAccessDeniedException();

        //Récupération de la clé privé de l'utilisateur courant
        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        //Récupération du service de clés RSA
        $rsaManager = $this->get('app.rsa_key_manager');

        //Récupération du repository de groupe de notes
        $em = $this->getDoctrine()->getManager();
        $gradeGroupRepository = $em->getRepository('AppBundle:GradeGroup');

        //Récupération des notes du groupe de notes
        $gradesResult = $gradeGroupRepository->findGradesGradeGroup($gradeGroup);
        $gradesDisplay = array();
        foreach ($gradesResult as $gradeElement) {
            //Déchiffrement de la note
            $gradefloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $gradeElement));

            //Sauvegarde de la données déchiffrée avec son formulaire de suppression
            $grade = array(
                'grade' => $gradeElement,
                'gradeFloat' => $gradefloat,
                'deleteForm' => $this->createDeleteGradeForm($gradeElement)->createView(),
            );
            $gradesDisplay[] = $grade;
        }

        //Affichage de la liste des notes
        return $this->render("AppBundle:Teacher:viewGradeGroup.html.twig", array(
            'gradeGroup' => $gradeGroup,
            'gradesDisplay' => $gradesDisplay,
        ));
    }

    /**
     * Suppression d'une note
     *
     * @Route("grade/{id}", name="grade_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param Grade $grade Note à supprimer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteGradeAction(Request $request, Grade $grade)
    {
        //Création du formulaire de suppression de la note et récupération de la requête
        $form = $this->createDeleteGradeForm($grade);
        $form->handleRequest($request);
        $gradeGroup = $grade->getGradeGroup();
        if ($form->isSubmitted() && $form->isValid()) {
            //Suppression de la note
            $em = $this->getDoctrine()->getManager();
            $em->remove($grade);
            $em->flush();
        }

        //Redirection vers le groupe de notes avec un message de succès
        $this->addFlash('success', "La note a bien été supprimée !");
        return $this->redirectToRoute('teacher_panel_view_grade_group', array(
            'id' => $gradeGroup->getId(),
        ));
    }

    /**
     * Créé le formmulaire de suppression d'une note
     *
     * @param Grade $grade Promotion à laquelle on créé le formulaire
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteGradeForm(Grade $grade)
    {
        //Création et renvoi du formulaire
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('grade_delete', array('id' => $grade->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * Supprime le groupe de notes choisi
     *
     * @Route("gradeGroup/{id}", name="grade_group_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param GradeGroup $gradeGroup Groupe de notes à supprimer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteGradeGroupAction(Request $request, GradeGroup $gradeGroup)
    {
        //Création du formulaire de suppression de la note et récupération de la requête
        $form = $this->createDeleteGradeGroupForm($gradeGroup);
        $form->handleRequest($request);
        $ue = $gradeGroup->getUe();
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($gradeGroup);
            $em->flush();
        }

        //Redirection vers l'UE avec un message de succès
        $this->addFlash('success', "Le groupe de notes a bien été supprimée !");
        return $this->redirectToRoute('teacher_panel_view_ue', array(
            'id' => $ue->getId(),
        ));
    }

    /**
     * Créé le formmulaire de suppression d'un groupe de notes
     *
     * @param GradeGroup $gradeGroup Groupe de notes auquel on créé le formulaire
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteGradeGroupForm(GradeGroup $gradeGroup)
    {
        //Création et renvoi du formulaire
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('grade_group_delete', array('id' => $gradeGroup->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * Edition d'une note par un professeur
     *
     * @Route("/grade/edit/{id}", name="teacher_panel_grade_edit")
     * @Method("GET")
     *
     * @param Request $request
     * @param Grade $grade Note à modifier
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function teacherEditGradeAction(Request $request, Grade $grade)
    {
        //Récupération de l'utilisateur courant et de ses UEs associées
        $user = $this->getUser();
        $teacherUes = $user->getUes();

        //L'UE n'est pas gérée pas le prof courant
        if(!($teacherUes->contains($grade->getGradeGroup()->getUe())))
            throw $this->createAccessDeniedException();

        //Récupération de la clé privé de l'utilisateur courant
        $session = $this->get('session');
        $userPrivateKey = $session->get('userPrivateKey');

        //Récupération du service de clés RSA
        $rsaManager = $this->get('app.rsa_key_manager');

        //Déchifrement de la note et cast en Float de celle-ci
        $gradeFloat = floatval($rsaManager->decryptGradeTeacher($userPrivateKey, $grade));

        //Création du formulaire d'édition de la note et récupération de la requête
        $form = $this->createForm(TeacherEditGradeType::class, null, ['gradeFloat' => $gradeFloat]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            //Récupération de la nouvelle valeure de la note
            $newGradeFloat = $form->getData()['gradeFloat'];

            //Cryptage de la nouvelle note
            $grades = $rsaManager->cryptStudentGrade($grade->getStudent(), $this->getUser()->getPublicKey(), $userPrivateKey, $newGradeFloat);

            //Sauvegarde de la valeure cryptée de la note
            $grade->setGrade($grades['grade']);
            $grade->setGradeTeacher($grades['gradeTeacher']);

            //Sauvegarde en base de données
            $em = $this->getDoctrine()->getManager();
            $em->persist($grade);
            $em->flush();

            //Redirection vers le groupe de note de la note éditée avec un message de succès
            $this->addFlash('success', 'La note a bien été modifiée.');
            return $this->redirectToRoute('teacher_panel_view_grade_group', array(
                'id' => $grade->getGradeGroup()->getId(),
            ));
        }

        //Affichage du formulaire d'édition
        return $this->render("AppBundle:Teacher:editGrade.html.twig", array(
            'form' => $form->createView(),
            'grade' => $grade,
        ));
    }

    /**
     * Formulaire permettant à un professeur d'uploader un fichier de notes
     *
     * @Route("/addGradeFile", name="teacher_panel_add_grade_file")
     * @Method({"GET","POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addGradeFileAction(Request $request)
    {
        //Récupération de la session courante
        $session = $this->get('session');

        //Création du formulaire d'upload
        $form = $this->createForm(GradeFileType::class, null, ['user' => $this->getUser()]);
        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            //Récupération du fichier
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

            //Recherche des étudiants listés dans le fichier
            $notFounded = array();
            $toAdd = array();
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
                else
                    $notFounded[] = $student;
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

        //Affichage du formulaire d'upload de notes
        return $this->render("AppBundle:Teacher:addGradeFile.html.twig", array(
            'form' => $form->createView(),
        ));
    }


    /**
     * Etape qui suit l'upload d'un fichier de notes par un professeur
     * Affiche un récapitulatif des notes qui vont être ajoutées et des nouveaux étudiants qui vont êtres créés
     * Puis demande une confirmation du mot de passe avant d'effectuer toutes les opérations
     *
     * @Route("/addGradeFile/validate", name="teacher_panel_add_grade_file_validate")
     * @Method({"GET","POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addGradeFileValidateAction(Request $request)
    {
        //Récupération des données de l'analyse de fichier
        $session = $this->get('session');
        $toAdd = $session->get('toAdd');
        $notFounded = $session->get('notFounded');

        //Si aucune donnée retour au formulaire
        if(!is_array($toAdd) || !is_array($notFounded) && ($toAdd != null || $notFounded != null)) {
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

                //Récupération du gestionaire RSA et du repository Utilisateur
                $rsaManager = $this->get('app.rsa_key_manager');
                $em = $this->getDoctrine()->getManager();
                $userRepository = $em->getRepository('UserBundle:User');

                //Récupération du repository des UEs
                $ueRepository = $em->getRepository('AppBundle:UE');
                $ue = $ueRepository->find($session->get('ueId'));

                //Création du groupe de note
                $gradeGroup = new GradeGroup();
                $gradeGroup->setTeacher($this->getUser());
                $gradeGroup->setName($session->get('intitule'));
                $gradeGroup->setUe($ue);

                //Sauvegarde du groupe de note
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
                    $student = $userService->addStudentToBDD($newUser);

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

        //Affichage du récapitulatif des opérations
        return $this->render("AppBundle:Teacher:addGradeFileValidate.html.twig", array(
            'form' => $formPassword->createView(),
        ));
    }

    /**
     * Etape qui suit l'affichage du récapitulatif des opérations et de la sauvegarde
     * Affiche un rapport des opérations qui ont été effectuées
     *
     * @Route("/addGradeFile/report", name="teacher_panel_add_grade_file_report")
     * @Method("GET")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
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
