<?php

namespace App\Controller\Backoffice\Integrations;

use App\Controller\BaseController;
use App\Domain\Integrations\UseCase\SaveFileMapping\SaveFileMappingCommand;
use App\Domain\Integrations\UseCase\SaveFileMapping\SaveFileMappingHandler;
use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Larp;
use App\Entity\ObjectFieldMapping;
use App\Entity\SharedFile;
use App\Form\Integrations\FileMappingType;
use App\Form\Models\ExternalResourceMappingModel;
use App\Repository\LarpIntegrationRepository;
use App\Service\Integrations\IntegrationManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'backoffice_larp_integration_')]
class FileMappingController extends BaseController
{
    #[Route('/integration/{provider}/file/{sharedFile}/mapping/{mapping}', name: 'file_mapping', defaults: ['mapping' => null]
    )]
    public function mappingConfiguration(
        Larp                  $larp,
        LarpIntegrationProvider $provider,
        SharedFile              $sharedFile,
        Request                 $request,
        SaveFileMappingHandler  $handler,
        ?ObjectFieldMapping     $mapping = null,
    ): Response
    {
        $mappingModel = ExternalResourceMappingModel::fromEntity($mapping);
        $form = $this->createForm(FileMappingType::class, $mappingModel, ['mimeType' => $sharedFile->getMimeType()]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ExternalResourceMappingModel $data */
            $data = $form->getData();
            $command = new SaveFileMappingCommand(
                $larp->getId()->toRfc4122(),
                $provider->value,
                $data->mappingType->value,
                $sharedFile->getId()->toRfc4122(),
                $data->mappings,
                $data->meta,
            );

            $handler($command);

            return $this->redirectToRoute('backoffice_larp_integration_file_mapping', [
                'larp' => $larp->getId()->toRfc4122(),
                'provider' => $provider->value,
                'sharedFile' => $sharedFile->getId()->toRfc4122(),
            ]);
        }

        if ($form->isSubmitted()) {
            $this->showErrorsAsFlash($form->getErrors(true));
        }

        return $this->render('backoffice/larp/integrations/fileMapping.html.twig', [
            'form' => $form,
            'larp' => $larp,
            'provider' => $provider,
            'sharedFile' => $sharedFile,
        ]);
    }

    #[Route('/integration/{provider}/file/{externalFileId}/preview', name: 'preview_spreadsheet')]
    public function previewSpreadsheet(Larp $larp, LarpIntegrationProvider $provider, string $externalFileId, Request $request): Response
    {
        return new Response('TODO: previewSpreadsheet');
    }

    #[Route('/integration/{provider}/file/{externalFileId}/open', name: 'file_open')]
    public function openExternalFile(
        Larp                    $larp,
        string                    $externalFileId,
        LarpIntegrationProvider   $provider,
        IntegrationManager        $integrationManager,
        LarpIntegrationRepository $larpIntegrationRepository
    ): RedirectResponse
    {
        $integration = $larpIntegrationRepository->findByLarpAndProvider($larp->getId()->toRfc4122(), $provider);
        $integrationService = $integrationManager->getService($integration);
        $url = $integrationService->getExternalFileUrl($integration, $externalFileId);

        return new RedirectResponse($url);
    }
}
