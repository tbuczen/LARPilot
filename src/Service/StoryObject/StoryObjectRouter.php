<?php

namespace App\Service\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\StoryObject\StoryObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class StoryObjectRouter
{
    private const ROUTE_MAP = [
        TargetType::Character->value => ['backoffice_larp_story_character_modify', 'character'],
        TargetType::Faction->value => ['backoffice_larp_story_faction_modify', 'faction'],
        TargetType::Thread->value => ['backoffice_larp_story_thread_modify', 'thread'],
        TargetType::Event->value => ['backoffice_larp_story_event_modify', 'event'],
        TargetType::Quest->value => ['backoffice_larp_story_quest_modify', 'quest'],
        TargetType::Item->value => ['backoffice_larp_story_item_modify', 'item'],
        TargetType::Place->value => ['backoffice_larp_story_place_modify', 'place'],
    ];

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getEditUrl(StoryObject $object, Larp $larp): ?string
    {
        $type = $object::getTargetType()->value;
        if (!isset(self::ROUTE_MAP[$type])) {
            return null;
        }
        [$route, $param] = self::ROUTE_MAP[$type];

        return $this->urlGenerator->generate($route, [
            'larp' => $larp->getId(),
            $param => $object->getId(),
        ]);
    }
}
