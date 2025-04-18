<?php

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;

class LarpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Larp Name',
                'translation_domain' => 'forms',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'translation_domain' => 'forms',
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'translation_domain' => 'forms',
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'translation_domain' => 'forms',
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'translation_domain' => 'forms',
            ])
            ->add('recaptcha', EWZRecaptchaType::class, array(
                'attr'        => array(
                    'options' => array(
                        'theme' => 'light',
                        'type'  => 'image',
                        'size'  => 'normal'
                    )
                ),
                'mapped'      => false,
                'constraints' => array(
                    new RecaptchaTrue()
                ),
                'translation_domain' => 'forms',
            ));
        ;
    }
}
