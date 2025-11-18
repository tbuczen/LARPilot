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
                'label' => 'LARP Name',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'title',
                'placeholder' => 'Select a location',
                'required' => false,
                'autocomplete' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('maxCharacterChoices', IntegerType::class, [
                'label' => 'Max Character Choices',
                'required' => true,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'help' => 'How many characters can players select when applying (only for Character Selection mode)'
            ])
            ->add('applicationMode', EnumType::class, [
                'class' => ApplicationMode::class,
                'choice_label' => fn (ApplicationMode $mode): string => $mode->getLabel(),
                'expanded' => true,
                'label' => 'Application Mode',
                'help' => 'How players apply for this LARP',
                'attr' => [
                    'class' => 'form-check',
                    'data-controller' => 'application-mode-toggle'
                ]
            ])
            ->add('publishCharactersPublicly', CheckboxType::class, [
                'label' => 'Publish Character Gallery',
                'required' => false,
                'help' => 'Show characters publicly on the LARP page (only available in Character Selection mode)',
                'attr' => [
                    'class' => 'form-check-input',
                    'data-application-mode-toggle-target' => 'publishCheckbox'
                ]
            ])
            ->add('setting', EnumType::class, [
                'class' => LarpSetting::class,
                'choice_label' => fn (LarpSetting $setting): string => $setting->getLabel(),
                'placeholder' => 'Select a setting',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('type', EnumType::class, [
                'class' => LarpType::class,
                'choice_label' => fn (LarpType $type): string => $type->getLabel(),
                'placeholder' => 'Select a type',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('characterSystem', EnumType::class, [
                'class' => LarpCharacterSystem::class,
                'choice_label' => fn (LarpCharacterSystem $system): string => $system->getLabel(),
                'placeholder' => 'Select a character system',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('discordServerUrl', UrlType::class, [
                'label' => 'Discord Server URL',
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
                'label' => 'Facebook Event URL',
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
                'label' => 'Header Image',
                'required' => false,
                'mapped' => false,
                'help' => 'Upload a background image for your LARP (like a Facebook event cover). Max 5MB. Accepted formats: JPG, PNG, GIF, WebP.',
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
                        'mimeTypesMessage' => 'Please upload a valid image file (JPG, PNG, GIF, or WebP)',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Update LARP Properties',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Larp::class,
        ]);
    }
}
