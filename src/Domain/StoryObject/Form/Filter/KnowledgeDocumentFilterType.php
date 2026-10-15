<?php

namespace App\Domain\StoryObject\Form\Filter;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\FactionRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KnowledgeDocumentFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp|null $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
                'label' => 'knowledge_document.title',
                'required' => false,
            ])
            ->add('category', Filters\ChoiceFilterType::class, [
                'label' => 'knowledge_document.category.title',
                'required' => false,
                'multiple' => true,
                'choices' => [
                    'knowledge_document.category.lore' => 'lore',
                    'knowledge_document.category.history' => 'history',
                    'knowledge_document.category.rules' => 'rules',
                    'knowledge_document.category.faction' => 'faction',
                    'knowledge_document.category.world' => 'world',
                    'knowledge_document.category.other' => 'other',
                ],
            ])
            ->add('isPublic', Filters\BooleanFilterType::class, [
                'label' => 'knowledge_document.is_public',
                'required' => false,
            ]);

        if ($larp) {
            $builder
                ->add('visibleToCharacters', EntityType::class, [
                    'class' => Character::class,
                    'choice_label' => 'title',
                    'label' => 'knowledge_document.visible_to_characters',
                    'required' => false,
                    'multiple' => true,
                    'query_builder' => fn (CharacterRepository $repo) => $repo->createQueryBuilder('c')
                        ->where('c.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('c.title', 'ASC'),
                    'autocomplete' => true,
                    'data_extraction_method' => 'default',
                    'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                        if (empty($values['value'])) {
                            return null;
                        }
                        $qb = $filterQuery->getQueryBuilder();
                        $qb->join('kd.visibleTo', 'filterChar')
                            ->andWhere('filterChar IN (:filterCharacters)')
                            ->setParameter('filterCharacters', $values['value']);
                        return null;
                    },
                ])
                ->add('visibleToFactions', EntityType::class, [
                    'class' => Faction::class,
                    'choice_label' => 'title',
                    'label' => 'knowledge_document.visible_to_factions',
                    'required' => false,
                    'multiple' => true,
                    'query_builder' => fn (FactionRepository $repo) => $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('f.title', 'ASC'),
                    'autocomplete' => true,
                    'data_extraction_method' => 'default',
                    'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                        if (empty($values['value'])) {
                            return null;
                        }
                        $qb = $filterQuery->getQueryBuilder();
                        $qb->join('kd.visibleTo', 'filterFaction')
                            ->andWhere('filterFaction IN (:filterFactions)')
                            ->setParameter('filterFactions', $values['value']);
                        return null;
                    },
                ]);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'knowledge_document_filter';
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

        $resolver->setAllowedTypes('larp', ['null', Larp::class]);
    }
}
