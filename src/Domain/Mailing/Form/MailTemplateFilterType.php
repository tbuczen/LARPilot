<?php

namespace App\Domain\Mailing\Form;

use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailTemplateFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
                'label' => 'name',
            ])
            ->add('type', Filters\EnumFilterType::class, [
                'class' => MailTemplateType::class,
                'placeholder' => 'choose',
                'required' => false,
                'label' => 'mail_template.type',
                'choice_label' => static fn (MailTemplateType $choice): string => $choice->label(),
            ])
            ->add('enabled', Filters\BooleanFilterType::class, [
                'label' => 'mail_template.enabled',
                'required' => false,
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
