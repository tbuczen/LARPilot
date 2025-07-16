<?php

namespace App\Form;

use App\Entity\LarpIncident;
use App\Service\Larp\ParticipantCodeValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpIncidentType extends AbstractType
{
    public function __construct(private readonly ParticipantCodeValidator $validator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reportCode', TextType::class, [
                'label' => 'form.incident.report_code',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.incident.description',
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('allowFeedback', CheckboxType::class, [
                'label' => 'form.incident.allow_feedback',
                'required' => false,
            ])
            ->add('contactAccused', CheckboxType::class, [
                'label' => 'form.incident.contact_accused',
                'required' => false,
            ])
            ->add('allowMediator', CheckboxType::class, [
                'label' => 'form.incident.allow_mediator',
                'required' => false,
            ])
            ->add('stayAnonymous', CheckboxType::class, [
                'label' => 'form.incident.stay_anonymous',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpIncident::class,
            'translation_domain' => 'forms',
        ]);
    }
}
