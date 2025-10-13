<?php

namespace App\Domain\Core\Form;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Repository\CharacterRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class LarpParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);
        /** @var Larp $larp */
        $larp = $options["larp"];

        if (!isset($options['data'])) {
            $builder
                ->add('user', EntityType::class, [
                    'label' => 'participant.user',
                    'class' => User::class,
                    'choice_label' => 'username',
                    'required' => true,
                    'placeholder' => 'choose',
                    'autocomplete' => true,
                    'multiple' => false,
                ]);
        }

        $builder
            ->add('roles', ChoiceType::class, [
                'label' => 'participant.role',
                'choices' => ParticipantRole::cases(),
                'choice_label' => fn (ParticipantRole $role): string => 'user_role.' . $role->value,
                'choice_translation_domain' => 'messages',
                'required' => true,
                'autocomplete' => true,
                'multiple' => true,
            ])
            ->addDependent('larpCharacters', 'roles', function (DependentField $field, ?array $roles) use ($larp): void {
                if (!$roles) {
                    return;
                }
                foreach ($roles as $role) {
                    if ($role === ParticipantRole::PLAYER) {
                        $field->add(EntityType::class, [
                            'class' => Character::class,
                            'choice_label' => 'title',
                            'required' => false,
                            'placeholder' => 'choose',
                            'label' => 'participant.character',
                            'autocomplete' => true,
                            'multiple' => true,
                            'query_builder' => fn (CharacterRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('c')
                                ->where('c.larp = :larp')
                                ->setParameter('larp', $larp),
                        ]);
                    }
                }
            })
        ;

        $builder->add('submit', SubmitType::class, [
            'label' => 'submit',
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
