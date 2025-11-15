<?php

namespace App\Domain\Core\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Core\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location implements Timestampable, CreatorAwareInterface, \Stringable
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $twitter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $images = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $facilities = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $accessibility = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $parkingInfo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $publicTransport = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isPublic = false;

    #[ORM\Column(enumType: LocationApprovalStatus::class, options: ['default' => 'pending'])]
    private LocationApprovalStatus $approvalStatus = LocationApprovalStatus::PENDING;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $approvedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    /** @var Collection<Larp> */
    #[ORM\OneToMany(targetEntity: Larp::class, mappedBy: 'location')]
    private Collection $larps;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->larps = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): self
    {
        $this->facebook = $facebook;
        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;
        return $this;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): self
    {
        $this->twitter = $twitter;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images ?? [];
    }

    public function setImages(?array $images): self
    {
        $this->images = $images;
        return $this;
    }

    public function addImage(string $imagePath): self
    {
        $images = $this->getImages();
        if (!in_array($imagePath, $images, true)) {
            $images[] = $imagePath;
            $this->setImages($images);
        }
        return $this;
    }

    public function removeImage(string $imagePath): self
    {
        $images = $this->getImages();
        $key = array_search($imagePath, $images, true);
        if ($key !== false) {
            unset($images[$key]);
            $this->setImages(array_values($images));
        }
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getFacilities(): ?string
    {
        return $this->facilities;
    }

    public function setFacilities(?string $facilities): self
    {
        $this->facilities = $facilities;
        return $this;
    }

    public function getAccessibility(): ?string
    {
        return $this->accessibility;
    }

    public function setAccessibility(?string $accessibility): self
    {
        $this->accessibility = $accessibility;
        return $this;
    }

    public function getParkingInfo(): ?string
    {
        return $this->parkingInfo;
    }

    public function setParkingInfo(?string $parkingInfo): self
    {
        $this->parkingInfo = $parkingInfo;
        return $this;
    }

    public function getPublicTransport(): ?string
    {
        return $this->publicTransport;
    }

    public function setPublicTransport(?string $publicTransport): self
    {
        $this->publicTransport = $publicTransport;
        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): self
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return Collection<int, Larp>
     */
    public function getLarps(): Collection
    {
        return $this->larps;
    }

    public function addLarp(Larp $larp): self
    {
        if (!$this->larps->contains($larp)) {
            $this->larps->add($larp);
            $larp->setLocation($this);
        }
        return $this;
    }

    public function removeLarp(Larp $larp): self
    {
        if ($this->larps->removeElement($larp) && $larp->getLocation() === $this) {
            $larp->setLocation(null);
        }
        return $this;
    }

    public function getCoordinates(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude
            ];
        }
        return null;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->postalCode,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    public function getSocialMediaLinks(): array
    {
        $links = [];

        if ($this->facebook) {
            $links['facebook'] = $this->facebook;
        }
        if ($this->instagram) {
            $links['instagram'] = $this->instagram;
        }
        if ($this->twitter) {
            $links['twitter'] = $this->twitter;
        }
        if ($this->website) {
            $links['website'] = $this->website;
        }

        return $links;
    }

    public function getApprovalStatus(): LocationApprovalStatus
    {
        return $this->approvalStatus;
    }

    public function setApprovalStatus(LocationApprovalStatus $approvalStatus): self
    {
        $this->approvalStatus = $approvalStatus;
        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): self
    {
        $this->approvedBy = $approvedBy;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeInterface $approvedAt): self
    {
        $this->approvedAt = $approvedAt;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function isApproved(): bool
    {
        return $this->approvalStatus === LocationApprovalStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->approvalStatus === LocationApprovalStatus::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->approvalStatus === LocationApprovalStatus::REJECTED;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
