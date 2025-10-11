<?php

namespace App\Tests\Controller;

use App\Controller\Backoffice\CharacterSubmissionsController;
use App\Entity\Larp;
use App\Entity\LarpApplication;
use App\Entity\LarpApplicationChoice;
use App\Entity\StoryObject\Character;
use App\Entity\User;
use App\Repository\LarpApplicationChoiceRepository;
use App\Repository\LarpApplicationRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use App\Service\Larp\LarpApplicationDashboardService;
use App\Service\Larp\SubmissionStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;



class TestController extends CharacterSubmissionsController
{
    public function __construct(
        TranslatorInterface $translator,
        FilterBuilderUpdaterInterface $filterBuilderUpdater,
        PaginatorInterface $paginator,
        EntityManagerInterface $entityManager,
        private readonly FormInterface $form
    ) {
        parent::__construct($translator, $filterBuilderUpdater, $paginator, $entityManager);
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        return $this->form;
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        return new Response('OK');
    }
}

class LarpCharacterSubmissionsControllerTest extends TestCase
{
    public function testMatchPageLoads(): void
    {
        $larp = new Larp();
        $user = new User();
        $application = new LarpApplication();
        $application->setUser($user);
        $application->setLarp($larp);

        $character = new Character();
        $character->setLarp($larp);

        $choice = new LarpApplicationChoice();
        $choice->setApplication($application);
        $choice->setCharacter($character);

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(LarpApplicationChoiceRepository::class);
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
        $query->method('getResult')->willReturn([$choice]);
        $qb->method('join')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $repository->method('createQueryBuilder')->willReturn($qb);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(LarpApplicationChoice::class)
            ->willReturn($repository);

        $controller = new TestController(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FilterBuilderUpdaterInterface::class),
            $this->createMock(PaginatorInterface::class),
            $em,
            $this->createMock(FormInterface::class)
        );

        $request = new Request();
        $response = $controller->match($request, $larp, $em, $repository);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testListFilteringInvokesFilterUpdater(): void
    {
        $larp = new Larp();
        $request = new Request();
        $repository = $this->createMock(LarpApplicationRepository::class);
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $repository->method('createQueryBuilder')->willReturn($qb);

        $filterUpdater = $this->createMock(FilterBuilderUpdaterInterface::class);
        $filterUpdater->expects($this->once())
            ->method('addFilterConditions');

        $statsRepo = $this->createMock(LarpApplicationRepository::class);
        $statsRepo->method('findBy')->willReturn([]);
        $preloader = new class($this->createMock(EntityManagerInterface::class)) extends \ShipMonk\DoctrineEntityPreloader\EntityPreloader {
            public function __construct(EntityManagerInterface $em) { parent::__construct($em); }
            public function preload(mixed $sourceEntities, string $sourcePropertyName, ?int $batchSize = null, ?int $maxFetchJoinSameFieldCount = null): array { return []; }
        };
        $statsService = new SubmissionStatsService($statsRepo, $preloader);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest');
        $form->method('createView');

        $controller = new TestController(
            $this->createMock(TranslatorInterface::class),
            $filterUpdater,
            $this->createMock(PaginatorInterface::class),
            $this->createMock(EntityManagerInterface::class),
            $form
        );

        $dashboardService = $this->createMock(LarpApplicationDashboardService::class);
        $dashboardService->method('getApplicationsWithPreloading')->willReturn([]);
        $dashboardService->method('getDashboardStats')->willReturn([]);

        $response = $controller->list($request, $larp, $repository, $dashboardService, $statsService);

        $this->assertInstanceOf(Response::class, $response);
    }
}
