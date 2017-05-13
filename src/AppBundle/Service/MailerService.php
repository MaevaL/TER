<?php

namespace AppBundle\Service;

use Trt\SwiftCssInlinerBundle\Plugin\CssInlinerPlugin;

class MailerService {

    private $mailer = null;
    private $mailer_from = null;

    public function __construct(\Swift_Mailer $mailer, $mailer_from)
    {
        $this->mailer = $mailer;
        $this->mailer_from = $mailer_from;
    }

    public function sendEmail(array $options)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($options['subject'])
            ->setFrom(array($this->mailer_from['email'] => $this->mailer_from['name']))
            ->setTo($options['to'])
            ->setContentType('text/html')
            //->setBody('')

            ->setBody(
                $options['content']
            )
        ;
        $message->getHeaders()->addTextHeader(
            CssInlinerPlugin::CSS_HEADER_KEY_AUTODETECT
        );

        return $this->mailer->send($message);
    }

}