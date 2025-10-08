<?php

namespace App\Form;

use App\Entity\Enum\UserRole;
use App\Entity\Larp;
use App\Entity\LarpInvitation;
use App\Entity\LarpParticipant;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\User;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Repository\UserRepository;
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

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);
        /** @var Larp $larp */
        $larp = $options["larp"];

        if (!isset($options['data'])) {
            $builder
                ->add('user', EntityType::class, [
                    'label' => 'form.participant.user',
                    'class' => User::class,
                    'choice_label' => 'username',
                    'required' => true,
                    'placeholder' => 'form.choose',
                    'autocomplete' => true,
                    'multiple' => false,
                ]);
        }

        $builder
            ->add('roles', ChoiceType::class, [
                'label' => 'form.participant.role',
                'choices' => UserRole::cases(),
                'choice_label' => fn (UserRole $role) => 'user_role.' . $role->value,
                'choice_translation_domain' => 'messages',
                'required' => true,
                'autocomplete' => true,
                'multiple' => true,
            ])
            ->addDependent('larpCharacters', 'roles', function (DependentField $field, ?array $roles) use ($larp) {
                if (!$roles) {
                    return;
                }
                foreach ($roles as $role) {
                    if ($role === UserRole::PLAYER) {
                        $field->add(EntityType::class, [
                            'class' => LarpCharacter::class,
                            'choice_label' => 'title',
                            'required' => false,
                            'placeholder' => 'form.choose',
                            'label' => 'form.participant.character',
                            'autocomplete' => true,
                            'multiple' => true,
                            'query_builder' => function (LarpCharacterRepository $repo) use ($larp) {
                                return $repo->createQueryBuilder('c')
                                    ->where('c.larp = :larp')
                                    ->setParameter('larp', $larp);
                            },
                        ]);
                    }
                }
            })
        ;

        $builder->add('submit', SubmitType::class, [
            'label' => 'form.submit',
            'priority' => -10000,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpParticipant::class,
            'larp' => null,
            'translation_domain' => 'forms',
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
