<?php

namespace App\Domain\Integrations\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterListColumnMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'character.name',
            ])
            ->add('inGameName', TextType::class, [
                'label' => 'character.in_game_name',
            ])
            ->add('factions', TextType::class, [
                'label' => 'character.faction',
            ])
            ->add('description', TextType::class, [
                'label' => 'character.description',
            ])
            ->add('storyWriter', TextType::class, [
                'label' => 'character.story_writer',
                'required' => false,
            ])
            ->add('tags', TextType::class, [
                'label' => 'character.tag',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'forms',
            'label' => 'mappings',
        ]);
    }
}
