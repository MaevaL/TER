<?php

namespace AppBundle\Service;

use UserBundle\Entity\User;
use Trt\SwiftCssInlinerBundle\Plugin\CssInlinerPlugin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service permettant l'envoi des différents types de mails utilisés sur le site
 *
 * @package AppBundle\Service
 */
class MailerService {

    /**
     * @var null|\Swift_Mailer Service de mail
     */
    private $mailer = null;

    /**
     * @var null Nom et adresse de l'expéditeur
     */
    private $mailer_from = null;

    /**
     * @var null|ContainerInterface
     */
    private $container = null;

    /**
     * MailerService constructor.
     *
     * @param \Swift_Mailer $mailer Service de mail
     * @param $mailer_from Nom et adresse de l'expéditeur
     * @param ContainerInterface $container
     */
    public function __construct(\Swift_Mailer $mailer, $mailer_from, ContainerInterface $container)
    {
        $this->mailer = $mailer;
        $this->mailer_from = $mailer_from;
        $this->container = $container;
    }

    /**
     * Envoi d'un email avec les options en paramètre
     * @param array $options Contient toutes les options de l'email
     * @return int Email envoyé ou non
     */
    public function sendEmail(array $options)
    {
        //Options
        //subject => Sujet de l'email (string)
        //to => Email du destinataire (string)
        //content => Contenu de l'email (string/html)

        //Création du message
        $message = new \Swift_Message();

        $message
            ->setSubject($options['subject'])
            ->setFrom(array($this->mailer_from['email'] => $this->mailer_from['name']))
            ->setTo($options['to'])
            ->setContentType('text/html')
            ->setBody(
                $options['content']
            )
        ;
        //Activation du css
        /*
        $message->getHeaders()->addTextHeader(
            CssInlinerPlugin::CSS_HEADER_KEY_AUTODETECT
        );*/

        //Envoi
        return $this->mailer->send($message);
    }

    /**
     * Message qui permet d'envoyer un mot de passe à un utilisateur
     *
     * @param User $user Utilisateur destinataire
     * @param $password string Mot de passe à envoyer
     * @return int Email envoyé ou non
     */
    public function sendPasswordMail(User $user, $password) {
        //Sujet du mail
        $subject = "Votre nouveau mot de passe";

        //Définition des options du message et création du template de l'email
        $options = array(
            'subject' => $subject,
            'to' => $user->getEmail(),
            'content' => $this->container->get('templating')->render('AppBundle:Mail:template.html.twig', array(
                'user' => $user,
                'subject' => $subject,
                'content' => "Voici votre nouveau mot de passe pour accéder au service. Mot de passe : ".$password,
            )),

        );

        //Envoi de l'email avec les options données
        return $this->sendEmail($options);
    }

    /**
     * Message qui est envoyé à l'administrateur pour lui signaler une demande de nouveau mot de passe
     *
     * @param User $user Utilisateur destinataire
     * @return int Email envoyé ou non
     */
    public function sendPasswordRequest(User $user) {
        //Sujet du mail
        $subject = "Demande de nouveau mot de passe";

        //Définition des options du message et création du template de l'email
        $options = array(
            'subject' => $subject,
            'to' => $user->getEmail(),
            'content' => $this->container->get('templating')->render('AppBundle:Mail:template.html.twig', array(
                'subject' => $subject,
                'content' => "Vous avez reçu une nouvelle requête de changement de mot de passe.",
            )),
        );

        //Envoi de l'email avec les options données
        return $this->sendEmail($options);
    }

    /**
     * Message qui est envoyé afin de finaliser la création du compte et de l'activer via un lien
     *
     * @param User $user Utilisateur destinataire
     * @return int Email envoyé ou non
     */
    public function sendActivation(User $user) {
        //Sujet du mail
        $subject = "Activation de votre compte";

        //Création du lien d'activation
        $router = $this->container->get('router');
        $activationUrl = $router->generate('user_registration', array(
            'activationToken' => $user->getActivationToken(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);

        //Définition des options du message et création du template de l'email
        $options = array(
            'subject' => $subject,
            'to' => $user->getEmail(),
            'content' => $this->container->get('templating')->render('AppBundle:Mail:template.html.twig', array(
                'user' => $user,
                'subject' => $subject,
                'content' => "Afin de finaliser la création de votre compte veuillez cliquer sur le lien suivant : <br><a href='".$activationUrl."'>".$activationUrl."</a>",
            )),
        );

        //Envoi de l'email avec les options données
        return $this->sendEmail($options);
    }
}