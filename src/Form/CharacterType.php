<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Repository\StoryObject\LarpFactionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterType extends AbstractType
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
            ->add('description', TextareaType::class, [
                'label' => 'form.character.description',
            ])
            ->add('factions', EntityType::class, [
                'class' => LarpFaction::class,
                'choice_label' => 'name',
                'label' => 'form.character.faction',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'data-autocomplete-tags' => true,
                ],
                'placeholder' => 'form.character.choose_faction',
                'query_builder' => function (LarpFactionRepository $repo) use ($options) {
                    /** @var Larp $larp */
                    $larp = $options['larp'];
                    return $repo->createQueryBuilder('f')
                        ->join('f.larps', 'l')
                        ->where('l = :larp')
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
    }
}