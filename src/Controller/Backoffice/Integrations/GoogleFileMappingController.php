<?php

namespace App\Controller\Backoffice\Integrations;

use App\Form\Integrations\Google\GoogleSpreadsheetMappingType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_integration_google_')]
class GoogleFileMappingController extends AbstractController
{
    #[Route('/{id}/file/{externalFileId}/mapping', name: 'file_mapping')]
    public function mappingConfiguration(string $id, string $externalFileId, Request $request): Response
    {
        // Default mapping values – these could be replaced by stored values if available
        $defaultData = [
            'startingRow'         => 3,
            'factionColumn'       => 'C',
            'characterNameColumn' => 'D',
            'inGameNameColumn'    => 'N',
        ];
        $form = $this->createForm(GoogleSpreadsheetMappingType::class, $defaultData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $mappingData = $form->getData();
            // For now, store the mapping data in session. Later, persist it in the DB per file.
            $session = $request->getSession();
            $session->set('spreadsheet_mapping_' . $id, $mappingData);

            // Redirect to a preview page (to be implemented in later steps)
            return $this->redirectToRoute('backoffice_larp_integration_google_preview_spreadsheet', ['id' => $id]);
        }

        return $this->render('backoffice/larp/integrations/spreadsheet_mapping.html.twig', [
            'form'   => $form->createView(),
            'larpId' => $id,
        ]);
    }


    #[Route('/{id}/file/mapping', name: 'preview_spreadsheet')]
    public function previewSpreadsheet(string $id, Request $request): Response
    {
        return new Response('TODO: previewSpreadsheet');
    }
}
