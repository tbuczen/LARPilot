<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Repository\SharedFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SharedFileRepository::class)]
class SharedFile
{

    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\Column(type: 'string')]
    private string $fileId;

    #[ORM\Column(type: 'string')]
    private string $fileName;

    #[ORM\ManyToOne(targetEntity: LarpIntegration::class, inversedBy: 'sharedFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private LarpIntegration $integration;

    #[ORM\Column(type: 'json')]
    private array $metadata;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $permissionType;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $mimeType;

    #[ORM\Column(type: 'string', nullable: true)]
    private string $url;

    #[ORM\OneToMany(targetEntity: ObjectFieldMapping::class, mappedBy: 'externalFile')]
    private Collection $mappings;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->mappings = new ArrayCollection();
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function setFileId(string $fileId): void
    {
        $this->fileId = $fileId;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getIntegration(): LarpIntegration
    {
        return $this->integration;
    }

    public function setIntegration(LarpIntegration $integration): void
    {
        $this->integration = $integration;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getPermissionType(): string
    {
        return $this->permissionType;
    }

    public function setPermissionType(string $permissionType): void
    {
        $this->permissionType = $permissionType;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @return Collection<ObjectFieldMapping>
     */
    public function getMappings(): Collection
    {
        return $this->mappings;
    }

    public function setMappings(Collection $mappings): void
    {
        $this->mappings = $mappings;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

}
