<?php

namespace App\Form;

use App\Entity\Enum\ReferenceRole;
use App\Entity\Enum\ReferenceType;
use App\Entity\Enum\TargetType;
use App\Entity\ExternalReference;
use App\Entity\Larp;
use App\Repository\BaseRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ExternalReferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('name', TextType::class, [
                'label' => 'form.external_reference.name',
            ])
            ->add('url', TextType::class, [
                'label' => 'form.external_reference.url',
            ])
            ->add('referenceType', ChoiceType::class, [
                'label' => 'form.external_reference.mapping_type',
                'choices' => ReferenceType::cases(),
                'choice_label' => fn (ReferenceType $choice) => $choice->name,
                'choice_value' => fn (?ReferenceType $choice) => $choice?->value,
                'required' => true,
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'form.external_reference.reference_type',
                'choices' => ReferenceRole::cases(),
                'choice_label' => fn (ReferenceRole $choice) => $choice->name,
                'choice_value' => fn (?ReferenceRole $choice) => $choice?->value,
                'required' => true,
            ])
            ->add('storyObjectType', ChoiceType::class, [
                'label' => 'form.external_reference.toType',
                'choices' => TargetType::cases(),
                'choice_label' => fn (TargetType $type) => $type->name,
                'choice_value' => fn (?TargetType $type) => $type?->value,
                'required' => true,
                'placeholder' => 'form.choose',
            ])
            ->addDependent('storyObject', 'storyObjectType', function (DependentField $field, ?TargetType $type) use ($larp): void {
                if (!$type instanceof TargetType) {
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
                    'query_builder' => function (BaseRepository $repo) use ($larp): \Doctrine\ORM\QueryBuilder {
                        $qb = $repo->createQueryBuilder('o')
                            ->where('o.larp = :larp')
                            ->setParameter('larp', $larp);
                        return $qb;
                    },
                    'attr' => [
                        'data-loading-class' => 'is-loading',
                    ],
                ]);
            })
        ->add('submit', SubmitType::class, [
            'label' => 'form.submit',
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExternalReference::class,
            'translation_domain' => 'forms',
            'csrf_protection' => false,
        ]);
        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
