<?php

namespace App\Command;

use App\Entity\Enum\UserRole;
use App\Entity\Larp;
use App\Entity\LarpApplication;
use App\Entity\LarpParticipant;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:seed:larp',
    description: 'Seed users, participants and applications for a given LARP id (idempotent)'
)]
class SeedLarpFixturesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('larpId', InputArgument::REQUIRED, 'LARP UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $larpId = (string) $input->getArgument('larpId');

        if (!Uuid::isValid($larpId)) {
            $io->error('Invalid LARP UUID.');
            return Command::INVALID;
        }

        /** @var Larp|null $larp */
        $larp = $this->em->getRepository(Larp::class)->find($larpId);
        if (!$larp) {
            $io->error('LARP not found.');
            return Command::FAILURE;
        }

        $userRepo = $this->em->getRepository(User::class);
        $participantRepo = $this->em->getRepository(LarpParticipant::class);
        $characterRepo = $this->em->getRepository(LarpCharacter::class);

        $users = $this->ensureUsers($userRepo, $larpId, 150);
        $io->writeln(sprintf('Users present: %d', count($users)));

        $organizerParticipants = $this->ensureOrganizerParticipants($participantRepo, $larp, $users);
        $io->writeln(sprintf('Organizer participants present: %d', count($organizerParticipants)));

        $characters = $this->getLarpCharacters($characterRepo, $larp);
        $playerParticipants = $this->ensurePlayerParticipantsForCharactersCount($participantRepo, $larp, $users, count($characters));
        $io->writeln(sprintf('Player participants present: %d', count($playerParticipants)));

        $createdLinks = $this->linkCharactersToParticipantsIfMissing($characters, $playerParticipants);
        if ($createdLinks > 0) {
            $io->writeln(sprintf('Linked %d characters to player participants.', $createdLinks));
        }

        $createdApps = $this->ensureApplications($characters, $larp);
        $io->writeln(sprintf('Applications created this run: %d', $createdApps));

        $io->success('Seeding finished.');

        return Command::SUCCESS;
    }

    /**
     * Create or load deterministic users for this larp namespace.
     *
     * @return User[]
     */
    private function ensureUsers(ObjectRepository $userRepo, string $larpId, int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $username = sprintf('seed_%s_%03d', substr($larpId, 0, 8), $i + 1);
            /** @var User|null $user */
            $user = $userRepo->findOneBy(['username' => $username]);
            if (!$user) {
                $user = new User();
                $user->setUsername($username);
                $user->setContactEmail($username . '@example.test');
                $user->setRoles(['ROLE_USER']);
                $this->em->persist($user);
            }
            $users[] = $user;
        }
        $this->em->flush();

        return $users;
    }

    /**
     * Ensure exactly one participant per organizer role for the given LARP.
     *
     * @return LarpParticipant[] keyed by role value
     */
    private function ensureOrganizerParticipants(ObjectRepository $participantRepo, Larp $larp, array $users): array
    {
        $byRole = $this->getExistingRolesMap($participantRepo, $larp);

        $picker = $this->userPicker($users);
        $result = [];

        foreach (UserRole::getOrganizers() as $role) {
            $roleValue = $role->value;

            if (isset($byRole[$roleValue])) {
                $result[$roleValue] = $byRole[$roleValue];
                continue;
            }

            $p = new LarpParticipant();
            $p->setUser($picker());
            $p->setLarp($larp);
            $p->setRoles([$role]);
            $this->em->persist($p);
            $result[$roleValue] = $p;
        }

        $this->em->flush();

        return $result;
    }

    /**
     * Get all characters for the LARP.
     *
     * @return LarpCharacter[]
     */
    private function getLarpCharacters(ObjectRepository $characterRepo, Larp $larp): array
    {
        return $characterRepo->createQueryBuilder('c')
            ->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();
    }

    /**
     * Ensure we have at least N player participants for this LARP.
     * Returns the full current list of player participants (existing + newly created).
     *
     * @return LarpParticipant[]
     */
    private function ensurePlayerParticipantsForCharactersCount(ObjectRepository $participantRepo, Larp $larp, array $users, int $requiredCount): array
    {
        $existingPlayers = $participantRepo->createQueryBuilder('p')
            ->andWhere('p.larp = :larp')
            ->andWhere('JSONB_EXISTS(p.roles, :key) = TRUE') // or ->andWhere('JSONB_EXISTS(p.roles, :key)')
            ->setParameter('larp', $larp)
            ->setParameter('key', UserRole::PLAYER->value)
            ->getQuery()
            ->getResult();

        $toCreate = max(0, $requiredCount - count($existingPlayers));
        if ($toCreate === 0) {
            return $existingPlayers;
        }

        $picker = $this->userPicker($users);
        for ($i = 0; $i < $toCreate; $i++) {
            $p = new LarpParticipant();
            $p->setUser($picker());
            $p->setLarp($larp);
            $p->setRoles([UserRole::PLAYER]);
            $this->em->persist($p);
            $existingPlayers[] = $p;
        }
        $this->em->flush();

        return $existingPlayers;
    }

    /**
     * If a character lacks a participant, attach one in round-robin from provided players.
     * Returns number of links created.
     *
     * @param LarpCharacter[] $characters
     * @param LarpParticipant[] $playerParticipants
     */
    private function linkCharactersToParticipantsIfMissing(array $characters, array $playerParticipants): int
    {
        if ($playerParticipants === []) {
            return 0;
        }

        $idx = 0;
        $created = 0;
        foreach ($characters as $ch) {
            if ($ch->getLarpParticipant()) {
                continue;
            }
            $p = $playerParticipants[$idx % count($playerParticipants)];
            $idx++;

            $ch->setLarpParticipant($p);
            $p->addLarpCharacter($ch);

            $this->em->persist($ch);
            $this->em->persist($p);
            $created++;
        }

        if ($created > 0) {
            $this->em->flush();
        }

        return $created;
    }

    /**
     * Ensure one application per participant (linked via LarpParticipant::larpApplication).
     * Creates missing applications for participants associated to characters in the given LARP.
     *
     * Returns number of applications created this run.
     *
     * @param LarpCharacter[] $characters
     */
    private function ensureApplications(array $characters, Larp $larp): int
    {
        $created = 0;

        foreach ($characters as $ch) {
            $participant = $ch->getLarpParticipant();
            if (!$participant) {
                continue;
            }
            if ($participant->getLarpApplication()) {
                continue;
            }

            $app = new LarpApplication();
            $app->setLarp($larp);
            $app->setUser($participant->getUser());
            $app->setCreatedBy($participant->getUser());
            $participant->setLarpApplication($app);

            $this->em->persist($app);
            $this->em->persist($participant);
            $created++;
        }

        if ($created > 0) {
            $this->em->flush();
        }

        return $created;
    }

    /**
     * Build a map of role => existing participant, for the given LARP (first participant found with that role).
     *
     * @return array<string,LarpParticipant>
     */
    private function getExistingRolesMap(ObjectRepository $participantRepo, Larp $larp): array
    {
        /** @var LarpParticipant[] $all */
        $all = $participantRepo->createQueryBuilder('p')
            ->andWhere('p.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($all as $p) {
            foreach ($p->getRoles() as $roleEnum) {
                $roleValue = $roleEnum->value;
                if (!isset($map[$roleValue])) {
                    $map[$roleValue] = $p;
                }
            }
        }
        return $map;
    }

    /**
     * Simple round-robin user picker over provided list.
     *
     * @param User[] $users
     * @return callable(): User
     */
    private function userPicker(array $users): callable
    {
        $i = 0;
        $n = count($users);
        return static function () use (&$i, $n, $users) {
            $u = $users[$i % $n];
            $i++;
            return $u;
        };
    }
}
