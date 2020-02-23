<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\User;
use Swift_Mailer;
use Swift_Message;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig_Environment;

class EmailSender
{

    /**
     * @var Swift_Mailer
     */
    private $mailer;
    /**
     * @var string
     */
    private $emailFrom;
    /**
     * @var Twig_Environment
     */
    private $environment;


    public function __construct(string $emailFrom, Swift_Mailer $mailer, Twig_Environment $environment)
    {
        $this->mailer = $mailer;
        $this->emailFrom = $emailFrom;
        $this->environment = $environment;
    }

    /**
     * @param User $user
     * @param User $recipient
     * @param Activity $activity
     * @param $subject
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendEmail(User $user, User $recipient, Activity $activity, $subject): void
    {
        $message = (new Swift_Message(
            $user->getName() . ' ' . $user->getSurname() . $subject . $activity->getName()
        ))
            ->setFrom($this->emailFrom)
            ->setTo($recipient->getEmail())
            ->setBody(
                $this->environment->render(
                    'mail/mail.html.twig',
                    [
                        'user' => $user,
                        'subject' => $subject,
                        'recipient' => $recipient,
                        'activity' => $activity
                    ]
                ),
                'text/html'
            );
        $this->mailer->send($message);
    }
}
