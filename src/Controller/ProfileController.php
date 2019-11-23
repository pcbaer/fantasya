<?php
declare (strict_types = 1);
namespace App\Controller;

use Doctrine\DBAL\DBALException;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Entity\User;
use App\Service\GameService;
use App\Service\PartyService;
use App\Repository\UserRepository;

/**
 * @IsGranted("ROLE_USER")
 */
class ProfileController extends AbstractController
{
	/**
	 * @var array(string=>string)
	 */
	private static $roles = [
		User::ROLE_ADMIN        => 'Administrator',
		User::ROLE_BETA_TESTER  => 'Betatester',
		User::ROLE_MULTI_PLAYER => 'Mehrere Parteien',
		User::ROLE_NEWS_CREATOR => 'News verfassen'
	];

	/**
	 * @var array(int=>string)
	 */
	private static $errors = [
		10 => 'Der Benutzername darf nicht leer sein.',
		11 => 'Der Benutzername darf nicht länger als 190 Zeichen sein.',
		12 => 'Es gibt bereits einen Benutzer mit diesem Namen.',
		20 => 'Das ist keine gültige E-Mail-Adresse.',
		21 => 'Die E-Mail-Adresse darf nicht länger als 190 Zeichen sein.',
		22 => 'Es gibt bereits einen Benutzer mit dieser E-Mail-Adresse.',
		30 => 'Das Passwort darf nicht leer sein.',
		40 => 'Die Einstellungen konnten nicht gespeichert werden.'
	];

	/**
	 * @var UserRepository
	 */
	private $userRepository;

	/**
	 * @var GameService
	 */
	private $gameService;

	/**
	 * @var PartyService
	 */
	private $partyService;

	/**
	 * @var UserPasswordEncoderInterface
	 */
	private $passwordEncoder;

	/**
	 * @var MailerInterface
	 */
	private $mailer;

	/**
	 * @param UserRepository $userRepository
	 * @param GameService $gameService
	 * @param PartyService $partyService
	 * @param UserPasswordEncoderInterface $encoder
	 * @param MailerInterface $mailer
	 */
	public function __construct(UserRepository $userRepository, GameService $gameService, PartyService $partyService,
								UserPasswordEncoderInterface $encoder, MailerInterface $mailer) {
		$this->userRepository  = $userRepository;
		$this->gameService     = $gameService;
		$this->partyService    = $partyService;
		$this->passwordEncoder = $encoder;
		$this->mailer          = $mailer;
	}

	/**
	 * @Route("/profile", name="profile")
	 *
	 * @param Request $request
	 * @return Response
	 * @throws DBALException
	 */
	public function index(Request $request): Response {
		$roles   = $this->getRoles();
		$flags   = $this->getFlags();
		$games   = $this->gameService->getAll();
		$parties = $this->partyService->getFor($this->user());
		$newbies = $this->partyService->getNewbies($this->user());

		$success   = null;
		$errorCode = 0;
		$error     = null;
		if ($request->query->has('error')) {
			$errorCode = (int)$request->query->get('error');
			if ($errorCode) {
				$error = $errorCode;
			} else {
				$success = true;
			}
		}

		return $this->render('profile/index.html.twig', [
			'roles'   => $roles,
			'flags'   => $flags,
			'games'   => $games,
			'parties' => $parties,
			'newbies' => $newbies,
			'success' => $success,
			'error'   => ['code' => $errorCode, 'text' => self::$errors[$error] ?? null]
		]);
	}

	/**
	 * @Route("/profile/change", name="profile_change")
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function change(Request $request): Response {
		if ($request->request->has('submitName') && $request->request->has('name')) {
			$name = $request->request->get('name');
			if ($name) {
				if (mb_strlen($name) <= 190) {
					$user = $this->user();
					if ($name !== $user->getName()) {
						if ($this->userRepository->findOneBy(['name' => $name])) {
							$error = 12;
						} else {
							$this->save($this->user()->setName($name), true);
							$error = 0;
						}
					}
				} else {
					$error = 11;
				}
			} else {
				$error = 10;
			}
		}

		if ($request->request->has('submitEmail') && $request->request->has('email')) {
			$email     = strtolower($request->request->get('email'));
			$validator = new EmailValidator();
			if ($validator->isValid($email, new RFCValidation())) {
				if (strlen($email) <= 190) {
					$user = $this->user();
					if ($email !== $user->getEmail()) {
						if ($this->userRepository->findOneBy(['email' => $email])) {
							$error = 22;
						} else {
							$this->save($this->user()->setEmail($email), true);
							$this->partyService->update($this->user());
							$error = 0;
						}
					}
				} else {
					$error = 21;
				}
			} else {
				$error = 20;
			}
		}

		if ($request->request->has('submitPassword') && $request->request->has('password')) {
			$password = $request->request->get('password');
			if ($password) {
				$user = $this->user();
				$user->setPassword($this->passwordEncoder->encodePassword($user, $password));
				$this->save($user, true);
				$error = 0;
			} else {
				$error = 30;
			}
		}

		return $this->redirectToRoute('profile', isset($error) ? ['error' => $error] : []);
	}

	/**
	 * @Route("/profile/settings", name="profile_settings")
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function settings(Request $request): Response {
		if ($request->request->has('submitSettings') && $request->request->has('flags')) {
			$user  = $this->user();
			$flags = $request->request->get('flags');

			try {
				$withAttachment = $flags['withAttachment'] ?? false;
				$user->setFlag(User::FLAG_WITH_ATTACHMENT, (bool)$withAttachment);

				$this->save($user);
				$error = 0;
			} catch (\Exception $e) {
				$error = 40;
			}
		}

		return $this->redirectToRoute('profile', isset($error) ? ['error' => $error] : []);
	}

	/**
	 * @return string[]
	 */
	private function getRoles(): array {
		$roles = [];
		foreach ($this->user()->getRoles() as $role) {
			if ($role !== User::ROLE_USER) {
				$roles[] = self::$roles[$role] ?? $role;
			}
		}
		return $roles;
	}

	/**
	 * @return array(string=>string)
	 */
	private function getFlags(): array {
		$withAttachment = $this->user()->hasFlag(User::FLAG_WITH_ATTACHMENT);
		return [
			'withAttachment' => $withAttachment ? ' checked="checked"' : ''
		];
	}

	/**
	 * @return User
	 */
	private function user(): User {
		return $this->getUser();
	}

	/**
	 * @param User $user
	 * @param bool $sendMail
	 */
	private function save(User $user, bool $sendMail = false) {
		$entityManager = $this->getDoctrine()->getManager();
		$entityManager->persist($user);
		$entityManager->flush();
		if ($sendMail) {
			$this->sendMail($user);
		}
	}

	/**
	 * @param User $user
	 */
	private function sendMail(User $user) {
		$mail = new Email();
		$mail->from(new Address($this->getParameter('app.mail.admin.address'), $this->getParameter('app.mail.admin.name')));
		$mail->to(new Address($user->getEmail(), $user->getName()));
		$mail->subject('Fantasya-Profil geändert');
		$mail->text($this->renderView('emails/profile_change.html.twig', ['user' => $user]));
		$this->mailer->send($mail);
	}
}
