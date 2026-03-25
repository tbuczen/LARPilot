<?php

declare(strict_types=1);

namespace App\Domain\Application\Form;

use App\Domain\Application\Entity\LarpApplicationTurn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class LarpApplicationTurnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roundNumber', IntegerType::class, [
                'label' => 'application_turn.round_number',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [new NotBlank(), new GreaterThanOrEqual(1)],
            ])
            ->add('price', NumberType::class, [
                'label' => 'application_turn.price',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'application_turn.price_placeholder'],
                'help' => 'application_turn.price_help',
                'constraints' => [new PositiveOrZero()],
                'scale' => 2,
            ])
            ->add('opensAt', DateTimeType::class, [
                'label' => 'application_turn.opens_at',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('closesAt', DateTimeType::class, [
                'label' => 'application_turn.closes_at',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'save',
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpApplicationTurn::class,
            'translation_domain' => 'forms',
        ]);
    }
}
