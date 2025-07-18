<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Domain\Larp\UseCase\ImportCharacters\ImportCharactersCommand;
use App\Domain\Larp\UseCase\ImportCharacters\ImportCharactersHandler;
use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Larp;
use App\Entity\ObjectFieldMapping;
use App\Entity\SharedFile;
use App\Entity\StoryObject\LarpCharacter;
use App\Form\CharacterType;
use App\Form\Filter\LarpCharacterFilterType;
use App\Helper\Logger;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

#[Route('/larp/{larp}/story/character/', name: 'backoffice_larp_story_character_')]
class LarpCharactersController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        Larp $larp,
        LarpManager $larpManager,
        LarpCharacterRepository $repository,
    ): Response {
        $filterForm = $this->createForm(LarpCharacterFilterType::class, options: ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $this->getListQueryBuilder($repository, $filterForm, $request, $larp);
        $pagination = $this->getPagination($qb, $request);
        $this->entityPreloader->preload($pagination->getItems(), 'factions');
        $this->entityPreloader->preload($pagination->getItems(), 'storyWriter');

        $integrations = $larpManager->getIntegrationsForLarp($larp);
        return $this->render('backoffice/larp/characters/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'larp' => $larp,
            'integrations' => $integrations,
            'characters' => $pagination,
        ]);
    }

    #[Route('{character}', name: 'modify', defaults: ['character' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Request                 $request,
        Larp                    $larp,
        LarpCharacterRepository $characterRepository,
        ?LarpCharacter          $character = null,
    ): Response {
        $new = false;
        if (!$character) {
            $character = new LarpCharacter();
            $character->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(CharacterType::class, $character, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $characterRepository->save($character);

            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $character);

            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_character_list', ['larp' => $larp->getId()]);
        }

        $this->entityPreloader->preload([$character], 'quests');
        $this->entityPreloader->preload([$character], 'threads');

        return $this->render('backoffice/larp/characters/modify.html.twig', [
            'character' => $character,
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('{character}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Larp                    $larp,
        Request                 $request,
        LarpCharacterRepository $characterRepository,
        LarpCharacter           $character,
    ): Response {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations) {
            if (!$this->removeStoryObjectFromIntegrations($larpManager, $larp, $integrationManager, $character, 'Character')) {
                return $this->redirectToRoute('backoffice_larp_story_character_list', [
                    'larp' => $larp->getId(),
                ]);
            }
        }

        $characterRepository->remove($character);

        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_character_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(string $id, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }

    #[Route('import/{provider}/select/file', name: 'import_file_select', methods: ['GET'])]
    public function selectIntegrationFile(
        Larp                    $larp,
        LarpManager             $larpManager,
        LarpIntegrationProvider $provider
    ): Response {
        $integration = $larpManager->getIntegrationTypeForLarp($larp, $provider);
        Assert::notNull($integration, sprintf('Integration %s not found for LARP %s', $provider->value, $larp->getId()->toRfc4122()));

        /** @var SharedFile[] $files */
        $files = $integration->getSharedFiles();
        return $this->render('backoffice/larp/characters/fileSelect.html.twig', [
            'larp' => $larp,
            'files' => $files,
        ]);
    }

    #[Route('import/{provider}/{sharedFile}/{mapping}', name: 'import_from_mapping', methods: ['GET', 'POST'])]
    public function importFromSelectedMapping(
        Larp                    $larp,
        LarpIntegrationProvider $provider,
        SharedFile              $sharedFile,
        ObjectFieldMapping      $mapping,
        IntegrationManager      $integrationManager,
        ImportCharactersHandler $handler
    ): Response {
        $integrationService = $integrationManager->getService($provider);

        $rows = $integrationService->fetchSpreadsheetRows($sharedFile, $mapping);
        $additionalData = [
            'sheetId' => $integrationService->fetchSpreadsheetSheetIdByName($sharedFile, $mapping),
        ];
        $command = new ImportCharactersCommand(
            $larp->getId()->toRfc4122(),
            $rows,
            $mapping->getMappingConfiguration(),
            $mapping->getMetaConfiguration(),
            $sharedFile->getId()->toRfc4122(),
            additionalFileData: $additionalData
        );
        $handler->handle($command);

        return $this->redirectToRoute('backoffice_larp_story_character_list', [
            'larp' => $larp->getId()->toRfc4122(),
        ]);
    }

    #[Route('import/{provider}', name: 'import_integration', methods: ['GET', 'POST'])]
    public function importFromIntegration(Larp $larp, LarpIntegrationProvider $provider): Response
    {
        return match ($provider) {
            default => $this->redirectToRoute('backoffice_larp_story_character_import_file_select', [
                'larp' => $larp->getId()->toRfc4122(),
                'provider' => $provider->value
            ]),
        };
    }
}
