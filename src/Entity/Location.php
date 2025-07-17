<?php

namespace App\Entity;

use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location implements Timestampable, CreatorAwareInterface
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
    private ?array $images = null;

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
    private bool $isActive = true;

    /** @var Collection<Larp> */
    #[ORM\OneToMany(targetEntity: Larp::class, mappedBy: 'location')]
    private Collection $larps;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->larps = new ArrayCollection();
        $this->images = [];
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): static
    {
        $this->facebook = $facebook;
        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): static
    {
        $this->instagram = $instagram;
        return $this;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): static
    {
        $this->twitter = $twitter;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images ?? [];
    }

    public function setImages(?array $images): static
    {
        $this->images = $images;
        return $this;
    }

    public function addImage(string $imagePath): static
    {
        $images = $this->getImages();
        if (!in_array($imagePath, $images, true)) {
            $images[] = $imagePath;
            $this->setImages($images);
        }
        return $this;
    }

    public function removeImage(string $imagePath): static
    {
        $images = $this->getImages();
        $key = array_search($imagePath, $images, true);
        if ($key !== false) {
            unset($images[$key]);
            $this->setImages(array_values($images));
        }
        return $this;
    }

    public function getFacilities(): ?string
    {
        return $this->facilities;
    }

    public function setFacilities(?string $facilities): static
    {
        $this->facilities = $facilities;
        return $this;
    }

    public function getAccessibility(): ?string
    {
        return $this->accessibility;
    }

    public function setAccessibility(?string $accessibility): static
    {
        $this->accessibility = $accessibility;
        return $this;
    }

    public function getParkingInfo(): ?string
    {
        return $this->parkingInfo;
    }

    public function setParkingInfo(?string $parkingInfo): static
    {
        $this->parkingInfo = $parkingInfo;
        return $this;
    }

    public function getPublicTransport(): ?string
    {
        return $this->publicTransport;
    }

    public function setPublicTransport(?string $publicTransport): static
    {
        $this->publicTransport = $publicTransport;
        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
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

    public function addLarp(Larp $larp): static
    {
        if (!$this->larps->contains($larp)) {
            $this->larps->add($larp);
            $larp->setLocation($this);
        }
        return $this;
    }

    public function removeLarp(Larp $larp): static
    {
        if ($this->larps->removeElement($larp)) {
            if ($larp->getLocation() === $this) {
                $larp->setLocation(null);
            }
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

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
