<?php

namespace App\Domain\StoryObject\Entity;

use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Repository\PlaceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlaceRepository::class)]
class Place extends StoryObject
{
    public function __construct()
    {
        parent::__construct();
    }


    public static function getTargetType(): TargetType
    {
        return TargetType::Place;
    }
}
