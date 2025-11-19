<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Mailing;

use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Entity\MailTemplate;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<MailTemplate>
 */
final class MailTemplateFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return MailTemplate::class;
    }

    protected function defaults(): array
    {
        return [
            'larp' => LarpFactory::new(),
            'type' => self::faker()->randomElement(MailTemplateType::cases()),
            'name' => self::faker()->words(3, true),
            'subject' => self::faker()->sentence(),
            'body' => self::faker()->paragraphs(3, true),
            'enabled' => true,
            'availablePlaceholders' => ['{{larp_name}}', '{{user_name}}', '{{user_email}}'],
        ];
    }



    // ========================================================================
    // Factory States (Template Types)
    // ========================================================================

    /**
     * Enquiry open template
     */
    public function enquiryOpen(): self
    {
        return $this->with([
            'type' => MailTemplateType::ENQUIRY_OPEN,
            'name' => 'Enquiry Open',
        ]);
    }

    /**
     * Enquiry closing soon template
     */
    public function enquiryClosingSoon(): self
    {
        return $this->with([
            'type' => MailTemplateType::ENQUIRY_CLOSING_SOON,
            'name' => 'Enquiry Closing Soon',
        ]);
    }

    /**
     * Payment reminder template
     */
    public function paymentReminder(): self
    {
        return $this->with([
            'type' => MailTemplateType::PAYMENT_REMINDER,
            'name' => 'Payment Reminder',
        ]);
    }

    /**
     * Installment due template
     */
    public function installmentDue(): self
    {
        return $this->with([
            'type' => MailTemplateType::INSTALLMENT_DUE,
            'name' => 'Installment Due',
        ]);
    }

    /**
     * Ticket confirmed template
     */
    public function ticketConfirmed(): self
    {
        return $this->with([
            'type' => MailTemplateType::TICKET_CONFIRMED,
            'name' => 'Ticket Confirmed',
        ]);
    }

    /**
     * Costume review template
     */
    public function costumeReview(): self
    {
        return $this->with([
            'type' => MailTemplateType::COSTUME_REVIEW,
            'name' => 'Costume Review',
        ]);
    }

    /**
     * Lore update template
     */
    public function loreUpdate(): self
    {
        return $this->with([
            'type' => MailTemplateType::LORE_UPDATE,
            'name' => 'Lore Update',
        ]);
    }

    /**
     * Character assignment published template
     */
    public function characterAssignmentPublished(): self
    {
        return $this->with([
            'type' => MailTemplateType::CHARACTER_ASSIGNMENT_PUBLISHED,
            'name' => 'Character Assignment Published',
        ]);
    }

    /**
     * Application rejected template
     */
    public function applicationRejected(): self
    {
        return $this->with([
            'type' => MailTemplateType::APPLICATION_REJECTED,
            'name' => 'Application Rejected',
        ]);
    }

    /**
     * Waitlist notification template
     */
    public function waitlistNotification(): self
    {
        return $this->with([
            'type' => MailTemplateType::WAITLIST_NOTIFICATION,
            'name' => 'Waitlist Notification',
        ]);
    }

    /**
     * Organizer broadcast template
     */
    public function organizerBroadcast(): self
    {
        return $this->with([
            'type' => MailTemplateType::ORGANIZER_BROADCAST,
            'name' => 'Organizer Broadcast',
        ]);
    }

    // ========================================================================
    // Factory Configuration Methods
    // ========================================================================

    /**
     * Template for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Disabled template
     */
    public function disabled(): self
    {
        return $this->with([
            'enabled' => false,
        ]);
    }

    /**
     * Enabled template
     */
    public function enabled(): self
    {
        return $this->with([
            'enabled' => true,
        ]);
    }

    /**
     * Template with specific placeholders
     */
    public function withPlaceholders(array $placeholders): self
    {
        return $this->with([
            'availablePlaceholders' => $placeholders,
        ]);
    }
}
