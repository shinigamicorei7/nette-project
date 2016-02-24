<?php

namespace App\Forms;

use \Nette\Application\UI\Form;
use \Nette\Security\User;
use \Nette\Database\Context;
use \Nette\Mail\IMailer;
use Nette\Bridges\ApplicationLatte\ILatteFactory;

class SignFormFactory extends \Nette\Object
{

	/** @var User */
	private $user;

	/** @var Context */
	private $database;

	/** @var IMailer */
	private $mailer;

	/** @var \Latte\Engine */
	private $latte;

	public function __construct(User $user, Context $database, IMailer $mailer, ILatteFactory $late_factory)
	{
		$this->user = $user;
		$this->database = $database;
		$this->mailer = $mailer;
		$this->latte = $late_factory->create();
	}

	/**
	 * @return Form
	 */
	public function create()
	{
		$form = new Form;
		$form->addText('username', 'Username:')
				->setRequired('Please enter your username.');

		$form->addPassword('password', 'Password:')
				->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Keep me signed in');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = array($this, 'formSucceeded');
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		if ($values->remember)
		{
			$this->user->setExpiration('14 days', FALSE);
		}
		else
		{
			$this->user->setExpiration('20 minutes', TRUE);
		}

		try
		{
			$this->user->login($values->username, $values->password);
		}
		catch (Nette\Security\AuthenticationException $e)
		{
			$form->addError($e->getMessage());
		}
	}

	public function createRecovery()
	{
		$form = new Form;
		$form->getElementPrototype()->class = 'ajax';
		$form->addText('email', 'Email:')->addRule(Form::EMAIL)->isRequired();
		$form->addSubmit('send', 'Enviar');

		$form->onSuccess[] = array($this, 'recoverySucceeded');
		return $form;
	}

	public function recoverySucceeded(Form $form, $values)
	{
		$row = $this->database->table('users')->where('email', $values->email)->fetch();
		if (!$row)
		{
			$form->addError('No existe una cuenta con este correo.');
			$form->getPresenter()->redrawControl('recoveryForm');
		}
		else
		{
			$mail = new \Nette\Mail\Message();
			$mail->setFrom('recovery@pasword.com')
					->setSubject('Password Recovery')
					->addTo($row->email)
					->setHtmlBody($this->getEmailBody($row));

			try
			{
				$this->mailer->send($mail);
			}
			catch (\Nette\Mail\SendException $e)
			{
				$form->addError($e->getMessage());
				$form->getPresenter()->redrawControl('recoveryForm');
			}

			$form->getPresenter()->flashMessage('<b>Listo!</b> Revisa tu bandeja de entrada.');
			$form->getPresenter()->redrawControl('recoveryForm');
		}
	}

	public function getEmailBody($row)
	{
		$templates_dir = realpath(__DIR__ . '/../presenters/templates');
		$templates_dir = rtrim($templates_dir, '\/');
		$recovery_template = $templates_dir . '/emails/recovery.latte';
		return $this->latte->renderToString($recovery_template, array('person' => $row));
	}

}
