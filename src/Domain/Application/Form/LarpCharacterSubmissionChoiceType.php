<?php

namespace App\Domain\Application\Form;

use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;
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
                'class' => Character::class,
                'choice_label' => 'title',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp),
                'label' => 'character.name',
                'placeholder' => 'choose',
                'autocomplete' => true,
                'required' => false,
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'priority',
            ])
            ->add('justification', TextareaType::class, [
                'required' => false,
                'label' => 'justification',
            ])
            ->add('visual', TextareaType::class, [
                'required' => false,
                'label' => 'visual',
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
