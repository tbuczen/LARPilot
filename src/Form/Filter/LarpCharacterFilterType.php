<?php

namespace App\Form\Filter;

use App\Entity\Enum\CharacterType;
use App\Entity\Enum\Gender;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\Tag;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpCharacterFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inGameName', Filters\TextFilterType::class)
            ->add('gender', Filters\EnumFilterType::class, [
                'class' => Gender::class,
                'required' => false,
                'placeholder' => 'form.choose',
            ])
            ->add('characterType', Filters\EnumFilterType::class, [
                'class' => CharacterType::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' =>  'form.choose',
            ])
            ->add('factions', EntityType::class, [
                'class' => LarpFaction::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default', // potrzebne przez FilterBundle
                'tom_select_options' => [
//                    'plugins' =>  ['dropdown_input']
                'hideSelected' => false
                ]
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default', // potrzebne przez FilterBundle
            ])
            ->add('saveFilter', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'common.save_filter_name',
            ]);
            ;
    }

    public function getBlockPrefix(): string
    {
        return 'larp_character_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => array('filtering'),
            'method' => 'GET',
            'translation_domain' => 'forms',
        ]);
    }
}