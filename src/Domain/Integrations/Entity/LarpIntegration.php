<?php

namespace App\Domain\Integrations\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\IntegrationStatus;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LarpIntegrationRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\Index(columns: ['created_by_id'])]
#[ORM\UniqueConstraint(name: 'uniq_larp_integration_larp_provider', columns: ['larp_id', 'provider'])]
class LarpIntegration implements Timestampable, CreatorAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\Column(type: 'string', enumType: LarpIntegrationProvider::class)]
    private LarpIntegrationProvider $provider;

    #[ORM\Column(type: 'string', enumType: ConnectionType::class)]
    private ConnectionType $connectionType = ConnectionType::UserOauth;

    #[ORM\Column(type: 'string', enumType: IntegrationStatus::class)]
    private IntegrationStatus $status = IntegrationStatus::Active;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $externalAccountId = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $config = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastCheckedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $scopes = null;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'integrations')]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    /** @var Collection<SharedFile>  */
    #[ORM\OneToMany(targetEntity: SharedFile::class, mappedBy: 'integration', cascade: ['persist', 'remove'])]
    private Collection $sharedFiles;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $owner = null;

    private ?OAuth2ClientInterface $client = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->sharedFiles = new ArrayCollection();
    }

    public function getProvider(): LarpIntegrationProvider
    {
        return $this->provider;
    }

    public function setProvider(LarpIntegrationProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getConnectionType(): ConnectionType
    {
        return $this->connectionType;
    }

    public function setConnectionType(ConnectionType $connectionType): void
    {
        $this->connectionType = $connectionType;
    }

    public function getStatus(): IntegrationStatus
    {
        return $this->status;
    }

    public function setStatus(IntegrationStatus $status): void
    {
        $this->status = $status;
    }

    public function markActive(): void
    {
        $this->status = IntegrationStatus::Active;
        $this->lastError = null;
        $this->lastCheckedAt = new \DateTimeImmutable();
    }

    public function markNeedsReauth(string $reason): void
    {
        $this->status = IntegrationStatus::NeedsReauth;
        $this->lastError = $reason;
        $this->lastCheckedAt = new \DateTimeImmutable();
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getExternalAccountId(): ?string
    {
        return $this->externalAccountId;
    }

    public function setExternalAccountId(?string $externalAccountId): void
    {
        $this->externalAccountId = $externalAccountId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): void
    {
        $this->lastError = $lastError;
    }

    public function getLastCheckedAt(): ?\DateTimeInterface
    {
        return $this->lastCheckedAt;
    }

    public function setLastCheckedAt(?\DateTimeInterface $lastCheckedAt): void
    {
        $this->lastCheckedAt = $lastCheckedAt;
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): void
    {
        $this->larp = $larp;
    }

    public function getScopes(): ?string
    {
        return $this->scopes;
    }

    public function setScopes(?string $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function setClient(OAuth2ClientInterface $oauthClient): void
    {
        $this->client = $oauthClient;
    }

    public function getClient(): ?OAuth2ClientInterface
    {
        return $this->client;
    }

    /**
     * @return Collection<SharedFile>
     */
    public function getSharedFiles(): Collection
    {
        return $this->sharedFiles;
    }

    public function addSharedFile(SharedFile $file): void
    {
        $this->sharedFiles[] = $file;
        $file->setIntegration($this);
    }

    public function removeSharedFile(SharedFile $file): void
    {
        $this->sharedFiles->removeElement($file);
    }

    public function setOwner(?string $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }
}
