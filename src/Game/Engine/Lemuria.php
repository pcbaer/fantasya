<?php
declare(strict_types = 1);
namespace App\Game\Engine;

use Doctrine\ORM\EntityManagerInterface;
use Lemuria\Engine\Fantasya\Factory\Model\LemuriaNewcomer;
use Lemuria\Engine\Fantasya\Storage\NewcomerConfig;
use Lemuria\Exception\UnknownUuidException;
use Lemuria\Id;
use Lemuria\Lemuria as LemuriaGame;
use Lemuria\Model\Catalog;
use Lemuria\Model\Exception\NotRegisteredException;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Party as PartyModel;

use App\Data\Newbie as NewbieData;
use App\Entity\Assignment;
use App\Entity\Game;
use App\Entity\User;
use App\Game\Engine;
use App\Game\Newbie;
use App\Game\Party;
use App\Game\Race;
use App\Game\Statistics;
use App\Repository\AssignmentRepository;

class Lemuria implements Engine
{
	use BuilderTrait;

	private static bool $hasBeenInitialized = false;

	private static bool $hasBeenChanged = false;

	private static NewcomerConfig $config;

	public function __construct(private AssignmentRepository $assignmentRepository, private EntityManagerInterface $entityManager) {
		if (!self::$hasBeenInitialized) {
			self::$config = new NewcomerConfig(__DIR__ . '/../../../var/lemuria');
			LemuriaGame::init(self::$config);
			LemuriaGame::load();
			self::$hasBeenInitialized = true;
		}
	}

	function __destruct() {
		if (self::$hasBeenChanged) {
			LemuriaGame::save();
			self::$hasBeenChanged = false;
		}
	}

	public function canSimulate(Game $game, int $turn): bool {
		return $turn === $this->getRound($game);
	}

	public function getRound(Game $game): int {
		return LemuriaGame::Calendar()->Round();
	}

	public function getLastZat(Game $game): \DateTime {
		$dateTime = new \DateTime();
		return $dateTime->setTimestamp(self::$config[NewcomerConfig::MDD]);
	}

	public function getById(string $id, Game $game): ?Party {
		try {
			/** @var PartyModel $party */
			$party = LemuriaGame::Catalog()->get(Id::fromId($id), Catalog::PARTIES);
			return $this->createParty($party);
		} catch (NotRegisteredException) {
			return null;
		}
	}

	public function getByOwner(string $owner, Game $game): ?Party {
		/** @var PartyModel $party */
		$party = LemuriaGame::Registry()->find($owner);
		return $party ? $this->createParty($party) : null;
	}

	/**
	 * @return Party[]
	 */
	public function getParties(User $user, Game $game): array {
		$parties = [];
		foreach ($this->assignmentRepository->findByUser($user) as $assignment) {
			/** @var PartyModel $party */
			$party = LemuriaGame::Registry()->find($assignment->getUuid());
			if ($party) {
				$parties[] = $this->createParty($party);
			}
		}
		return $parties;
	}

	/**
	 * @return Newbie[]
	 */
	public function getNewbies(User $user, Game $game): array {
		$newbies = [];
		foreach ($this->assignmentRepository->findByUser($user) as $assignment) {
			try{
				$newcomer  = LemuriaGame::Debut()->get($assignment->getUuid());
				$newbies[] = $this->createNewbie($newcomer, $user);
			} catch (UnknownUuidException) {
			}
		}
		return $newbies;
	}

	public function getStatistics(Game $game): Statistics {
		return new LemuriaStatistics();
	}

	public function updateUser(User $user, Game $game): void {
	}

	public function create(Newbie $newbie, Game $game): void {
		$name        = $newbie->getName();
		$description = $newbie->getDescription();
		$race        = new Race($newbie->getRace());
		$race        = self::createRace($race->toLemuria());
		$newcomer    = new LemuriaNewcomer();
		$newcomer->setName($name)->setDescription($description)->setRace($race);
		LemuriaGame::Debut()->add($newcomer);
		self::$hasBeenChanged = true;

		$assignment = new Assignment();
		$assignment->setUser($newbie->getUser());
		$assignment->setUuid($newcomer->Uuid());
		$this->entityManager->persist($assignment);
		$this->entityManager->flush();
	}

	public function delete(Newbie $newbie, Game $game): void {
		try {
			$newcomer = LemuriaGame::Debut()->get($newbie->getUuid());
			LemuriaGame::Debut()->remove($newcomer);
			self::$hasBeenChanged = true;
		} catch (UnknownUuidException) {
		}
		$assignment = $this->assignmentRepository->findByUuid($newbie->getUuid());
		$this->entityManager->remove($assignment);
		$this->entityManager->flush();
	}

	private function createParty(PartyModel $party): Party {
		$uuid  = $party->Uuid();
		$email = $this->fetchEmailAddress($uuid);
		$user  = $this->assignmentRepository->findByUuid($party->Uuid())?->getUser()->getId();
		return new Party([
			'id'           => (string)$party->Id(),
			'rasse'        => (string)Race::lemuria((string)$party->Race()),
			'name'         => $party->Name(),
			'beschreibung' => $party->Description(),
			'owner_id'     => $uuid,
			'user_id'      => $user,
			'email'        => $email
		]);
	}

	private function createNewbie(LemuriaNewcomer $newcomer, User $user): Newbie {
		$data = new NewbieData();
		$data->setName($newcomer->Name());
		$data->setDescription($newcomer->Description());
		$data->setRace((string)Race::lemuria((string)$newcomer->Race()));
		return Newbie::fromData($data)->setUser($user)->setUuid($newcomer->Uuid());
	}

	private function fetchEmailAddress(string $uuid): string {
		$assignment = $this->assignmentRepository->findByUuid($uuid);
		return $assignment ? $assignment->getUser()->getEmail() : '';
	}
}
