<?php

namespace App\Controller\API;

use App\Entity\Larp;
use App\Repository\StoryObject\StoryObjectRepository;
use App\Security\Voter\Backoffice\Larp\LarpStoryVoter;
use App\Service\StoryObject\StoryObjectRouter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class StoryObjectMentionController extends AbstractController
{
    #[Route('/larp/{larp}/story-object/mention-search', name: 'backoffice_story_object_mention_search', methods: ['GET'])]
    public function __invoke(
        Request $request,
        Larp $larp,
        StoryObjectRepository $repository,
        StoryObjectRouter $router,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(LarpStoryVoter::VIEW, $larp);
        $query = $request->query->get('query', '');

        $objects = $repository->searchByTitle($larp, $query);
        $result = [];
        foreach ($objects as $object) {
            $result[] = [
                'id' => $object->getId()->toRfc4122(),
                'name' => $object->getTitle(),
                'type' => $object::getTargetType()->value,
                'url' => $router->getEditUrl($object, $larp),
            ];
        }
        return new JsonResponse($result);
    }
}
