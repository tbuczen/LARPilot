<?php

namespace App\Domain\Application\Form;

use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpCharacterSubmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        //        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('contactEmail', EmailType::class, [
                'required' => false,
                'label' => 'contact_email',
                'help' => 'contact_email_help',
            ])
            ->add('favouriteStyle', TextareaType::class, [
                'required' => false,
                'label' => 'favourite_style',
                'help' => 'favourite_style_help',
                'attr' => ['rows' => 3],
            ])
            ->add('preferredTags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'label' => 'preferred_tags',
                'help' => 'preferred_tags_help',
                'autocomplete' => true,
                'query_builder' => fn (EntityRepository $er): \Doctrine\ORM\QueryBuilder => $er->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('t.title', 'ASC'),
            ])
            ->add('unwantedTags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'label' => 'unwanted_tags',
                'help' => 'unwanted_tags_help',
                'autocomplete' => true,
                'query_builder' => fn (EntityRepository $er): \Doctrine\ORM\QueryBuilder => $er->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('t.title', 'ASC'),
            ])
            ->add('choices', CollectionType::class, [
                'entry_type' => LarpCharacterSubmissionChoiceType::class,
                'entry_options' => ['larp' => $larp],
                'allow_add' => false,
                'allow_delete' => false,
                'label' => 'character_choices',
                'help' => 'character_choices_help',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LarpApplication::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);
        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
