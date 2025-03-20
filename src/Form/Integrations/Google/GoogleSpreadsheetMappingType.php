<?php

namespace App\Form\Integrations\Google;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class GoogleSpreadsheetMappingType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startingRow', IntegerType::class, [
                'label' => 'Starting Row',
                'data' => 3, // Default starting row (skip headers)
            ])
            ->add('factionColumn', TextType::class, [
                'label' => 'Faction Column',
            ])
            ->add('characterNameColumn', TextType::class, [
                'label' => 'Character Name Column',
            ])
            ->add('inGameNameColumn', TextType::class, [
                'label' => 'In-game Name Column',
            ]);
    }

}