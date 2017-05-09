<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Grade;
use AppBundle\Entity\GradeGroup;
use AppBundle\Form\GradeFileType;
use AppBundle\Form\PasswordSecurityType;
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
            foreach ($data as $student) {
                //Récupération depuis la BDD par Numéro étudiant
                $foundEtu = $userRepository->findOneBy(array(
                    'numEtu' => $student['numEtu'],
                ));


                //Sinon récupération depuis la BDD par email
                if($foundEtu == null) {
                    $foundEtu = $userRepository->findOneBy(array(
                        'email' => $student['email'],
                    ));
                }


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
                foreach ($notFounded as $newUser) {
                    //Création de l'étudiant
                    //TODO: envoi de mail pour finalisation du compte
                    $student = $userManager->createUser();
                    $student->setEnabled(false);
                    $student->setFirstname($newUser['firstname']);
                    $student->setLastname($newUser['lastname']);
                    $student->setNumEtu($newUser['numEtu']);
                    $student->setEmail($newUser['email']);
                    $student->setUsername($newUser['email']);
                    $student->setPlainPassword(uniqid());
                    $student->setPromotion($gradeGroup->getUe()->getPromotion());
                    $rsaManager->generateUserKeys($student);

                    $userManager->updateUser($student);

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
