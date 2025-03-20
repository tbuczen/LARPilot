<?php

namespace App\Controller\Backoffice\Story;

use App\Enum\LarpIntegrationProvider;
use App\Service\Integrations\IntegrationServiceProvider;
use App\Service\Larp\LarpManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_story_characters_')]

class LarpCharactersController extends AbstractController
{
    #[Route('/{id}/story/characters', name: 'list', methods: ['GET', 'POST'])]
    public function list(string $id, LarpManager $larpManager): Response
    {
        $larp = $larpManager->getLarp($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        $integrations = $larpManager->getIntegrationsForLarp($id);

        return $this->render('backoffice/larp/characters/list.html.twig', [
            'larp' => $larp,
            'integrations' => $integrations,
        ]);
    }


    #[Route('/{id}/story/characters/import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(string $id, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }

    #[Route('/{id}/story/characters/import/{integration}/select/file', name: 'import_file_select', methods: ['GET'])]
    public function selectIntegrationFile(
        string $id,
        LarpManager $larpManager,
        IntegrationServiceProvider $integrationServiceProvider,
        LarpIntegrationProvider $integration
    ): Response {
        $larp = $larpManager->getLarp($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        // Get the Google integration for the current LARP.
        $integration = $larpManager->getIntegrationTypeForLarp($id, $integration);
        if (!$integration) {
            throw new \Exception('Google integration not configured for this LARP.');
        }

        $service = $integrationServiceProvider->getServiceForIntegration($integration->getProvider());

        $files = $service->listSpreadsheets($integration);

        return $this->render('backoffice/larp/characters/file_select.html.twig', [
            'larp' => $larp,
            'files' => $files,
        ]);
    }

    #[Route('/{id}/story/characters/import/{integration}', name: 'import_integration', methods: ['GET', 'POST'])]
    public function importFromIntegration(string $id, LarpManager $larpManager, LarpIntegrationProvider $integration): Response
    {

        $larp = $larpManager->getLarp($id);
        if (!$larp) {
            throw $this->createNotFoundException('Larp not found.');
        }

        //TODO:: Import from integration
        return match ($integration) {
            default => $this->redirectToRoute('backoffice_larp_story_characters_import_file_select', [
                'id' => $id,
                'integration' => $integration->value
            ]),
        };

    }
}