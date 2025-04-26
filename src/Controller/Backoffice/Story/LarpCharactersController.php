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
use App\Helper\Logger;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

#[Route('/larp', name: 'backoffice_larp_story_characters_')]
class LarpCharactersController extends BaseController
{
    #[Route('/{larp}/story/characters', name: 'list', methods: ['GET', 'POST'])]
    public function list(Larp $larp, LarpManager $larpManager): Response
    {
        $integrations = $larpManager->getIntegrationsForLarp($larp);
        $characters = $larp->getCharacters();
        return $this->render('backoffice/larp/characters/list.html.twig', [
            'larp' => $larp,
            'integrations' => $integrations,
            'characters' => $characters,
        ]);
    }

    #[Route('/{larp}/story/character/{character}', name: 'modify', defaults: ['character' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Request                 $request,
        Larp                    $larp,
        LarpCharacterRepository $characterRepository,
        ?LarpCharacter          $character = null,
    ): Response
    {

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

            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    if ($new) {
                        $integrationService->createStoryObject($integration, $character);
                    } else {
                        $integrationService->syncStoryObject($integration, $character);
                    }
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('warning', 'Failed to sync with ' . $integration->getProvider()->name);
                }
            }

            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_characters_list', ['larp' => $larp->getId()]);
        }

//        if ($form->isSubmitted()) {
//            $this->showErrorsAsFlash($form->getErrors(true));
//        }

        return $this->render('backoffice/larp/characters/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('/{larp}/story/character/{character}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Larp                    $larp,
        Request                 $request,
        LarpCharacterRepository $characterRepository,
        LarpCharacter           $character,
    ): Response
    {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations) {
            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    $integrationService->removeStoryObject($integration, $character);
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('danger', 'Failed to remove from ' . $integration->getProvider()->name . '. Character not deleted.');
                    return $this->redirectToRoute('backoffice_larp_story_characters_list', [
                        'larp' => $larp->getId(),
                    ]);
                }
            }
        }

        $characterRepository->remove($character);

        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_characters_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('/{id}/story/characters/import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(string $id, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }

    #[Route('/{larp}/story/characters/import/{provider}/select/file', name: 'import_file_select', methods: ['GET'])]
    public function selectIntegrationFile(
        Larp                    $larp,
        LarpManager             $larpManager,
        LarpIntegrationProvider $provider
    ): Response
    {
        $integration = $larpManager->getIntegrationTypeForLarp($larp, $provider);
        Assert::notNull($integration, sprintf('Integration %s not found for LARP %s', $provider->value, $larp->getId()->toRfc4122()));

        /** @var SharedFile[] $files */
        $files = $integration->getSharedFiles();
        return $this->render('backoffice/larp/characters/fileSelect.html.twig', [
            'larp' => $larp,
            'files' => $files,
        ]);
    }

    #[Route('/{larp}/story/characters/import/{provider}/{sharedFile}/{mapping}', name: 'import_from_mapping', methods: ['GET', 'POST'])]
    public function importFromSelectedMapping(
        Larp                    $larp,
        LarpIntegrationProvider $provider,
        SharedFile              $sharedFile,
        ObjectFieldMapping      $mapping,
        IntegrationManager      $integrationManager,
        ImportCharactersHandler $handler
    ): Response
    {
        $integrationService = $integrationManager->getService($provider);

        $rows = $integrationService->fetchSpreadsheetRows($sharedFile, $mapping);
        $command = new ImportCharactersCommand(
            $larp->getId()->toRfc4122(),
            $rows,
            $mapping->getMappingConfiguration(),
            $mapping->getMetaConfiguration(),
            $sharedFile->getId()->toRfc4122()
        );
        $handler->handle($command);

        return $this->redirectToRoute('backoffice_larp_story_characters_list', [
            'larp' => $larp->getId()->toRfc4122(),
        ]);
    }

    #[Route('/{larp}/story/characters/import/{provider}', name: 'import_integration', methods: ['GET', 'POST'])]
    public function importFromIntegration(Larp $larp, LarpIntegrationProvider $provider): Response
    {
        return match ($provider) {
            default => $this->redirectToRoute('backoffice_larp_story_characters_import_file_select', [
                'larp' => $larp->getId()->toRfc4122(),
                'provider' => $provider->value
            ]),
        };

    }
}