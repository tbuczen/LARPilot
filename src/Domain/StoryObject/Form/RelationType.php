<?php

namespace App\Domain\StoryObject\Form;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Enum\RelationType as RelationKind;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\StoryObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class RelationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        /** @var StoryObject|null $contextOwner If this is set - the field should not be editable */
        $contextOwner = $options['contextOwner'];
        [$disableFrom, $disableTo] = $this->getDisabledFieldList($builder, $contextOwner);


        $builder = new DynamicFormBuilder($builder);
        $builder
            ->add('title', TextType::class, [
                'label' => 'relation.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'relation.description',
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('relationType', ChoiceType::class, [
                'label' => 'relation.type',
                'choices' => RelationKind::cases(),
                'choice_label' => fn (RelationKind $type) => $type->name,
                'choice_value' => fn (?RelationKind $type) => $type?->value,
                'required' => true,
            ])
            ->add('fromType', ChoiceType::class, [
                'label' => 'relation.fromType',
                'choices' => TargetType::getAvailableForRelations(),
                'choice_label' => fn (TargetType $type) => $type->name,
                'choice_value' => fn (?TargetType $type) => $type?->value,
                'required' => true,
                'placeholder' => 'choose',
                'disabled' => $disableFrom
            ])
            ->addDependent('from', 'fromType', $this->getClosure($larp, $disableFrom, $contextOwner))
            ->add('toType', ChoiceType::class, [
                'label' => 'relation.toType',
                'choices' => TargetType::getAvailableForRelations(),
                'choice_label' => fn (TargetType $type) => $type->name,
                'choice_value' => fn (?TargetType $type) => $type?->value,
                'required' => true,
                'placeholder' => 'choose',
                'disabled' => $disableTo
            ])
            ->addDependent('to', 'toType', $this->getClosure($larp, $disableTo, $contextOwner))
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => Relation::class,
            'translation_domain' => 'forms',
            'larp' => null,
            'contextOwner' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
        $resolver->setAllowedTypes('contextOwner', StoryObject::class);
    }

    private function getClosure(Larp $larp, bool $disable, ?StoryObject $contextOwner = null): \Closure
    {
        return function (DependentField $field, ?TargetType $type) use ($larp, $disable, $contextOwner): void {
            if (!$type instanceof TargetType) {
                return;
            }
            $field->add(EntityType::class, [
                'class' => $type->getEntityClass(),
                'choice_label' => 'title',
                'required' => false,
                'autocomplete' => true,
                'multiple' => false,
                'placeholder' => 'choose',
                'label' => 'relation.from', //TODO: Change
                'query_builder' => function (BaseRepository $repo) use ($larp, $disable, $contextOwner): \Doctrine\ORM\QueryBuilder {
                    $qb = $repo->createQueryBuilder('o')
                        ->where('o.larp = :larp')
                        ->setParameter('larp', $larp);

                    if ($contextOwner instanceof StoryObject && !$disable) {
                        $qb->andWhere('o != :self')
                            ->setParameter('self', $contextOwner);
                    }

                    return $qb;
                },
                'disabled' => $disable,
                'attr' => [
                    'data-loading-class' => 'is-loading',
                ],
            ]);
        };
    }

    /**
     * @return bool[]
     */
    private function getDisabledFieldList(FormBuilderInterface $builder, ?StoryObject $contextOwner): array
    {
        /** @var Relation|null $relation */
        $relation = $builder->getData();

        $isEditing = $relation && null !== $relation->getId();
        $disableFrom = false;
        $disableTo = false;

        if ($isEditing && $contextOwner instanceof StoryObject) {
            if ($relation->getFrom() === $contextOwner) {
                $disableFrom = true;
            } elseif ($relation->getTo() === $contextOwner) {
                $disableTo = true;
            }
        }
        return [$disableFrom, $disableTo];
    }
}
