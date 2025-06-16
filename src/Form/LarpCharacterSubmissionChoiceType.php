<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\LarpApplicationChoice;
use App\Entity\StoryObject\LarpCharacter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpCharacterSubmissionChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('character', EntityType::class, [
                'class' => LarpCharacter::class,
                'choice_label' => 'title',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp),
                'label' => 'form.character.name',
                'placeholder' => 'form.choose',
                'autocomplete' => true,
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'form.priority',
            ])
            ->add('justification', TextareaType::class, [
                'required' => false,
                'label' => 'form.justification',
            ])
            ->add('visual', TextareaType::class, [
                'required' => false,
                'label' => 'form.visual',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpApplicationChoice::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
