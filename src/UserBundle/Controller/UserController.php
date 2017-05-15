<?php

namespace UserBundle\Controller;

use UserBundle\Entity\User;
use UserBundle\Form\UserCSVType;
use Sinner\Phpseclib\Crypt\Crypt_RSA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * User controller.
 *
 * @Route("/admin/user")
 */

/**
 * Class UserController
 * Regroupe toutes les fonctionnalités liées à un utilisateur
 *
 * @Route("/admin/user")
 * @package UserBundle\Controller
 */
class UserController extends Controller
{
    /**
     * Affiche tous les utilisateurs en base de données
     *
     * @Route("/", name="user_index")
     * @Method("GET")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        //Récupère tout les utilisateurs sauf le super admin
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('UserBundle:User')->findNonSuperAdmin();

        //Affichage de la liste des utilisateurs
        return $this->render('UserBundle:User:index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Permet à un administrateur de créer un nouvel utilisateur
     *
     * @Route("/new", name="user_new")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        //Création du formulaire avec un nouvel utilisateur
        $user = new User();
        $form = $this->createForm('UserBundle\Form\UserCreationType', $user);

        //Récupération de la requête et vérifie si le formulaire est validé
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Création du compte inactif avec un mot de passe aléatoire
            $user->setEnabled(false);
            $user->setPlainPassword(uniqid());
            //Définition de l'adresse email comme identifiant de connexion
            $user->setUsername($user->getEmail());

            //Génération des clés RSA
            $rsaKeyManager = $this->get('app.rsa_key_manager');
            $rsaKeyManager->generateUserKeys($user);

            //Sauvegarde de l'utilisateur
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            //Envoi du mail d'activation du compte
            $mailerService = $this->get('app.mailer_service');
            $mailerService->sendActivation($user);

            //Redirection vers la fiche de l'utilisateur créé avec un mesasge de succès
            $this->addFlash('success', "L'utilisateur a bien été ajouté !");
            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        }

        //Affichage du formulaire de création d'un utilisateur
        return $this->render('UserBundle:User:new.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }
    
    /**
     * Cherche et affiche la fiche un utilisateur donnée en paramètre
     *
     * @Route("/{id}", name="user_show")
     * @Method("GET")
     *
     * @param User $user Utilisateur à afficher
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(User $user)
    {
        //Impossible d'afficher la fiche du super administrateur
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        //Création du formulaire de suppresion pour le promoser sur la fiche
        $deleteForm = $this->createDeleteForm($user);

        //Affichage de la fiche de l'utilisateur
        return $this->render('UserBundle:user:show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Permet d'éditer un utilisateur donné en paramètre
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param User $user Utilisateur à éditer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, User $user)
    {
        //Impossible d'éditer le super administrateur
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        //Création du formulaire
        $editForm = $this->createForm('UserBundle\Form\UserEditType', $user);

        //Récupération de la requête et vérifie si le fromulaire est validé
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //Définie que le nom d'utilisateur est l'adresse email de l'utilisateur
            $user->setUsername($user->getEmail());

            //Sauvegarde de l'utilisateur
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            //Redirection vers la fiche de l'utilisateur édité avec un message de succès
            $this->addFlash('success', "L'utilisateur a bien été édité !");
            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        }

        //Affichage du formulaire d'édition
        return $this->render('UserBundle:User:edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Permet d'éditer le mot de passe d'un utilisateur donné en paramètre
     *
     * @Route("/{id}/editPassword", name="user_edit_password")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param User $user Utilisateur à modifier le mot de passe
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editPasswordAction(Request $request, User $user)
    {
        //Impossible de changer le mot de passe du super administrateur
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        //Création du formulaire de changement de mot de passe
        $editForm = $this->createForm('UserBundle\Form\UserEditPasswordType');

        //Récupération de la requête et vérifie si le formulaire ets validé
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //Récupération de la clé privée de l'utilisateur courant
            $session = $this->get('session');
            $adminPrivateKey = $session->get('userPrivateKey');

            //Déchiffrage de la clé de l'utilisateur demandé
            $userPrivateKey = utf8_decode($user->getPrivateKeyAdmin());
            $crypt_rsa = new Crypt_RSA();
            $crypt_rsa->loadKey($adminPrivateKey);
            $userPrivateKey = $crypt_rsa->decrypt($userPrivateKey);

            //Changement du mot de passe et mise à jour sur la clé privée de l'utilisateur
            $newPassword = $editForm->getData()['plainPassword'];
            $user->setPlainPassword($newPassword);
            $rsa = $this->get('app.rsa_key_manager');
            $userPrivateKey = utf8_encode($rsa->cryptByPassword($userPrivateKey, $newPassword));

            //Mise à jour de la clé privée et activation de l'utilisateur s'il ne l'étais pas
            $user->setPrivateKey($userPrivateKey);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);

            //Récupération du UserManager de FOSUser et sauvegarde de l'utilisateur
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            //Si démandé, envoi du mot de passe par email à l'utilisateur
            if($editForm->getData()['sendEmail']) {
                $mailerService = $this->get('app.mailer_service');
                $mailerService->sendPasswordMail($user, $newPassword);
            }

            //Redirection vers la liste des utilisateur avec un message de succès
            $this->addFlash('success', "Le mot de passe de l'utilisateur a bien été édité !");
            return $this->redirectToRoute('user_index');
        }

        //Affichage du formulaire de changement de mote de passe
        return $this->render('UserBundle:User:editPassword.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Permet de supprimer un utilisateur en paramètre
     *
     * @Route("/{id}", name="user_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param User $user Utilisateur à supprimer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, User $user)
    {
        //Impossible de supprimer le super administrateur
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        //Création du formualire de suppression
        $form = $this->createDeleteForm($user);

        //Récupération de la requête et vérifie que le formulaire est valide
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Suppression de l'utilisateur
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        //Redirection vers la liste des utilisateurs avec un mesage de succès
        $this->addFlash('success', "L'utilisateur a bien été supprimé !");
        return $this->redirectToRoute('user_index');
    }

    /**
     * Créé le formulaire permettant de supprimer un utilisateur
     *
     * @param User $user Utilisateur à supprimer
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        //Création du formulaire de suppresion de l'utilisateur
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Permet d'envoyer un email d'activation de compte à un utilisateur
     *
     * @Route("/{id}/sendActivation", name="user_send_activation_mail")
     * @Method("GET")
     *
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendActivationAction(Request $request, User $user)
    {
        //Impossible d'envoyer un email d'activation au super administrateur
        if($user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException('Utilisateur Introuvable');

        //Envoi uniquement si le compte n'est pas déjà activé
        if(!$user->isEnabled()) {
            $mailerService = $this->get('app.mailer_service');
            $mailerService->sendActivation($user);
            $this->addFlash('success', "L'email d'activation a bien été envoyé à l'utilisateur.");
        } else {
            $this->addFlash('warning', "Ce compte utilisateur est déjà activé.");
        }

        //Redirection vers la liste des utilisateurs
        return $this->redirectToRoute('user_index');
    }

    /**
     * Upload d'une liste d'étudiants a ajouter dans un fichier CSV
     *
     * @Route("/uploadStudentList", name="user_upload_student_list")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadStudentListAction(Request $request)
    {
        //Création du formulaire
        $form = $this->createForm(UserCSVType::class);

        //Récupération de la requête et vérifie que le formulaire est valide
        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            //Récupération du fichier depuis le formulaire
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

            //Ajout des utilisateurs
            $userManager = $this->get("app.user_manager");
            foreach($data as $student)
                $userManager->addStudentToBDD($student);

            //Redirection vers la liste des utilisateurs avec un message de succès
            $this->addFlash('succes', count($data)." étudiant(s) ont été ajoutés à la base de données.");
            return $this->redirectToRoute('user_index');
        }

        //Affichage du formulaire d'upload
        return $this->render('UserBundle:User:uploadStudentList.html.twig', array('form' => $form->createView()));
    }

    /**
     * Upload d'une liste de professeurs a ajouter dans un fichier CSV
     *
     * @Route("/uploadTeacherList", name="user_upload_teachers_list")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadTeachersListAction(Request $request)
    {
        //Création du formulaire
        $form = $this->createForm(UserCSVType::class);

        //Récupération de la requête et vérifie que le formulaire est valide
        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            //Récupération du fichier depuis le formulaire
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

            //Ajout des utilisateurs
            $userManager = $this->get("app.user_manager");
            foreach($data as $prof)
                $userManager->addProfToBDD($prof);

            //Redirection vers la liste des utilisateurs avec un message de succès
            $this->addFlash('succes', count($data)." enseignant(s) ont été ajoutés à la base de données.");
            return $this->redirectToRoute('user_index');
        }

        //Affichage du formulaire d'upload
        return $this->render('UserBundle:User:uploadTeacherList.html.twig', array('form' => $form->createView()));
    }
}
