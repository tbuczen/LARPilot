<?php

namespace App\Domain\Core\Form\Filter;

use App\Domain\Core\Entity\Enum\LocationApprovalStatus;
use App\Domain\Core\Repository\LocationRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationPublicFilterType extends AbstractType
{
    public function __construct(private LocationRepository $locationRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get distinct cities and countries for filter dropdowns (only approved locations)
        $cities = $this->locationRepository->createQueryBuilder('l')
            ->select('DISTINCT l.city')
            ->where('l.isPublic = :isPublic')
            ->andWhere('l.isActive = :isActive')
            ->andWhere('l.approvalStatus = :approved')
            ->andWhere('l.city IS NOT NULL')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true)
            ->setParameter('approved', LocationApprovalStatus::APPROVED)
            ->orderBy('l.city', 'ASC')
            ->getQuery()
            ->getResult();

        $countries = $this->locationRepository->createQueryBuilder('l')
            ->select('DISTINCT l.country')
            ->where('l.isPublic = :isPublic')
            ->andWhere('l.isActive = :isActive')
            ->andWhere('l.approvalStatus = :approved')
            ->andWhere('l.country IS NOT NULL')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true)
            ->setParameter('approved', LocationApprovalStatus::APPROVED)
            ->orderBy('l.country', 'ASC')
            ->getQuery()
            ->getResult();

        // Transform to choice array format
        $cityChoices = array_combine(
            array_column($cities, 'city'),
            array_column($cities, 'city')
        );
        $countryChoices = array_combine(
            array_column($countries, 'country'),
            array_column($countries, 'country')
        );


        $builder
            ->add('title', Filters\TextFilterType::class, [
                'required' => false,
                'label' => 'Search',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Search by name, city or country...',
                ],
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    $searchTerm = strtolower($values['value']);

                    $qb->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->like('LOWER(l.title)', ':searchTerm'),
                            $qb->expr()->like('LOWER(l.city)', ':searchTerm'),
                            $qb->expr()->like('LOWER(l.country)', ':searchTerm')
                        )
                    )->setParameter('searchTerm', '%' . $searchTerm . '%');

                    return null;
                },
            ])
            ->add('city', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'All Cities',
                'attr' => ['class' => 'form-select'],
                'choices' => $cityChoices,
            ])
            ->add('country', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'All Countries',
                'attr' => ['class' => 'form-select'],
                'choices' => $countryChoices,
            ])
            ->add('minCapacity', Filters\NumberFilterType::class, [
                'required' => false,
                'label' => 'Min Capacity',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 9000,
                    'placeholder' => 'Min',
                ],
                'html5' => true
            ])
            ->add('maxCapacity', Filters\NumberFilterType::class, [
                'required' => false,
                'label' => 'Max Capacity',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 9000,
                    'placeholder' => 'Max',
                ],
                'html5' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => false,
            'method' => 'GET',
            'translation_domain' => 'forms',
        ]);
    }
}
