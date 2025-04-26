<?php

namespace App\Entity\StoryObject;


use App\Entity\Larp;
use App\Repository\StoryObject\QuestRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TargetType;

#[ORM\Entity(repositoryClass: QuestRepository::class)]

class Quest extends StoryObject
{

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Larp $larp;

    #[ORM\ManyToOne(targetEntity: Thread::class, inversedBy: 'quests')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Thread $thread = null;

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): void
    {
        $this->thread = $thread;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Quest;
    }


}