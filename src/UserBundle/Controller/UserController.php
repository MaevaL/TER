<?php

namespace UserBundle\Controller;

use Sinner\Phpseclib\Crypt\Crypt_RSA;
use UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;
use UserBundle\Form\UserCSVType;

/**
 * User controller.
 *
 * @Route("/admin/user")
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     * @Route("/", name="user_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        //Ne récupère pas le super admin
        $users = $em->getRepository('UserBundle:User')->findNonSuperAdmin();

        return $this->render('UserBundle:user:index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Upload students.
     *
     * @Route("/uploadStudentList", name="user_upload_student_list")
     * @Method({"GET", "POST"})
     */
    public function uploadStudentListAction(Request $request)
    {
        $form = $this->createForm(UserCSVType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            $file = $form->getData()['usercsv'];

            //Sauvegarde temporaire du fichier
            $filename = uniqid() . "." . $file->getClientOriginalExtension();
            $path = __DIR__ . '/../../../web/upload';
            $file->move($path, $filename);

            //Analyse du fichier
            $CSVToArray = $this->get('app.csvtoarray');
            $data = $CSVToArray->convert($path . "/" . $filename, ',', array(
                'lastname',
                'firstname',
                'numEtu',
                'idpromotion',
                'nompromotion',
                'groupe',
                'annee',
                'email'));

            //Suppression du fichier après analyse
            unlink($path . "/" . $filename);

            $userManager = $this->get("app.user_manager");
            foreach($data as $student)
                $userManager->addStudentToBDD($student);

            $this->addFlash('succes', count($data)." étudiant(s) ont été ajoutés à la base de données.");
            return $this->redirectToRoute('user_index');
        }

        return $this->render('UserBundle:user:uploadStudentList.html.twig', array('form' => $form->createView()));

    }

    /**
     * Upload teachers.
     *
     * @Route("/uploadTeacherList", name="user_upload_teacher_list")
     * @Method({"GET", "POST"})
     */
    public function uploadProfListAction(Request $request)
    {
        $form = $this->createForm(UserCSVType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            $file = $form->getData()['usercsv'];

            //Sauvegarde temporaire du fichier
            $filename = uniqid() . "." . $file->getClientOriginalExtension();
            $path = __DIR__ . '/../../../web/upload';
            $file->move($path, $filename);

            //Analyse du fichier
            $CSVToArray = $this->get('app.csvtoarray');
            $data = $CSVToArray->convert($path . "/" . $filename, ',', array(
                'lastname',
                'firstname',
                'email',
                'ue'
                ));

            //Suppression du fichier après analyse
            unlink($path . "/" . $filename);

            $userManager = $this->get("app.user_manager");
            foreach($data as $prof)
                $userManager->addProfToBDD($prof);

            $this->addFlash('succes', count($data)." enseignant(s) ont été ajoutés à la base de données.");
            return $this->redirectToRoute('user_index');
        }

        return $this->render('UserBundle:user:uploadTeacherList.html.twig', array('form' => $form->createView()));

    }

    /**
     * Creates a new user entity.
     *
     * @Route("/new", name="user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm('UserBundle\Form\UserCreationType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEnabled(false);
            $user->setPlainPassword(uniqid());
            $user->setUsername($user->getEmail());

            $rsaKeyManager = $this->get('app.rsa_key_manager');
            $rsaKeyManager->generateUserKeys($user);

            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            //Envoi du mail d'activation du compte
            $mailerService = $this->get('app.mailer_service');
            $mailerService->sendActivation($user);

            $this->addFlash('success', "L'utilisateur a bien été ajouté !");
            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        }

        return $this->render('UserBundle:user:new.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/{id}", name="user_show")
     * @Method("GET")
     */
    public function showAction(User $user)
    {
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        $deleteForm = $this->createDeleteForm($user);

        return $this->render('UserBundle:user:show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $user)
    {
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        $editForm = $this->createForm('UserBundle\Form\UserEditType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $user->setUsername($user->getEmail());

            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            $this->addFlash('success', "L'utilisateur a bien été édité !");
            return $this->redirectToRoute('user_index');
        }

        return $this->render('UserBundle:user:edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Displays a form to edit password an existing user entity.
     *
     * @Route("/{id}/editPassword", name="user_edit_password")
     * @Method({"GET", "POST"})
     */
    public function editPasswordAction(Request $request, User $user)
    {
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        $editForm = $this->createForm('UserBundle\Form\UserEditPasswordType');
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $session = $this->get('session');
            $adminPrivateKey = $session->get('userPrivateKey');

            $userPrivateKey = utf8_decode($user->getPrivateKeyAdmin());
            $crypt_rsa = new Crypt_RSA();
            $crypt_rsa->loadKey($adminPrivateKey);
            $userPrivateKey = $crypt_rsa->decrypt($userPrivateKey);

            $newPassword = $editForm->getData()['plainPassword'];
            $user->setPlainPassword($newPassword);

            $rsa = $this->get('app.rsa_key_manager');
            $userPrivateKey = utf8_encode($rsa->cryptByPassword($userPrivateKey, $newPassword));

            $user->setPrivateKey($userPrivateKey);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);

            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            if($editForm->getData()['sendEmail']) {
                $mailerService = $this->get('app.mailer_service');
                $mailerService->sendPasswordMail($user, $newPassword);
            }

            $this->addFlash('success', "Le mot de passe de l'utilisateur a bien été édité !");
            return $this->redirectToRoute('user_index');
        }

        return $this->render('UserBundle:user:editPassword.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="user_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $user)
    {
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        $this->addFlash('success', "L'utilisateur a bien été supprimé !");
        return $this->redirectToRoute('user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Send activation mail a user entity.
     *
     * @Route("/{id}/sendActivation", name="user_send_activation_mail")
     * @Method("GET")
     */
    public function sendActivationAction(Request $request, User $user)
    {
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');


        if(!$user->isEnabled()) {
            $mailerService = $this->get('app.mailer_service');
            $mailerService->sendActivation($user);
            $this->addFlash('success', "L'email d'activation a bien été envoyé à l'utilisateur.");
        } else {
            $this->addFlash('warning', "Ce compte utilisateur est déjà activé.");
        }

        return $this->redirectToRoute('user_index');
    }
}
