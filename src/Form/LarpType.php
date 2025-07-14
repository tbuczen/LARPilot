<?php

namespace App\Form;

use App\Entity\Larp;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.larp.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.larp.description',
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'form.larp.location',
                'required' => false,
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'form.larp.start_date',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'form.larp.end_date',
                'widget' => 'single_text',
            ])
            ->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => array(
                    new RecaptchaTrue()
                ),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'forms',
            'data_class' => Larp::class,
        ]);
    }
}
