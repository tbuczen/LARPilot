<?php

namespace App\Entity;

use App\Entity\Larp;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class ObjectFieldMapping implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    const TYPE_CHARACTER_LIST = 'character_list';

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    // Type of file (e.g., "character_list")
    #[ORM\Column(type: 'string')]
    private string $fileType;

    // External file identifier (e.g., Google Spreadsheet ID)
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $externalFileId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $mappingConfiguration = null;

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): self
    {
        $this->fileType = $fileType;
        return $this;
    }

    public function getExternalFileId(): ?string
    {
        return $this->externalFileId;
    }

    public function setExternalFileId(?string $externalFileId): self
    {
        $this->externalFileId = $externalFileId;
        return $this;
    }

    public function getMappingConfiguration(): ?array
    {
        return $this->mappingConfiguration;
    }

    public function setMappingConfiguration(?array $mappingConfiguration): self
    {
        $this->mappingConfiguration = $mappingConfiguration;
        return $this;
    }
}
