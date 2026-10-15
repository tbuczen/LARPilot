<?php

namespace App\Domain\Integrations\Form\Integrations;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterListColumnMappingType extends AbstractColumnMappingType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $columnChoices = $options['column_choices'];

        $this->addColumnField($builder, 'title', ['label' => 'character.name'], $columnChoices);
        $this->addColumnField($builder, 'inGameName', ['label' => 'character.in_game_name'], $columnChoices);
        $this->addColumnField($builder, 'factions', ['label' => 'character.faction'], $columnChoices);
        $this->addColumnField($builder, 'description', ['label' => 'character.description'], $columnChoices);
        $this->addColumnField($builder, 'storyWriter', ['label' => 'character.story_writer', 'required' => false], $columnChoices);
        $this->addColumnField($builder, 'tags', ['label' => 'character.tag', 'required' => false], $columnChoices);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'label' => 'mappings',
        ]);
    }
}
