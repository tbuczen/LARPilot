<?php

namespace App\Domain\Integrations\Form\Integrations;

use Symfony\Component\Form\FormBuilderInterface;

class TagListColumnMappingType extends AbstractColumnMappingType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $columnChoices = $options['column_choices'];

        $this->addColumnField($builder, 'title', ['label' => 'tag.title', 'required' => true], $columnChoices);
        $this->addColumnField($builder, 'description', ['label' => 'tag.description', 'required' => false], $columnChoices);
    }
}
