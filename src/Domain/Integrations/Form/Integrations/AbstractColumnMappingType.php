<?php

namespace App\Domain\Integrations\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractColumnMappingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'forms',
            'column_choices' => null,
        ]);
        $resolver->setAllowedTypes('column_choices', ['null', 'array']);
    }

    /**
     * @param array<string, mixed> $fieldOptions
     * @param array<string, string>|null $columnChoices
     */
    protected function addColumnField(FormBuilderInterface $builder, string $fieldName, array $fieldOptions, ?array $columnChoices): void
    {
        if ($columnChoices === null || $columnChoices === []) {
            $builder->add($fieldName, TextType::class, $fieldOptions);
            return;
        }

        $builder->add($fieldName, ChoiceType::class, array_merge($fieldOptions, [
            'choices' => $columnChoices,
            'choice_translation_domain' => false,
            'placeholder' => 'mapping.spreadsheet.column_placeholder',
        ]));
    }
}
