<?php

namespace App\Form\Integrations;

use App\Entity\Enum\ResourceType;
use App\Form\Models\ExternalResourceMappingModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class FileMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('mappingType', EnumType::class, [
                'class' => ResourceType::class,
                'label' => 'Mapping type',
            ])
            ->addDependent('mappings', 'mappingType', function (DependentField $field, ?ResourceType $type) {
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
            'data_class' => ExternalResourceMappingModel::class,
        ]);
    }
}
