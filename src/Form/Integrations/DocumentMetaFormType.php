<?php

namespace App\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DocumentMetaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('headerType', TextType::class, [
                'label' => 'form.mapping.document.header_type',
                'translation_domain' => 'forms',
            ])
            ->add('startingParagraph', IntegerType::class, [
                'data' => 2,
                'label' => 'form.mapping.document.starting_paragraph',
                'translation_domain' => 'forms',
            ]);
    }
}
