<?php

namespace App\Entity\StoryObject;

use App\Entity\Larp;
use App\Repository\StoryObject\PlaceRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TargetType;

#[ORM\Entity(repositoryClass: PlaceRepository::class)]
class Place extends StoryObject
{

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'places')]
    #[ORM\JoinColumn(nullable: false)]
    protected Larp $larp;
    
    public function __construct()
    {
        parent::__construct();
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): void
    {
        $this->larp = $larp;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Place;
    }
}