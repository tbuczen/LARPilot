<?php

namespace App\Domain\Mailing\Entity\Enum;

enum MailTemplateType: string
{
    case ENQUIRY_OPEN = 'ENQUIRY_OPEN';
    case ENQUIRY_CLOSING_SOON = 'ENQUIRY_CLOSING_SOON';
    case PAYMENT_REMINDER = 'PAYMENT_REMINDER';
    case INSTALLMENT_DUE = 'INSTALLMENT_DUE';
    case TICKET_CONFIRMED = 'TICKET_CONFIRMED';
    case COSTUME_REVIEW = 'COSTUME_REVIEW';
    case LORE_UPDATE = 'LORE_UPDATE';
    case CHARACTER_ASSIGNMENT_PUBLISHED = 'CHARACTER_ASSIGNMENT_PUBLISHED';
    case APPLICATION_REJECTED = 'APPLICATION_REJECTED';
    case WAITLIST_NOTIFICATION = 'WAITLIST_NOTIFICATION';
    case ORGANIZER_BROADCAST = 'ORGANIZER_BROADCAST';

    public function label(): string
    {
        return match ($this) {
            self::ENQUIRY_OPEN => 'Enquiries are open',
            self::ENQUIRY_CLOSING_SOON => 'Enquiries closing soon',
            self::PAYMENT_REMINDER => 'Payment reminder',
            self::INSTALLMENT_DUE => 'Installment due',
            self::TICKET_CONFIRMED => 'Ticket confirmation',
            self::COSTUME_REVIEW => 'Costume review',
            self::LORE_UPDATE => 'Lore update',
            self::CHARACTER_ASSIGNMENT_PUBLISHED => 'Character assignment published',
            self::APPLICATION_REJECTED => 'Application rejected',
            self::WAITLIST_NOTIFICATION => 'Waitlist notification',
            self::ORGANIZER_BROADCAST => 'Organizer broadcast',
        };
    }

    public function belongsToFinanceContext(): bool
    {
        return match ($this) {
            self::PAYMENT_REMINDER,
            self::INSTALLMENT_DUE,
            self::TICKET_CONFIRMED => true,
            default => false,
        };
    }
}
