<?php

namespace UserBundle\Controller;

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

        $users = $em->getRepository('UserBundle:User')->findAll();

        return $this->render('UserBundle:user:index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Lists all user entities.
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
                'codepromotion',
                'nompromotion',
                'groupe',
                'annee',
                'email'));

            var_dump($data);
            //Suppression du fichier après analyse
            unlink($path . "/" . $filename);
        }

        return $this->render('UserBundle:user:uploadStudentList.html.twig', array('form' => $form->createView()));

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
            $user->setUsername($user->getEmail());

            $rsaKeyManager = $this->get('app.rsa_key_manager');
            $rsaKeyManager->generateUserKeys($user);

            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

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
        $editForm = $this->createForm('UserBundle\Form\UserEditPasswordType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $user->setUsername($user->getEmail());

            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

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
}
