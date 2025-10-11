<?php

namespace App\Form;

use App\Entity\Enum\UserRole;
use App\Entity\LarpInvitation;
use App\Entity\StoryObject\Character;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class InvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('invitedRole', ChoiceType::class, [
                'label' => 'form.invitation.role',
                'choices' => UserRole::cases(),
                'choice_label' => fn (UserRole $role): string => 'user_role.' . $role->value,
                'choice_translation_domain' => 'messages',
                'choice_value' => fn (?UserRole $role) => $role?->value,
                'required' => true,
            ])
            ->add('validTo', DateTimeType::class, [
                'label' => 'form.invitation.valid_to',
                'widget' => 'single_text',
            ])
            ->add('isReusable', CheckboxType::class, [
                'label' => 'form.invitation.is_reusable',
                'required' => false,
            ])
            ->addDependent('larpCharacter', 'invitedRole', function (DependentField $field, ?UserRole $role): void {
                if (!$role instanceof UserRole) {
                    return;
                }
                if ($role === UserRole::PLAYER) {
                    $field->add(EntityType::class, [
                        'class' => Character::class,
                        'choice_label' => 'name',
                        'required' => false,
                        'placeholder' => 'form.choose',
                        'label' => 'form.invitation.character'
                    ]);
                }
            })
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpInvitation::class,
            'larp' => null,
            'translation_domain' => 'forms',
        ]);
    }
}
