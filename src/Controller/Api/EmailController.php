<?php
/**
 * Handles sending of emails from all sections of the system
 */
namespace App\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\ExpressionLanguage\Expression;
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
}
