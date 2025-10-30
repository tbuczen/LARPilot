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
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'data-controller' => 'wysiwyg',
                ]
            ])
            ->add('address', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('country', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('postalCode', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('latitude', NumberType::class, [
                'required' => false,
                'scale' => 8,
                'attr' => ['class' => 'form-control', 'step' => 0.00000001]
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'scale' => 8,
                'attr' => ['class' => 'form-control', 'step' => 0.00000001]
            ])
            ->add('website', UrlType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('facebook', UrlType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('instagram', UrlType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('twitter', UrlType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contactEmail', EmailType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contactPhone', TelType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('facilities', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('accessibility', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('parkingInfo', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            ->add('publicTransport', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            ->add('capacity', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('isPublic', CheckboxType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('isActive', CheckboxType::class, [
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
            'show_captcha' => false, // Only show CAPTCHA for global location creation
        ]);
    }
}
