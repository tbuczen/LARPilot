<?php

namespace App\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CharacterListColumnMappingType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.character.name',
                'translation_domain' => 'forms',
            ])
            ->add('inGameName', TextType::class, [
                'label' => 'form.character.in_game_name',
                'translation_domain' => 'forms',
            ])
            ->add('factions', TextType::class, [
                'label' => 'form.character.faction',
                'translation_domain' => 'forms',
            ])
            ->add('description', TextType::class, [
                'label' => 'form.character.description',
                'translation_domain' => 'forms',
            ]);
    }
}
