<?php

namespace App\Domain\CallTranscript\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CallRecordingUploadType extends AbstractType
{
    public const AUTO_DETECT_LANGUAGE = 'auto';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'call_recording.form.title',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'call_recording.form.language',
                'choices' => [
                    'call_recording.form.language_pl' => 'pl',
                    'call_recording.form.language_en' => 'en',
                    'call_recording.form.language_auto' => self::AUTO_DETECT_LANGUAGE,
                ],
                'data' => 'pl',
            ])
            ->add('archive', FileType::class, [
                'label' => 'call_recording.form.archive',
                'help' => 'call_recording.form.archive_help',
                'constraints' => [
                    new NotBlank(),
                    new File(extensions: ['zip']),
                ],
                'attr' => ['accept' => '.zip,application/zip'],
            ])
            ->add('summary', CheckboxType::class, [
                'label' => 'call_recording.form.summary',
                'help' => 'call_recording.form.summary_help',
                'required' => false,
                'data' => $options['summary_default'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'call_transcript',
            'summary_default' => true,
        ]);
        $resolver->setAllowedTypes('summary_default', 'bool');
    }
}
