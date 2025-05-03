<?php

namespace App\Form\Filter;

use App\Entity\Enum\CharacterType;
use App\Entity\Enum\Gender;
use App\Entity\LarpParticipant;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Thread;
use App\Entity\Tag;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
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
            ;
    }

    public function getBlockPrefix(): string
    {
        return 'larp_quest_filter';
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