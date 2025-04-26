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

        /** @var string|null $mimeType */
        $mimeType = $options['mimeType'];
        $allowedTypes = $this->getAllowedResourceTypes($mimeType);

        $builder
            ->add('mappingType', EnumType::class, [
                'class' => ResourceType::class,
                'label' => 'Mapping type',
                'choices' => $allowedTypes,
            ])
            ->addDependent('meta', 'mappingType', function (DependentField $field, ?ResourceType $type) use ($mimeType) {
                if (!$type) {
                    return;
                }
                $type = $this->getType($mimeType, $type);

                $form = $type->getMetaForm();
                if ($form !== null) {
                    $field->add($form);
                }
            })
            ->addDependent('mappings', 'mappingType', function (DependentField $field, ?ResourceType $type) use ($mimeType) {
                if (!$type) {
                    return;
                }
                $type = $this->getType($mimeType, $type);

                $form = $type->getSubForm();
                if ($form !== null) {
                    $field->add($form);
                }
            })
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExternalResourceMappingModel::class,
            'mimeType' => null,
            'translation_domain' => 'forms',
        ]);
    }

    public function getAllowedResourceTypes(?string $mimeType): array
    {
        if (!$mimeType) {
            return ResourceType::cases(); // fallback if no mimeType known
        }

        return array_filter(ResourceType::cases(), function (ResourceType $type) use ($mimeType) {
            // Example: you define your own matching rules here
            if (str_starts_with($mimeType, 'application/vnd.google-apps.folder')) {
                return $type->isFolderMapping();
            }
            if (str_starts_with($mimeType, 'application/vnd.google-apps.spreadsheet')) {
                return $type->isSpreadsheet();
            }
            if (str_starts_with($mimeType, 'application/vnd.google-apps.document')) {
                return $type->isDocument();
            }

            return false; // unknown types excluded
        });
    }

    private function getType(?string $mimeType, ResourceType $type): ResourceType
    {
        $allowedTypes = $this->getAllowedResourceTypes($mimeType);
        if (!in_array($type, $allowedTypes, true)) {
            $type = reset($allowedTypes);
        }
        return $type;
    }
}
