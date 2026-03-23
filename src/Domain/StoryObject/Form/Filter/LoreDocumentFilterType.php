<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Form\Filter;

use App\Domain\StoryObject\Entity\Enum\LoreDocumentCategory;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\TextFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoreDocumentFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextFilterType::class, [
                'required' => false,
                'label' => 'title',
                'attr' => [
                    'placeholder' => 'search_by_title',
                ],
            ])
            ->add('category', ChoiceFilterType::class, [
                'required' => false,
                'label' => 'category',
                'choices' => array_combine(
                    array_map(fn (LoreDocumentCategory $c) => $c->getLabel(), LoreDocumentCategory::cases()),
                    array_map(fn (LoreDocumentCategory $c) => $c->value, LoreDocumentCategory::cases())
                ),
                'placeholder' => 'all_categories',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
        ]);
    }
}
