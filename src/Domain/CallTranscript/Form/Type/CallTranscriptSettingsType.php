<?php

namespace App\Domain\CallTranscript\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CallTranscriptSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('summary', CheckboxType::class, [
            'label' => 'call_recording.settings.summary',
            'required' => false,
            'data' => $options['summary_default'],
        ]);

        if ($options['google_doc_folders'] !== []) {
            $builder->add('googleDocFolder', ChoiceType::class, [
                'label' => 'call_recording.settings.google_doc_folder',
                'required' => false,
                'placeholder' => 'call_recording.form.google_doc_none',
                'choices' => $options['google_doc_folders'],
                'choice_translation_domain' => false,
                'data' => $options['google_doc_folder_default'],
            ]);
        }

        if ($options['discord_channels'] !== []) {
            $builder->add('discordChannel', ChoiceType::class, [
                'label' => 'call_recording.settings.discord_channel',
                'help' => 'call_recording.settings.discord_channel_help',
                'required' => false,
                'placeholder' => 'call_recording.form.discord_none',
                'choices' => $options['discord_channels'],
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
