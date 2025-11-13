<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Form\Filter\TagFilterType;
use App\Domain\Core\Form\TagType;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\Core\Service\LarpManager;
use App\Domain\Core\UseCase\ImportTags\ImportTagsCommand;
use App\Domain\Core\UseCase\ImportTags\ImportTagsHandler;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Repository\ObjectFieldMappingRepository;
use App\Domain\Integrations\Service\IntegrationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/tag/', name: 'backoffice_larp_story_tag_')]
class TagController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, TagRepository $repository, LarpManager $larpManager): Response
    {
        $filterForm = $this->createForm(TagFilterType::class);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('t');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('t.' . $sort, $dir);
        $qb->andWhere('t.larp = :larp')->setParameter('larp', $larp);

        $integrations = $larpManager->getIntegrationsForLarp($larp);

        return $this->render('backoffice/larp/tag/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'tags' => $qb->getQuery()->getResult(),
            'larp' => $larp,
            'integrations' => $integrations,
        ]);
    }

    #[Route('{tag}', name: 'modify', defaults: ['tag' => null], methods: ['GET', 'POST'])]
    public function modify(Request $request, Larp $larp, TagRepository $tagRepository, ?Tag $tag = null): Response
    {
        if (!$tag instanceof Tag) {
            $tag = new Tag();
            $tag->setLarp($larp);
        }

        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tagRepository->save($tag);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_larp_story_tag_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/tag/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'tag' => $tag,
        ]);
    }

    #[Route('import', name: 'import', methods: ['GET'])]
    public function import(Larp $larp): Response
    {
        return $this->render('backoffice/larp/tag/import.html.twig', [
            'larp' => $larp,
        ]);
    }

    #[Route('import/file-select/{provider}', name: 'import_file_select', methods: ['GET'])]
    public function importFileSelect(
        Larp $larp,
        LarpIntegrationProvider $provider,
        ObjectFieldMappingRepository $mappingRepository
    ): Response {
        $files = $larp->getIntegrationByProvider($provider)?->getSharedFiles();
        $mappings = $mappingRepository->findBy(['larp' => $larp, 'fileType' => ResourceType::TAG_LIST]);

        return $this->render('backoffice/larp/tag/fileSelect.html.twig', [
            'larp' => $larp,
            'files' => $files,
            'mappings' => $mappings,
        ]);
    }

    #[Route('import/{provider}/{sharedFile}/{mapping}', name: 'import_from_mapping', methods: ['GET', 'POST'])]
    public function importFromSelectedMapping(
        Larp $larp,
        LarpIntegrationProvider $provider,
        SharedFile $sharedFile,
        ObjectFieldMapping $mapping,
        IntegrationManager $integrationManager,
        ImportTagsHandler $handler
    ): Response {
        $integrationService = $integrationManager->getService($provider);

        $rows = $integrationService->fetchSpreadsheetRows($sharedFile, $mapping);
        $additionalData = [
            'sheetId' => $integrationService->fetchSpreadsheetSheetIdByName($sharedFile, $mapping),
        ];
        $command = new ImportTagsCommand(
            $larp->getId()->toRfc4122(),
            $rows,
            $mapping->getMappingConfiguration(),
            $mapping->getMetaConfiguration(),
            $sharedFile->getId()->toRfc4122(),
            additionalFileData: $additionalData
        );
        $result = $handler->handle($command);

        if (!empty($result['skipped'])) {
            $this->addFlash('warning', $this->translator->trans('import.tags.skipped', [
                'count' => count($result['skipped']),
                'tags' => implode(', ', $result['skipped'])
            ]));
        }

        $this->addFlash('success', $this->translator->trans('import.tags.success'));

        return $this->redirectToRoute('backoffice_larp_story_tag_list', [
            'larp' => $larp->getId()->toRfc4122(),
        ]);
    }

    #[Route('import/{provider}', name: 'import_integration', methods: ['GET', 'POST'])]
    public function importFromIntegration(Larp $larp, LarpIntegrationProvider $provider): Response
    {
        return match ($provider) {
            default => $this->redirectToRoute('backoffice_larp_story_tag_import_file_select', [
                'larp' => $larp->getId()->toRfc4122(),
                'provider' => $provider->value
            ]),
        };
    }

    #[Route('{tag}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(Larp $larp, TagRepository $tagRepository, Tag $tag): Response
    {
        $tagRepository->remove($tag);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_tag_list', ['larp' => $larp->getId()]);
    }
}
