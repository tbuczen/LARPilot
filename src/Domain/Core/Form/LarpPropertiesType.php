<?php

namespace App\Domain\Core\Form;

use App\Domain\Core\Entity\Enum\LarpCharacterSystem;
use App\Domain\Core\Entity\Enum\LarpSetting;
use App\Domain\Core\Entity\Enum\LarpType;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Location;
use App\Domain\Survey\Entity\Enum\ApplicationMode;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Url;

class LarpPropertiesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'larp.name',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'larp.start_date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'larp.end_date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('location', EntityType::class, [
                'label' => 'larp.location',
                'class' => Location::class,
                'choice_label' => 'title',
                'placeholder' => 'larp.select_location',
                'required' => false,
                'autocomplete' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('maxCharacterChoices', IntegerType::class, [
                'label' => 'larp.max_character_choices',
                'required' => true,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'help' => 'larp.max_character_choices_help'
            ])
            ->add('applicationMode', EnumType::class, [
                'class' => ApplicationMode::class,
                'choice_label' => fn (ApplicationMode $mode): string => $mode->getLabel(),
                'expanded' => true,
                'label' => 'larp.application_mode',
                'help' => 'larp.application_mode_help',
                'attr' => [
                    'class' => 'form-check',
                    'data-controller' => 'application-mode-toggle',
                    'data-action' => 'change->application-mode-toggle#change'
                ]
            ])
            ->add('publishCharactersPublicly', CheckboxType::class, [
                'label' => 'larp.publish_characters_publicly',
                'required' => false,
                'help' => 'larp.publish_characters_publicly_help',
                'attr' => [
                    'class' => 'form-check-input',
                    'data-application-mode-toggle-target' => 'publishCheckbox'
                ]
            ])
            ->add('setting', EnumType::class, [
                'class' => LarpSetting::class,
                'choice_label' => fn (LarpSetting $setting): string => $setting->getLabel(),
                'label' => 'larp.setting',
                'placeholder' => 'larp.select_setting',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('type', EnumType::class, [
                'class' => LarpType::class,
                'choice_label' => fn (LarpType $type): string => $type->getLabel(),
                'label' => 'larp.type',
                'placeholder' => 'larp.select_type',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('characterSystem', EnumType::class, [
                'class' => LarpCharacterSystem::class,
                'choice_label' => fn (LarpCharacterSystem $system): string => $system->getLabel(),
                'label' => 'larp.character_system',
                'placeholder' => 'larp.select_character_system',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'larp.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('discordServerUrl', UrlType::class, [
                'label' => 'larp.discord_server_url',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://discord.gg/...',
                ],
                'constraints' => [
                    new Url(),
                ],
            ])
            ->add('facebookEventUrl', UrlType::class, [
                'label' => 'larp.facebook_event_url',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://facebook.com/events/...',
                ],
                'constraints' => [
                    new Url(),
                ],
            ])
            ->add('headerImageFile', FileType::class, [
                'label' => 'larp.header_image',
                'required' => false,
                'mapped' => false,
                'help' => 'larp.header_image_help',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'larp.header_image_invalid',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'larp.update_properties',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Larp::class,
            'translation_domain' => 'forms',
        ]);
    }
}
