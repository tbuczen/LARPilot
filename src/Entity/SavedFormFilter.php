<?php

namespace App\Entity;

use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\LarpAwareInterface;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\SavedFormFilterRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;


#[ORM\Entity(repositoryClass: SavedFormFilterRepository::class)]
class SavedFormFilter implements CreatorAwareInterface, Timestampable, LarpAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Larp $larp = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100)]
    private string $formName;

    #[ORM\Column(type: 'json')]
    private array $parameters = [];

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function setFormName(string $formName): self
    {
        $this->formName = $formName;
        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): void
    {
        $this->larp = $larp;
    }
}