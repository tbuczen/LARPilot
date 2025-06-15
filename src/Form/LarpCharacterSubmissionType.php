<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\LarpCharacterSubmission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class LarpCharacterSubmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('contactEmail', EmailType::class, [
                'required' => false,
                'label' => 'form.contact_email',
            ])
            ->add('favouriteStyle', TextareaType::class, [
                'required' => false,
                'label' => 'form.favourite_style',
            ])
            ->add('triggers', TextareaType::class, [
                'required' => false,
                'label' => 'form.triggers',
            ])
            ->add('choices', CollectionType::class, [
                'entry_type' => LarpCharacterSubmissionChoiceType::class,
                'entry_options' => ['larp' => $larp],
                'allow_add' => false,
                'allow_delete' => false,
                'label' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpCharacterSubmission::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);
        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
