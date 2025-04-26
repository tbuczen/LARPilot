<?php

namespace App\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterListColumnMappingType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.character.name',
            ])
            ->add('inGameName', TextType::class, [
                'label' => 'form.character.in_game_name',
            ])
            ->add('factions', TextType::class, [
                'label' => 'form.character.faction',
            ])
            ->add('description', TextType::class, [
                'label' => 'form.character.description',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'forms',
        ]);
    }
}
