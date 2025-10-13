<?php

namespace App\Domain\Account\Form;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\Locale;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('preferredLocale', ChoiceType::class, [
                'label' => 'Language',
                'choices' => Locale::cases(),
                'choice_label' => fn (Locale $locale) => $locale->name,
                'choice_value' => fn (?Locale $locale) => $locale?->value,
                'required' => true,
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email',
                'disabled' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
            ])
        ;
        // profile picture, 2fa
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'forms',
        ]);
    }
}
