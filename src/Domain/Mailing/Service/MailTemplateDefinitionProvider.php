<?php

namespace App\Domain\Mailing\Service;

use App\Domain\Mailing\Dto\MailTemplateDefinition;
use App\Domain\Mailing\Entity\Enum\MailTemplateType;

class MailTemplateDefinitionProvider
{
    /** @var array<string, MailTemplateDefinition>|null */
    private ?array $cache = null;

    /**
     * @return array<string, MailTemplateDefinition>
     */
    public function getDefinitions(): array
    {
        if ($this->cache === null) {
            $this->cache = $this->buildDefinitions();
        }

        return $this->cache;
    }

    public function getDefinition(MailTemplateType $type): ?MailTemplateDefinition
    {
        return $this->getDefinitions()[$type->value] ?? null;
    }

    /**
     * @return array<string, MailTemplateDefinition>
     */
    private function buildDefinitions(): array
    {
        $definitions = [];

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::ENQUIRY_OPEN,
            'Enquiries are open',
            'Sent to interested players once a LARP accepts enquiries.',
            'Enquiries for {{ larp_title }} are open',
            "Hello {{ player_name }},\n\nWe have opened enquiries for {{ larp_title }}. Submit your form here: {{ enquiry_url }} before {{ enquiry_deadline }}.\n\nSee you soon,\n{{ organizer_signature }}",
            array_merge($this->basePlaceholders(), ['enquiry_url', 'enquiry_deadline', 'organizer_signature']),
            true,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::ENQUIRY_CLOSING_SOON,
            'Enquiries closing soon',
            'Reminder that the enquiry window is about to close.',
            'Final call for {{ larp_title }} enquiries',
            "Hi {{ player_name }},\n\nEnquiries for {{ larp_title }} will close on {{ enquiry_deadline }}. If you still want to apply, finish your form at {{ enquiry_url }}.\n\nCheers,\n{{ organizer_signature }}",
            array_merge($this->basePlaceholders(), ['enquiry_url', 'enquiry_deadline', 'organizer_signature']),
            false,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::PAYMENT_REMINDER,
            'Payment reminder',
            'General reminder covering ticket or invoice payments.',
            'Payment reminder for {{ larp_title }}',
            "Hello {{ player_name }},\n\nThis is a friendly reminder that we are waiting for your payment of {{ payment_amount }} for {{ larp_title }}. You can complete it here: {{ payment_url }}.\n\nThank you!",
            array_merge($this->basePlaceholders(), ['payment_amount', 'payment_url', 'payment_deadline']),
            true,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::INSTALLMENT_DUE,
            'Installment due',
            'Finance-focused reminder for the next installment.',
            'Upcoming installment for {{ larp_title }}',
            "Hi {{ player_name }},\n\nYour next installment of {{ payment_amount }} for {{ larp_title }} is due on {{ payment_deadline }}. Pay online: {{ payment_url }}.\n\nNeed help? Contact {{ organizer_signature }}.",
            array_merge($this->basePlaceholders(), ['payment_amount', 'payment_deadline', 'payment_url', 'organizer_signature']),
            false,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::TICKET_CONFIRMED,
            'Ticket confirmation',
            'Confirmation message once a player secures a seat.',
            'Your ticket for {{ larp_title }} is confirmed',
            "Hello {{ player_name }},\n\nWe have received your payment and confirmed your ticket for {{ larp_title }}. You can review your booking details here: {{ player_portal_url }}.\n\nWe cannot wait to see you!",
            array_merge($this->basePlaceholders(), ['player_portal_url']),
            true,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::COSTUME_REVIEW,
            'Costume feedback',
            'Used by costume teams to approve or request changes.',
            'Costume submission for {{ character_name }}',
            "Hi {{ player_name }},\n\nThank you for submitting a costume for {{ character_name }}. Our team reviewed it and left a note: {{ costume_feedback }}.\n\nPlease reply if you have questions.",
            array_merge($this->basePlaceholders(), ['character_name', 'costume_feedback']),
            false,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::LORE_UPDATE,
            'Lore update',
            'Optional narrative update that builds hype.',
            'Lore update for {{ larp_title }}',
            "Greetings {{ player_name }},\n\n{{ lore_summary }}\n\nRead the full update: {{ lore_link }}.",
            array_merge($this->basePlaceholders(), ['lore_summary', 'lore_link']),
            false,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::CHARACTER_ASSIGNMENT_PUBLISHED,
            'Character assignment published',
            'Sent when a player receives a character.',
            'Your character for {{ larp_title }}',
            "Hi {{ player_name }},\n\nGreat news! You have been cast as {{ character_name }}. Visit {{ character_public_url }} to read the full character sheet and preparation tasks.\n\nHappy reading!",
            array_merge($this->basePlaceholders(), ['character_name', 'character_public_url']),
            true,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::APPLICATION_REJECTED,
            'Application outcome - no role',
            'Default rejection mail if a player cannot be cast.',
            'Application outcome for {{ larp_title }}',
            "Hello {{ player_name }},\n\nThank you for applying to {{ larp_title }}. Unfortunately we cannot offer you a role this time. We truly appreciate your interest and hope to see you at future events.\n\nBest wishes,\n{{ organizer_signature }}",
            array_merge($this->basePlaceholders(), ['organizer_signature']),
            true,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::WAITLIST_NOTIFICATION,
            'Waitlist update',
            'Keep waitlisted players informed.',
            'Waitlist update for {{ larp_title }}',
            "Hi {{ player_name }},\n\nWe are still processing the waitlist for {{ larp_title }}. Your current position is {{ waitlist_position }}. We will reach out immediately if a slot opens.\n\nThanks for your patience!",
            array_merge($this->basePlaceholders(), ['waitlist_position']),
            false,
        );

        $definitions[] = new MailTemplateDefinition(
            MailTemplateType::ORGANIZER_BROADCAST,
            'Organizer broadcast',
            'General purpose announcement template.',
            'Update from the {{ larp_title }} crew',
            "Hello {{ player_name }},\n\n{{ broadcast_message }}\n\nSee more details: {{ broadcast_link }}.",
            array_merge($this->basePlaceholders(), ['broadcast_message', 'broadcast_link']),
            false,
        );

        return array_reduce(
            $definitions,
            static function (array $carry, MailTemplateDefinition $definition): array {
                $carry[$definition->type->value] = $definition;

                return $carry;
            },
            []
        );
    }

    /**
     * @return list<string>
     */
    private function basePlaceholders(): array
    {
        return [
            'larp_title',
            'larp_slug',
            'player_name',
            'player_email',
        ];
    }
}
