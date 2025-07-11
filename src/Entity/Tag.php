<?php

namespace App\Entity;

use App\Entity\Enum\TargetType;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/*
* ambicja
autorytet
cwaniactwo
depresja
dylemat
fatum
honor
interesy
mag ? moc nadprzyrodzona
bogactwo
ubóstwo
mędrzec
głupota
odwaga
przywództwo
misja
młodość
nielubiany
odpowiedzialność
przyjaźń
religijny
rodzina
romans
sekret
starośc
trudność
uprzedzenia
weteran
wojsko
wstyd
zatarg
zbrodnia
polityka
przemoc
lojalność

*/
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag implements CreatorAwareInterface, Timestampable, TargetableInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\Column(type: 'string', enumType: TargetType::class)]
    private ?TargetType $target = null;

    #[ORM\Column(length: 100)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $description;

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): void
    {
        $this->larp = $larp;
    }

    public function getTarget(): ?TargetType
    {
        return $this->target;
    }

    public function setTarget(?TargetType $target): void
    {
        $this->target = $target;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Tag;
    }
}
