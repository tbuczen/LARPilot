<?php

namespace App\Form\Filter;

use App\Entity\Enum\CharacterType;
use App\Entity\Enum\Gender;
use App\Entity\Enum\UserRole;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Thread;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\StoryObject\ThreadRepository;
use App\Repository\UserRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('roles', ChoiceType::class, [
                'label' => 'form.invitation.role',
                'choices' => UserRole::cases(),
                'choice_label' => fn (UserRole $role) => 'user_role.' . $role->value,
                'choice_translation_domain' => 'messages',
                'choice_value' => fn (?UserRole $role) => $role?->value,
                'required' => true,
//                'multiple' => true,
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
//                    'plugins' =>  ['dropdown_input']
                    'hideSelected' => false
                ],
                'query_builder' => function (UserRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('u')
                        ->innerJoin('u.larpParticipants', 'lp')
                        ->where('lp.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'larp_participant_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => array('filtering'),
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
