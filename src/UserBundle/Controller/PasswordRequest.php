<?php

namespace UserBundle\Controller;

use Sinner\Phpseclib\Crypt\Crypt_RSA;
use UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;
use UserBundle\Form\UserCSVType;
use UserBundle\Form\UserPasswordRequestType;

/**
 * PasswordRequest controller.
 *
 * @Route("/")
 */
class PasswordRequest extends Controller
{
    /**
     * UserRequest a new password
     *
     * @Route("/password/request", name="password_request")
     * @Method({"GET", "POST"})
     */
    public function passwordRequestAction(Request $request)
    {
        $form = $this->createForm(UserPasswordRequestType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $userRepository = $em->getRepository('UserBundle:User');

            //TODO : vérifier si l'utilisateur a fait la finalisation de son compte

            //Recherche si l'utilisateur existe
            $user = $userRepository->findOneBy(array(
                'email' => $form->getData()['email'],
            ));

            //Il existe, on enregistre la demande et on désactive le compte
            if($user != null) {
                $mailerService = $this->get('app.mailer_service');

                //Le compte a déjà été finalisé
                if($user->isEnabled()) {
                    $user->setPasswordRequestedAt(new \DateTime());
                    $user->setEnabled(false);
                    $em->persist($user);
                    $em->flush();

                    //Envoi d'un mail à l'administrateur
                    $admin = $userRepository->findOneByRole('ROLE_SUPER_ADMIN');
                    $mailerService->sendPasswordRequest($admin);

                    $this->addFlash('success', 'Votre demande a bien été enregistrée, un administrateur vous contactera afin de modifier votre mot de passe.');
                }
                //Le compte n'est pas finalisé envoi du mail de confirmation
                else {
                    $mailerService->sendActivation($user);

                    $this->addFlash('success', "Votre compte n'a pas été finalisé, un mail permettant d'activer votre compte vous a été envoyé.");
                }

                return $this->redirectToRoute('fos_user_security_login');
            }
            //Il n'existe pas dans la base de données
            else {
                $this->addFlash('warning', "L'adresse email renseignée n'a pas été trouvée, veuillez réessayer.");
            }
        }

        return $this->render('UserBundle:PasswordRequest:request.html.twig', array(
            'form' => $form->createView(),
        ));
    }

}