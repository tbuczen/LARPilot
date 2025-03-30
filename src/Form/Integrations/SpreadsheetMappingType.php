<?php

namespace App\Form\Integrations;

use App\Enum\FileMappingType;
use App\Form\Models\SpreadsheetMappingModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpreadsheetMappingType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mappingType', EnumType::class, [
                'label' => 'Type of mapping',
                'class' =>  FileMappingType::class
            ])
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
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SpreadsheetMappingModel::class,
        ]);
    }

}