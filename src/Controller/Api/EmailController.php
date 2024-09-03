<?php
/**
 * Handles sending of emails from all sections of the system
 */
namespace App\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends AbstractFOSRestController
{
	private MailerInterface $mailer;

	public function __construct(MailerInterface $mailer)
	{
		$this->mailer = $mailer;
	}

	#[Route('/sendemail/multimediaassigneenotify', methods: ['POST'])]
	#[IsGranted('ROLE_MULTIMEDIA_EMAIL')]
	#[IsGranted('ROLE_GLOBAL_ADMIN')]
	public function postSendemailMultimediaassigneenotifyAction(Request $request): Response
	{
		$message = (new Email())
			->subject('Hello there!')
			->from('admin@iccommand.emich.edu')
			->to($request->request->get('recipient'))
			->html(
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

		return new Response('Message was sent.', 200, array('Content-Type' => 'text/html'));
	}
}
