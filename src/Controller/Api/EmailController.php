<?php
/**
 * Handles sending of emails from all sections of the system
 */
namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Carbon\Carbon;

class EmailController extends FOSRestController
{
    private $mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * MULTIMEDIA REQUEST APP. Send notification email to an assignee of a request.
     * @Rest\Post("sendemail/multimediaassigneenotify")
     * @Security("has_role('ROLE_MULTIMEDIA_EMAIL') or has_role('ROLE_GLOBAL_ADMIN')")
     */
    public function postSendemailMultimediaassigneenotifyAction(Request $request): Response
    {
        $message = (new \Swift_Message('Hello Email'))
            ->setFrom('admin@iccommand.emich.edu')
            ->setTo($request->request->get('recipient'))
            ->setBody(
                $this->renderView(
                    'multimedia_request/assignees/email.html.twig',
                    [
                        'customBody' => $request->request->get('customBody'),
                        'record' => $request->request->get('record'),
                    ]
                ),
                'text/html'
            );

        $this->mailer->send($message);

        $response = new Response('Message was sent.', 200, array('Content-Type' => 'text/html'));
        return $response;
    }
}