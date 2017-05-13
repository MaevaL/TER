<?php

namespace AppBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trt\SwiftCssInlinerBundle\Plugin\CssInlinerPlugin;
use UserBundle\Entity\User;

class MailerService {

    private $mailer = null;
    private $mailer_from = null;
    private $container = null;

    public function __construct(\Swift_Mailer $mailer, $mailer_from, ContainerInterface $container)
    {
        $this->mailer = $mailer;
        $this->mailer_from = $mailer_from;
        $this->container = $container;
    }

    public function sendEmail(array $options)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($options['subject'])
            ->setFrom(array($this->mailer_from['email'] => $this->mailer_from['name']))
            ->setTo($options['to'])
            ->setContentType('text/html')
            ->setBody(
                $options['content']
            )
        ;
        $message->getHeaders()->addTextHeader(
            CssInlinerPlugin::CSS_HEADER_KEY_AUTODETECT
        );

        return $this->mailer->send($message);
    }

    public function sendPasswordMail(User $user, $password) {
        $subject = "Votre nouveau mot de passe";

        $options = array(
            'subject' => $subject,
            'to' => $user->getEmail(),
            'content' => $this->container->get('templating')->render('AppBundle:Mail:template.html.twig', array(
                'user' => $user,
                'subject' => $subject,
                'content' => "Voici votre nouveau mot de passe pour accéder au service. Mot de passe : ".$password,
            )),

        );

        return $this->sendEmail($options);
    }

    public function sendPasswordRequest(User $user) {
        $subject = "Demande de nouveau mot de passe";

        $options = array(
            'subject' => $subject,
            'to' => $user->getEmail(),
            'content' => $this->container->get('templating')->render('AppBundle:Mail:template.html.twig', array(
                'subject' => $subject,
                'content' => "Vous avez reçu une nouvelle requête de changement de mot de passe.",
            )),
        );

        return $this->sendEmail($options);
    }

    public function sendActivation(User $user) {
        $subject = "Activation de votre compte";

        $router = $this->container->get('router');
        $activationUrl = $router->generate('user_registration', array(
            'activationToken' => $user->getActivationToken(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);

        $options = array(
            'subject' => $subject,
            'to' => $user->getEmail(),
            'content' => $this->container->get('templating')->render('AppBundle:Mail:template.html.twig', array(
                'user' => $user,
                'subject' => $subject,
                'content' => "Afin de finaliser la création de votre compte veuillez cliquer sur le lien suivant : <br><a href='".$activationUrl."'>".$activationUrl."</a>",
            )),
        );

        return $this->sendEmail($options);
    }
}