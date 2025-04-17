<?php

namespace App\Form;

use App\Entity\LarpCharacter;
use App\Repository\LarpFactionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


//TODO::
class CharacterType extends AbstractType
{

    public function __construct(
        private readonly LarpFactionRepository $factionRepository
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Larp Name',
            ])
            ->add('inGameName', TextareaType::class, [
                'label' => 'NAzwa postaci',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('faction', TextareaType::class, [
                'label' => 'Description',
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpCharacter::class,
        ]);
    }
}