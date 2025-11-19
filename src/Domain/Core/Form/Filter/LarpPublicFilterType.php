<?php

namespace App\Domain\Core\Form\Filter;

use App\Domain\Core\Entity\Enum\LarpCharacterSystem;
use App\Domain\Core\Entity\Enum\LarpSetting;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\LarpType;
use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Entity\Location;
use App\Domain\Core\Repository\LocationRepository;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpPublicFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //TODO:: filtering start date and end date should be >= start date and <= endDate
        //statuses, settings, types, must be multiple OR
        //location needs to be autocomplete to location entity
        //PRO: there should be option to localise a larp in range of given address
        //duration should be a range from available larps min and max durations
        $builder
            ->add('status', Filters\EnumFilterType::class, [
                'class' => LarpStageStatus::class,
                'required' => false,
                'multiple' => false,
                'placeholder' => 'All Statuses',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    LarpStageStatus::PUBLISHED,
                    LarpStageStatus::INQUIRIES,
                    LarpStageStatus::CONFIRMED,
                    LarpStageStatus::COMPLETED,
                ],
            ])
            ->add('setting', Filters\EnumFilterType::class, [
                'class' => LarpSetting::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'choose',
            ])
            ->add('type', Filters\EnumFilterType::class, [
                'class' => LarpType::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'choose',
            ])
            ->add('characterSystem', Filters\EnumFilterType::class, [
                'class' => LarpCharacterSystem::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'choice_label' => fn (LarpCharacterSystem $enum): string => $enum->getLabel(),
                'placeholder' => 'choose',
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'title',
                'placeholder' => 'choose',
                'required' => false,
                'autocomplete' => true,
                'attr' => ['class' => 'form-select'],
                'query_builder' => fn (LocationRepository $repo): QueryBuilder => $repo->createQueryBuilder('l')
                    ->where('l.isActive = true')
                    ->andWhere('l.isPublic = true')
                    ->andWhere('l.approvalStatus = :status')
                    ->setParameter('status', LocationApprovalStatus::APPROVED->value)
            ])
            ->add('startDate', Filters\DateFilterType::class, [
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', Filters\DateFilterType::class, [
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('minDuration', Filters\NumberFilterType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 30,
                    'placeholder' => 'Min days'
                ],
                'html5' => true,
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    // Duration in days = difference between endDate and startDate
                    $qb->andWhere('DATE_DIFF(c.endDate, c.startDate) >= :minDuration')
                        ->setParameter('minDuration', (int) $values['value']);
                    return null;
                },
            ])
            ->add('maxDuration', Filters\NumberFilterType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 31,
                    'placeholder' => 'Max days'
                ],
                'html5' => true,
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    // Duration in days = difference between endDate and startDate
                    $qb->andWhere('DATE_DIFF(c.endDate, c.startDate) <= :maxDuration')
                        ->setParameter('maxDuration', (int) $values['value']);
                    return null;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => false,
            'method' => 'GET',
        ]);
    }
}
