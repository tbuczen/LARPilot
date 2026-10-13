<?php

namespace App\Domain\Integrations\Form\Integrations;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpreadsheetMetaFormType extends AbstractColumnMappingType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSheetNameField($builder, $options['sheet_choices']);
        $this->addColumnField($builder, 'endColumn', ['label' => 'mapping.spreadsheet.end_column'], $options['column_choices']);
        $builder->add('startingRow', IntegerType::class, [
            'empty_data' => '2',
            'label' => 'mapping.spreadsheet.starting_row',
            'attr' => ['placeholder' => '2', 'min' => 1],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'sheet_choices' => null,
        ]);
        $resolver->setAllowedTypes('sheet_choices', ['null', 'array']);
    }

    /**
     * @param array<string, string>|null $sheetChoices
     */
    private function addSheetNameField(FormBuilderInterface $builder, ?array $sheetChoices): void
    {
        if ($sheetChoices === null || $sheetChoices === []) {
            $builder->add('sheetName', TextType::class, [
                'label' => 'mapping.spreadsheet.sheet_name',
            ]);
            return;
        }

        $builder->add('sheetName', ChoiceType::class, [
            'label' => 'mapping.spreadsheet.sheet_name',
            'choices' => $sheetChoices,
            'choice_translation_domain' => false,
        ]);
    }
}
