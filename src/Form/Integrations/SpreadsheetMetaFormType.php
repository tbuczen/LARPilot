<?php

namespace App\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SpreadsheetMetaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sheetName', TextType::class, [
                'label' => 'form.mapping.spreadsheet.sheet_name',
                'translation_domain' => 'forms',
            ])
            ->add('endColumn', TextType::class, [
                'label' => 'form.mapping.spreadsheet.end_column',
                'translation_domain' => 'forms',
            ])
            ->add('startingRow', IntegerType::class, [
                'data' => 2,
                'label' => 'form.mapping.spreadsheet.starting_row',
                'translation_domain' => 'forms',
            ]);
    }
}
