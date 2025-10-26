<?php

namespace App\Domain\Core\Form\Filter;

use App\Domain\Account\Entity\User;
use App\Domain\Account\Repository\UserRepository;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
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
                'label' => 'invitation.role',
                'choices' => ParticipantRole::cases(),
                'choice_label' => fn (ParticipantRole $role): string => 'user_role.' . $role->value,
                'choice_translation_domain' => 'messages',
                'choice_value' => fn (?ParticipantRole $role) => $role?->value,
                'autocomplete' => true,
                'multiple' => true,
                'apply_filter' => static function (QueryInterface $filterQuery, $field, $values) {
                    $roles = $values['value'] ?? [];
                    if (!is_array($roles)) {
                        return null;
                    }
                    $parameters = [];
                    $expression = $filterQuery->getExpr()->andX();
                    /** @var \App\Domain\Account\Entity\Enum\\App\Domain\Core\Entity\Enum\ParticipantRole $role */
                    foreach ($roles as $i => $role) {
                        $expression->add("JSONB_EXISTS($field, :role_$i) = true");
                        $parameters["role_$i"] = $role->value;
                    }

                    return $filterQuery->createCondition($expression, $parameters);
                }
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
                'query_builder' => fn (UserRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('u')
                    ->innerJoin('u.larpParticipants', 'lp')
                    ->select('lp')
                    ->where('lp.larp = :larp')
                    ->setParameter('larp', $larp),
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
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
