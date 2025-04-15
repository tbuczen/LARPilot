<?php

namespace App\Form\Integrations;

use App\Enum\FileMappingType;
use App\Form\Models\SpreadsheetMappingModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use Symfonycasts\DynamicForms\DependentField;

class SpreadsheetMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('mappingType', EnumType::class, [
                'class' => FileMappingType::class,
                'label' => 'Mapping type',
            ])
            ->add('sheetName', TextType::class)
            ->add('endColumn', TextType::class)
            ->add('startingRow', IntegerType::class, [
                'data' => 2,
            ])
            ->addDependent('columnMappings', 'mappingType', function (DependentField $field, ?FileMappingType $type) {
                if (!$type) {
                    return;
                }

                $field->add($type->getSubForm(), [
                    'translation_domain' => 'forms',
                ]);
            })
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SpreadsheetMappingModel::class,
        ]);
    }
}
