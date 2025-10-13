<?php

namespace App\Domain\Core\Form;

use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\LarpInvitation;
use App\Domain\StoryObject\Entity\Character;
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
                'label' => 'invitation.role',
                'choices' => ParticipantRole::cases(),
                'choice_label' => fn (ParticipantRole $role): string => 'user_role.' . $role->value,
                'choice_translation_domain' => 'messages',
                'choice_value' => fn (?ParticipantRole $role) => $role?->value,
                'required' => true,
            ])
            ->add('validTo', DateTimeType::class, [
                'label' => 'invitation.valid_to',
                'widget' => 'single_text',
            ])
            ->add('isReusable', CheckboxType::class, [
                'label' => 'invitation.is_reusable',
                'required' => false,
            ])
            ->addDependent('larpCharacter', 'invitedRole', function (DependentField $field, ?ParticipantRole $role): void {
                if (!$role instanceof ParticipantRole) {
                    return;
                }
                if ($role === ParticipantRole::PLAYER) {
                    $field->add(EntityType::class, [
                        'class' => Character::class,
                        'choice_label' => 'name',
                        'required' => false,
                        'placeholder' => 'choose',
                        'label' => 'invitation.character'
                    ]);
                }
            })
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
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
