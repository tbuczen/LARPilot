<?php

namespace App\Entity;

use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Enum\FileMappingType;
use App\Repository\ObjectFieldMappingRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ObjectFieldMappingRepository::class )]
class ObjectFieldMapping implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\Column(type: 'string',  enumType: FileMappingType::class)]
    private FileMappingType $fileType;

    #[ORM\ManyToOne(targetEntity: SharedFile::class, inversedBy: 'mappings')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SharedFile $externalFile = null;

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

    public function getFileType(): FileMappingType
    {
        return $this->fileType;
    }

    public function setFileType(FileMappingType $fileType): self
    {
        $this->fileType = $fileType;
        return $this;
    }

    public function getExternalFile(): ?SharedFile
    {
        return $this->externalFile;
    }

    public function setExternalFile(?SharedFile $file): void
    {
        $this->externalFile = $file;
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
