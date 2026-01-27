<?php

namespace App\Domain\Core\Form;

use App\Domain\Core\Entity\Location;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'location_form.title',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'location_form.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'data-controller' => 'wysiwyg',
                ]
            ])
            ->add('address', TextType::class, [
                'label' => 'location_form.address',
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => 'location_form.city',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('country', TextType::class, [
                'label' => 'location_form.country',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'location_form.postal_code',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'location_form.latitude',
                'required' => false,
                'scale' => 8,
                'html5' => true,
                'attr' => ['class' => 'form-control', 'step' => 0.00000001]
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'location_form.longitude',
                'required' => false,
                'scale' => 8,
                'html5' => true,
                'attr' => ['class' => 'form-control', 'step' => 0.00000001]
            ])
            ->add('website', UrlType::class, [
                'label' => 'location_form.website',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('facebook', UrlType::class, [
                'label' => 'location_form.facebook',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('instagram', UrlType::class, [
                'label' => 'location_form.instagram',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('twitter', UrlType::class, [
                'label' => 'location_form.twitter',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'location_form.contact_email',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contactPhone', TelType::class, [
                'label' => 'location_form.contact_phone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('facilities', TextareaType::class, [
                'label' => 'location_form.facilities',
                'help' => 'location_form.facilities_help',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('accessibility', TextareaType::class, [
                'label' => 'location_form.accessibility',
                'help' => 'location_form.accessibility_help',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('parkingInfo', TextareaType::class, [
                'label' => 'location_form.parking_info',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            ->add('publicTransport', TextareaType::class, [
                'label' => 'location_form.public_transport',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'location_form.capacity',
                'help' => 'location_form.capacity_help',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 9000,
                ],
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'location_form.is_public',
                'help' => 'location_form.is_public_help',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'location_form.is_active',
                'help' => 'location_form.is_active_help',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
        ;

        // Add reCAPTCHA for global location creation to prevent spam
        if ($options['show_captcha']) {
            $builder->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'translation_domain' => 'forms',
            'show_captcha' => false,
        ]);
    }
}
