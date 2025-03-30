<?php

namespace App\Controller\Backoffice\Integrations;

use App\Domain\Integrations\UseCase\SaveFileMapping\SaveFileMappingCommand;
use App\Domain\Integrations\UseCase\SaveFileMapping\SaveFileMappingHandler;
use App\Entity\LarpIntegration;
use App\Entity\ObjectFieldMapping;
use App\Entity\SharedFile;
use App\Enum\FileMappingType;
use App\Enum\LarpIntegrationProvider;
use App\Form\Integrations\SpreadsheetMappingType;
use App\Form\Models\SpreadsheetMappingModel;
use App\Repository\LarpIntegrationRepository;
use App\Service\Integrations\LarpIntegrationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_integration_')]
class FileMappingController extends AbstractController
{
    #[Route('/{id}/integration/{provider}/file/{sharedFile}/mapping/{mapping}', name: 'file_mapping', defaults: ['mapping' => null]
    )]
    public function mappingConfiguration(
        string                  $id,
        LarpIntegrationProvider $provider,
        SharedFile              $sharedFile,
        Request                 $request,
        SaveFileMappingHandler  $handler,
        ?ObjectFieldMapping                  $mapping = null,
    ): Response
    {
        $formModel = SpreadsheetMappingModel::fromEntity($mapping);

        $form = $this->createForm(SpreadsheetMappingType::class, $formModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SpreadsheetMappingModel $data */
            $data = $form->getData();
            $command = new SaveFileMappingCommand(
                $id,
                $provider->value,
                $data->mappingType->value,
                $sharedFile->getId()->toRfc4122(),
                [
                    'startingRow' => $data->startingRow,
                    'factionColumn' => $data->factionColumn,
                    'characterNameColumn' => $data->characterNameColumn,
                    'inGameNameColumn' => $data->inGameNameColumn,
                ]
            );

            $handler($command);

            if ($data->mappingType === FileMappingType::CHARACTER_LIST) {
                return $this->redirectToRoute('backoffice_larp_story_characters_import_integration', [
                    'larp' => $id,
                    'provider' => $provider->value,
                ]);
            }
        }

        return $this->render('backoffice/larp/integrations/spreadsheet_mapping.html.twig', [
            'form' => $form->createView(),
            'larpId' => $id,
        ]);
    }


    #[Route('/{id}/integration/{provider}/file/{externalFileId}/preview', name: 'preview_spreadsheet')]
    public function previewSpreadsheet(string $id, LarpIntegrationProvider $provider, string $externalFileId, Request $request): Response
    {
        return new Response('TODO: previewSpreadsheet');
    }

    #[Route('/{id}/integration/{provider}/file/{externalFileId}/open', name: 'file_open')]
    public function openExternalFile(
        string                    $id,
        string                    $externalFileId,
        LarpIntegrationProvider   $provider,
        LarpIntegrationManager    $integrationManager,
        LarpIntegrationRepository $larpIntegrationRepository
    ): RedirectResponse
    {
        $integrationService = $integrationManager->getIntegrationServiceByProvider($provider);
        $integration = $larpIntegrationRepository->findByLarpAndProvider($id, $provider);
        $url = $integrationService->getExternalFileUrl($integration, $externalFileId);

        return new RedirectResponse($url);
    }
}
