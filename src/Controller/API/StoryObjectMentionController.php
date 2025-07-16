<?php

namespace App\Controller\API;

use App\Entity\Larp;
use App\Helper\Logger;
use App\Repository\StoryObject\StoryObjectRepository;
use App\Security\Voter\Backoffice\Larp\LarpStoryVoter;
use App\Service\StoryObject\StoryObjectRouter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Controller for searching story objects for mentions in the WYSIWYG editor
 * Only accessible to users with appropriate permissions for the specific LARP
 */
class StoryObjectMentionController extends AbstractController
{
    /**
     * Search for story objects by title for mention autocomplete
     * 
     * @param Request $request The request object
     * @param Larp $larp The LARP object (automatically resolved by param converter)
     * @param StoryObjectRepository $repository Repository for story objects
     * @param StoryObjectRouter $router Router for generating edit URLs
     * @return JsonResponse
     * 
     * @throws AccessDeniedException If user doesn't have access to this LARP
     */
    #[Route('/larp/{larp}/story-object/mention-search', name: 'backoffice_story_object_mention_search', methods: ['GET'])]
    public function __invoke(
        Request $request,
        Larp $larp,
        StoryObjectRepository $repository,
        StoryObjectRouter $router,
    ): JsonResponse {
        // Check if user is logged in and has access to this LARP
        if (!$this->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('You must be logged in to access this resource');
        }

        $this->denyAccessUnlessGranted(LarpStoryVoter::VIEW, $larp);

        $query = trim($request->query->get('query', ''));

        // Validate minimum query length
        if (strlen($query) < 1) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        try {
            // Get matching story objects (limit to 10 results)
            $objects = $repository->searchByTitle($larp, $query, 10);

            $result = [];
            foreach ($objects as $object) {
                $result[] = [
                    'id' => $object->getId()->toRfc4122(),
                    'name' => $object->getTitle(),
                    'type' => $object::getTargetType()->value,
                    'url' => $router->getEditUrl($object, $larp),
                ];
            }

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log the error
            Logger::get()->error('Error searching for story objects: ' . $e->getMessage());

            // Return an empty result in case of error
            return new JsonResponse(
                ['error' => 'An error occurred while searching for story objects'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
