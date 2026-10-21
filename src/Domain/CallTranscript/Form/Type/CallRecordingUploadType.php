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

        $googleDocFolders = $options['google_doc_folders'];
        if ($googleDocFolders !== []) {
            $builder->add('googleDocFolder', ChoiceType::class, [
                'label' => 'call_recording.form.google_doc_folder',
                'help' => 'call_recording.form.google_doc_folder_help',
                'required' => false,
                'placeholder' => 'call_recording.form.google_doc_none',
                'choices' => $googleDocFolders,
                'choice_translation_domain' => false,
                'data' => $options['google_doc_folder_default'],
            ]);
        }

        $discordChannels = $options['discord_channels'];
        if ($discordChannels !== []) {
            $builder->add('discordChannel', ChoiceType::class, [
                'label' => 'call_recording.form.discord_channel',
                'help' => $options['discord_can_post'] ? 'call_recording.form.discord_channel_help' : 'call_recording.form.discord_channel_missing_permissions',
                'required' => false,
                'placeholder' => 'call_recording.form.discord_none',
                'choices' => $discordChannels,
                'choice_translation_domain' => false,
                'data' => $options['discord_channel_default'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'call_transcript',
            'summary_default' => true,
            'google_doc_folders' => [],
            'google_doc_folder_default' => null,
            'discord_channels' => [],
            'discord_channel_default' => null,
            'discord_can_post' => true,
        ]);
        $resolver->setAllowedTypes('summary_default', 'bool');
        $resolver->setAllowedTypes('google_doc_folders', 'array');
        $resolver->setAllowedTypes('google_doc_folder_default', ['null', 'string']);
        $resolver->setAllowedTypes('discord_channels', 'array');
        $resolver->setAllowedTypes('discord_channel_default', ['null', 'string']);
        $resolver->setAllowedTypes('discord_can_post', 'bool');
    }
}
