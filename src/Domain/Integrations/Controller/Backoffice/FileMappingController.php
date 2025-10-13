<?php

namespace App\Domain\Integrations\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Form\Integrations\FileMappingType;
use App\Domain\Integrations\Form\Models\ExternalResourceMappingModel;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\IntegrationManager;
use App\Domain\Integrations\UseCase\SaveFileMapping\SaveFileMappingCommand;
use App\Domain\Integrations\UseCase\SaveFileMapping\SaveFileMappingHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'backoffice_larp_integration_')]
class FileMappingController extends BaseController
{
    #[Route(
        '/integration/{provider}/file/{sharedFile}/mapping/{mapping}',
        name: 'file_mapping',
        defaults: ['mapping' => null]
    )]
    public function mappingConfiguration(
        Larp                  $larp,
        LarpIntegrationProvider $provider,
        SharedFile              $sharedFile,
        Request                 $request,
        SaveFileMappingHandler  $handler,
        ?ObjectFieldMapping     $mapping = null,
    ): Response {
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
    ): RedirectResponse {
        $integration = $larpIntegrationRepository->findByLarpAndProvider($larp->getId()->toRfc4122(), $provider);
        $integrationService = $integrationManager->getService($integration);
        $url = $integrationService->getExternalFileUrl($integration, $externalFileId);

        return new RedirectResponse($url);
    }
}
