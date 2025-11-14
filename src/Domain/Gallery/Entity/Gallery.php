<?php

namespace App\Domain\Gallery\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Gallery\Entity\Enum\GalleryVisibility;
use App\Domain\Gallery\Repository\GalleryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: GalleryRepository::class)]
class Gallery implements \Stringable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'galleries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Larp $larp = null;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?LarpParticipant $photographer = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $externalAlbumUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $zipDownloadUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipFile = null;

    #[ORM\Column(enumType: GalleryVisibility::class, options: ['default' => 'participants_only'])]
    private GalleryVisibility $visibility = GalleryVisibility::PARTICIPANTS_ONLY;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): static
    {
        $this->larp = $larp;
        return $this;
    }

    public function getPhotographer(): ?LarpParticipant
    {
        return $this->photographer;
    }

    public function setPhotographer(?LarpParticipant $photographer): static
    {
        $this->photographer = $photographer;
        return $this;
    }

    public function getExternalAlbumUrl(): ?string
    {
        return $this->externalAlbumUrl;
    }

    public function setExternalAlbumUrl(?string $externalAlbumUrl): static
    {
        $this->externalAlbumUrl = $externalAlbumUrl;
        return $this;
    }

    public function getZipDownloadUrl(): ?string
    {
        return $this->zipDownloadUrl;
    }

    public function setZipDownloadUrl(?string $zipDownloadUrl): static
    {
        $this->zipDownloadUrl = $zipDownloadUrl;
        return $this;
    }

    public function getZipFile(): ?string
    {
        return $this->zipFile;
    }

    public function setZipFile(?string $zipFile): static
    {
        $this->zipFile = $zipFile;
        return $this;
    }

    public function getZipFilePath(): ?string
    {
        if (!$this->zipFile) {
            return null;
        }
        return '/uploads/galleries/' . $this->getId() . '/' . $this->zipFile;
    }

    public function getVisibility(): GalleryVisibility
    {
        return $this->visibility;
    }

    public function setVisibility(GalleryVisibility $visibility): static
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
