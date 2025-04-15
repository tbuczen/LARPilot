<?php

namespace App\Controller\Backoffice\Integrations;

use App\Controller\Backoffice\BaseBackofficeController;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_integration_')]
class FileMappingController extends BaseBackofficeController
{
    #[Route('/{id}/integration/{provider}/file/{sharedFile}/mapping/{mapping}', name: 'file_mapping', defaults: ['mapping' => null]
    )]
    public function mappingConfiguration(
        string                  $id,
        LarpIntegrationProvider $provider,
        SharedFile              $sharedFile,
        Request                 $request,
        SaveFileMappingHandler  $handler,
        ?ObjectFieldMapping     $mapping = null,
    ): Response
    {
        $mappingModel = SpreadsheetMappingModel::fromEntity($mapping);
        $form = $this->createForm(SpreadsheetMappingType::class, $mappingModel);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SpreadsheetMappingModel $data */
            $data = $form->getData();

            $mappings = [
                'sheetName' => $data->sheetName,
                'startingRow' => $data->startingRow,
                'endColumn' => $data->endColumn,
            ];

            $command = new SaveFileMappingCommand(
                $id,
                $provider->value,
                $data->mappingType->value,
                $sharedFile->getId()->toRfc4122(),
                array_merge([ 'columnMappings' => $data->columnMappings ], $mappings),
            );

            $handler($command);

            return $this->redirectToRoute('backoffice_larp_integration_file_mapping', [
                'id' => $id,
                'provider' => $provider->value,
                'sharedFile' => $sharedFile->getId()->toRfc4122(),
            ]);
        }

        if ($form->isSubmitted()) {
            $this->showErrorsAsFlash($form->getErrors(true));
        }

        return $this->render('backoffice/larp/integrations/spreadsheet_mapping.html.twig', [
            'form' => $form,
            'larpId' => $id,
            'provider' => $provider,
            'sharedFileId' => $sharedFile->getId()->toRfc4122(),
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
