<?php

namespace App\Domain\Mailing\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\LarpAwareInterface;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Repository\MailTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: MailTemplateRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_mail_template_larp_type', columns: ['larp_id', 'type'])]
class MailTemplate implements LarpAwareInterface, Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'mailTemplates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Larp $larp = null;

    #[ORM\Column(length: 64, enumType: MailTemplateType::class)]
    private MailTemplateType $type;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $subject = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $body = '';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enabled = true;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $availablePlaceholders = null;

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;

        return $this;
    }

    public function getType(): MailTemplateType
    {
        return $this->type;
    }

    public function setType(MailTemplateType $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAvailablePlaceholders(): array
    {
        return $this->availablePlaceholders ?? [];
    }

    /**
     * @param list<string> $availablePlaceholders
     */
    public function setAvailablePlaceholders(array $availablePlaceholders): self
    {
        $this->availablePlaceholders = array_values(array_unique($availablePlaceholders));

        return $this;
    }
}
