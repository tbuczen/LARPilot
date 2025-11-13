<?php

namespace App\Domain\StoryObject\Controller\API;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\Helper\Logger;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use App\Domain\StoryObject\Security\Voter\LarpStoryVoter;
use App\Domain\StoryObject\Service\StoryObjectRouter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
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
     * @param CacheItemPoolInterface $cache PSR-6 cache pool
     * @return JsonResponse
     *
     * @throws AccessDeniedException|InvalidArgumentException If user doesn't have access to this LARP
     */
    #[Route('/larp/{larp}/story-object/mention-search', name: 'backoffice_story_object_mention_search', methods: ['GET'])]
    public function __invoke(
        Request $request,
        Larp $larp,
        StoryObjectRepository $repository,
        StoryObjectRouter $router,
        CacheItemPoolInterface $cache,
    ): JsonResponse {
        if (!$this->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('You must be logged in to access this resource');
        }

        $this->denyAccessUnlessGranted(LarpStoryVoter::VIEW, $larp);

        $query = trim($request->query->get('query', ''));

        if (strlen($query) < 1) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        try {
            // Build cache key (per-larp, per-query); keep it short/safe
            $cacheKey = sprintf(
                'mention_search_%s_%s',
                $larp->getId()->toRfc4122(),
                substr(sha1(mb_strtolower($query)), 0, 16)
            );

            $grouped = $cache->getItem($cacheKey);
            if (!$grouped->isHit()) {
                // Get matching story objects (limit to 10 results)
                $objects = $repository->searchByTitle($larp, $query, 10);

                // Group by type for client-side grouping UI
                $byType = [];
                foreach ($objects as $object) {
                    $type = $object::getTargetType()->value;
                    $byType[$type] ??= [];
                    $byType[$type][] = [
                        'id' => $object->getId()->toRfc4122(),
                        'name' => $object->getTitle(),
                        'type' => $type,
                        'url' => $router->getEditUrl($object, $larp),
                    ];
                }

                // Transform to a stable grouped array format
                // Example:
                // [
                //   { "type": "character", "items": [ ... ] },
                //   { "type": "faction", "items": [ ... ] }
                // ]
                $result = [];
                foreach ($byType as $type => $items) {
                    $result[] = [
                        'type' => $type,
                        'items' => $items,
                    ];
                }

                $grouped->set($result);
                // Short TTL; tune as needed (e.g., 60-300 seconds)
                $grouped->expiresAfter(120);
                $cache->save($grouped);
            }

            return new JsonResponse($grouped->get(), Response::HTTP_OK);
        } catch (\Exception $e) {
            Logger::get()->error('Error searching for story objects: ' . $e->getMessage());

            return new JsonResponse(
                ['error' => 'An error occurred while searching for story objects'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
