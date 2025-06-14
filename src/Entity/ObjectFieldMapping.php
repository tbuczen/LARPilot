<?php

namespace App\Entity;

use App\Entity\Enum\ResourceType;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\ObjectFieldMappingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ObjectFieldMappingRepository::class)]
class ObjectFieldMapping implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\Column(type: 'string', enumType: ResourceType::class)]
    private ResourceType $fileType;

    #[ORM\ManyToOne(targetEntity: SharedFile::class, inversedBy: 'mappings')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SharedFile $externalFile = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $mappingConfiguration = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $metaConfiguration = null;

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getFileType(): ResourceType
    {
        return $this->fileType;
    }

    public function setFileType(ResourceType $fileType): self
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

    public function getMetaConfiguration(): ?array
    {
        return $this->metaConfiguration;
    }

    public function setMetaConfiguration(?array $metaConfiguration): void
    {
        $this->metaConfiguration = $metaConfiguration;
    }
}
