<?php

namespace App\Tests\Domain\Application\Controller;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CharacterApplicationsControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testMatchPageLoads(): void
    {
        // Create test data with unique identifiers
        $uniqueId = uniqid('match_', true);
        $user = $this->createUser('user_' . $uniqueId, $uniqueId . '@example.com');
        $larp = $this->createLarp('Test LARP ' . $uniqueId, $user);
        $character = $this->createCharacter($larp, 'Test Character', $user);
        $application = $this->createApplication($larp, $user);
        $this->createApplicationChoice($application, $character);

        $this->em->flush();

        // Login as user
        $this->client->loginUser($user);

        // Make request to match page
        $this->client->request('GET', sprintf('/backoffice/larp/%s/applications/match', $larp->getId()));

        // Assert response is successful
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Character');
    }

    public function testListPageShowsApplications(): void
    {
        // Create test data with unique identifiers
        $uniqueId = uniqid('list_', true);
        $user = $this->createUser('user_' . $uniqueId, $uniqueId . '@example.com');
        $larp = $this->createLarp('Test LARP ' . $uniqueId, $user);
        $character = $this->createCharacter($larp, 'Character Name', $user);
        $application = $this->createApplication($larp, $user);
        $this->createApplicationChoice($application, $character);

        $this->em->flush();

        // Login as user
        $this->client->loginUser($user);

        // Make request to list page
        $this->client->request('GET', sprintf('/backoffice/larp/%s/applications', $larp->getId()));

        // Assert response is successful
        $this->assertResponseIsSuccessful();
    }

    private function createUser(string $username, string $email): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setContactEmail($email);
        $user->setRoles(['ROLE_USER']);
        $this->em->persist($user);
        return $user;
    }

    private function createLarp(string $title, User $createdBy): Larp
    {
        $larp = new Larp();
        $larp->setTitle($title);
        $larp->setDescription('Test Description');
        $larp->setStartDate(new \DateTime('2025-01-01'));
        $larp->setEndDate(new \DateTime('2025-01-03'));
        $larp->setStatus(LarpStageStatus::DRAFT);
        $larp->setCreatedBy($createdBy);
        $this->em->persist($larp);
        return $larp;
    }

    private function createCharacter(Larp $larp, string $title, User $createdBy): Character
    {
        $character = new Character();
        $character->setLarp($larp);
        $character->setTitle($title);
        $character->setCreatedBy($createdBy);
        $this->em->persist($character);
        return $character;
    }

    private function createApplication(Larp $larp, User $user): LarpApplication
    {
        $application = new LarpApplication();
        $application->setLarp($larp);
        $application->setUser($user);
        $application->setCreatedBy($user);
        $this->em->persist($application);
        return $application;
    }

    private function createApplicationChoice(LarpApplication $application, Character $character): LarpApplicationChoice
    {
        $choice = new LarpApplicationChoice();
        $choice->setApplication($application);
        $choice->setCharacter($character);
        $choice->setPriority(1);
        $this->em->persist($choice);
        return $choice;
    }
}
