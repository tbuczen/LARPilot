<?php

namespace App\Form;

use App\Entity\Enum\Gender;
use App\Entity\Larp;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Repository\StoryObject\LarpFactionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.character.name',
            ])
            ->add('inGameName', TextType::class, [
                'label' => 'form.character.in_game_name',
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'form.character.gender',
                'choices' => Gender::cases(),
                'choice_label' => fn (Gender $gender) => $gender->name,
                'choice_value' => fn (?Gender $gender) => $gender?->value,
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.character.description',
            ])
            ->add('availableForRecruitment', CheckboxType::class, [
                'label' => 'form.character.available_for_recruitment',
                'required' => false,
            ])
            ->add('factions', EntityType::class, [
                'class' => LarpFaction::class,
                'choice_label' => 'title',
                'label' => 'form.character.faction',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.character.choose_faction',
                'query_builder' => function (LarpFactionRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpCharacter::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
