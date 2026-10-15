<?php

namespace App\Domain\Integrations\Form\Filter;

use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Entity\ExternalItemSuggestion;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationInboxFilterType extends AbstractType
{
    public const HAS_SUGGESTION_YES = 'yes';

    public const HAS_SUGGESTION_NO = 'no';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var LarpIntegration $integration */
        $integration = $options['integration'];

        $builder
            ->add('channel', EntityType::class, [
                'class' => ExternalSource::class,
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'inbox.filter.all_channels',
                'label' => 'inbox.filter.channel',
                'query_builder' => static fn (ExternalSourceRepository $sourceRepository): QueryBuilder => $sourceRepository->createQueryBuilder('source')
                    ->andWhere('source.integration = :integration')
                    ->setParameter('integration', $integration)
                    ->orderBy('source.title', 'ASC'),
                'apply_filter' => static function (QueryInterface $filterQuery, string $field, array $values): null {
                    if (!$values['value'] instanceof ExternalSource) {
                        return null;
                    }

                    $filterQuery->getQueryBuilder()
                        ->andWhere('item.source = :filterChannel')
                        ->setParameter('filterChannel', $values['value']);

                    return null;
                },
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'inbox.filter.all_statuses',
                'label' => 'inbox.filter.status',
                'choices' => array_combine(
                    array_map(static fn (ExternalItemReviewStatus $status): string => 'inbox.status.' . $status->value, ExternalItemReviewStatus::cases()),
                    array_map(static fn (ExternalItemReviewStatus $status): string => $status->value, ExternalItemReviewStatus::cases()),
                ),
                'apply_filter' => static function (QueryInterface $filterQuery, string $field, array $values): null {
                    $status = is_string($values['value']) ? ExternalItemReviewStatus::tryFrom($values['value']) : null;
                    if ($status === null) {
                        return null;
                    }

                    $filterQuery->getQueryBuilder()
                        ->andWhere('item.reviewStatus = :filterStatus')
                        ->setParameter('filterStatus', $status->value);

                    return null;
                },
            ])
            ->add('hasSuggestion', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'inbox.filter.any',
                'label' => 'inbox.filter.has_suggestion',
                'choices' => [
                    'inbox.filter.with_suggestions' => self::HAS_SUGGESTION_YES,
                    'inbox.filter.without_suggestions' => self::HAS_SUGGESTION_NO,
                ],
                'apply_filter' => static function (QueryInterface $filterQuery, string $field, array $values): null {
                    if (!in_array($values['value'], [self::HAS_SUGGESTION_YES, self::HAS_SUGGESTION_NO], true)) {
                        return null;
                    }

                    $queryBuilder = $filterQuery->getQueryBuilder();
                    $suggestionExists = $queryBuilder->getEntityManager()->createQueryBuilder()
                        ->select('1')
                        ->from(ExternalItemSuggestion::class, 'filterSuggestion')
                        ->andWhere('filterSuggestion.item = item')
                        ->getDQL();
                    $queryBuilder->andWhere($values['value'] === self::HAS_SUGGESTION_YES ? 'EXISTS(' . $suggestionExists . ')' : 'NOT EXISTS(' . $suggestionExists . ')');

                    return null;
                },
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'inbox_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'discord',
        ]);

        $resolver->setRequired('integration');
        $resolver->setAllowedTypes('integration', LarpIntegration::class);
    }
}
