<?php

namespace App\Domain\Integrations\Form\Integrations;

use Symfony\Component\Form\FormBuilderInterface;

class EventListColumnMappingType extends AbstractColumnMappingType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $columnChoices = $options['column_choices'];

        $this->addColumnField($builder, 'eventName', [], $columnChoices);
        $this->addColumnField($builder, 'description', [], $columnChoices);
        $this->addColumnField($builder, 'factions', [], $columnChoices);
    }
}
