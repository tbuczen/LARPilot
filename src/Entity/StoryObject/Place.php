<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Repository\StoryObject\PlaceRepository;
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
