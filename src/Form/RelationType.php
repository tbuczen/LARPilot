<?php

namespace App\Form;

use App\Entity\Enum\RelationType as RelationKind;
use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\StoryObject\Relation;
use App\Entity\StoryObject\StoryObject;
use App\Repository\BaseRepository;
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
        list($disableFrom, $disableTo) = $this->getDisabledFieldList($builder, $contextOwner);


        $builder = new DynamicFormBuilder($builder);
        $builder
            ->add('title', TextType::class, [
                'label' => 'form.relation.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.relation.description',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'form.relation.type',
                'choices' => RelationKind::cases(),
                'choice_label' => fn (RelationKind $type) => $type->name,
                'choice_value' => fn (?RelationKind $type) => $type?->value,
                'required' => true,
            ])

            ->add('fromType', ChoiceType::class, [
                'label' => 'form.relation.fromType',
                'choices' => TargetType::getAvailableForRelations(),
                'choice_label' => fn (TargetType $type) => $type->name,
                'choice_value' => fn (?TargetType $type) => $type?->value,
                'required' => true,
                'placeholder' => 'form.choose',
                'disabled' => $disableFrom
            ])
            ->addDependent('from', 'fromType', $this->getClosure($larp, $disableFrom, $contextOwner))
            ->add('toType', ChoiceType::class, [
                'label' => 'form.relation.toType',
                'choices' => TargetType::getAvailableForRelations(),
                'choice_label' => fn (TargetType $type) => $type->name,
                'choice_value' => fn (?TargetType $type) => $type?->value,
                'required' => true,
                'placeholder' => 'form.choose',
                'disabled' => $disableTo
            ])
            ->addDependent('to', 'toType', $this->getClosure($larp, $disableTo, $contextOwner))
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
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
        return function (DependentField $field, ?TargetType $type) use ($larp, $disable, $contextOwner) {
            if (!$type) {
                return;
            }
            $field->add(EntityType::class, [
                'class' => $type->getEntityClass(),
                'choice_label' => 'title',
                'required' => false,
                'autocomplete' => true,
                'multiple' => false,
                'placeholder' => 'form.choose',
                'label' => 'form.relation.from', //TODO: Change
                'query_builder' => function (BaseRepository $repo) use ($larp, $disable, $contextOwner) {
                    $qb = $repo->createQueryBuilder('o')
                        ->where('o.larp = :larp')
                        ->setParameter('larp', $larp);

                    if ($contextOwner && !$disable) {
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

        if ($isEditing && $contextOwner) {
            if ($relation->getFrom() === $contextOwner) {
                $disableFrom = true;
            } elseif ($relation->getTo() === $contextOwner) {
                $disableTo = true;
            }
        }
        return array($disableFrom, $disableTo);
    }
}
