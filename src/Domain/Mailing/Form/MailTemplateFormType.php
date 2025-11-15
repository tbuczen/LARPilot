<?php

namespace App\Domain\Mailing\Form;

use App\Domain\Mailing\Dto\MailTemplateDefinition;
use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Entity\MailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailTemplateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var MailTemplateDefinition|null $definition */
        $definition = $options['definition'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'mail_template.name',
                'required' => true,
            ])
            ->add('type', EnumType::class, [
                'class' => MailTemplateType::class,
                'label' => 'mail_template.type',
                'disabled' => true,
                'choice_label' => static fn (MailTemplateType $choice): string => $choice->label(),
            ])
            ->add('subject', TextType::class, [
                'label' => 'mail_template.subject',
                'required' => true,
                'help' => $definition?->description,
            ])
            ->add('body', TextareaType::class, [
                'label' => 'mail_template.body',
                'attr' => [
                    'rows' => 12,
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'mail_template.enabled',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'save',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MailTemplate::class,
            'translation_domain' => 'forms',
            'definition' => null,
        ]);

        $resolver->setAllowedTypes('definition', [MailTemplateDefinition::class, 'null']);
    }
}
