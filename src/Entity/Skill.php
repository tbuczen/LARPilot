<?php

namespace App\Entity;

use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class Skill implements CreatorAwareInterface, Timestampable
{

    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private string $description;

}