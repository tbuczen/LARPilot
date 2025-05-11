<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\StoryObject\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/autocomplete', name: 'backoffice_custom_autocomplete_')]
class AutocompleteController extends BaseController
{
    #[Route('create', name: 'create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Larp $larp, EntityManagerInterface $entityManager): Response
    {
        $title = $request->request->get('title') ?? $request->request->all()['attribute']['name'] ?? null;
        $type = $request->request->get('type');

        if (!$title || !$type) {
            return new JsonResponse(['error' => 'Missing title or type'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $targetType = TargetType::from($type);
            $class = $targetType->getEntityClass();
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'Invalid type'], Response::HTTP_BAD_REQUEST);
        }

        $repo = $entityManager->getRepository($class);
        $existing = $repo->findOneBy(['title' => $title, 'larp' => $larp]);

        if ($existing) {
            return new JsonResponse([
                'id' => $existing->getId(),
                'title' => $existing->getTitle(),
            ]);
        }

        /** @var StoryObject $object */
        $object = new $class();
        $object->setTitle($title);
        $object->setLarp($larp);
        $entityManager->persist($object);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $object->getId(),
            'title' => $object->getTitle(),
        ]);
    }

}