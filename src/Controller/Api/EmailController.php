<?php
/**
 * Handles sending of emails from all sections of the system
 */
namespace App\Controller\Api;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EmailController extends AbstractController
{
	private MailerInterface $mailer;

	public function __construct(MailerInterface $mailer)
	{
		$this->mailer = $mailer;
	}
}
