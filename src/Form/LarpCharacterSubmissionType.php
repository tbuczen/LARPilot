<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\LarpApplication;
use App\Entity\Tag;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

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
                'label' => 'form.contact_email',
                'help' => 'form.contact_email_help',
            ])
            ->add('favouriteStyle', TextareaType::class, [
                'required' => false,
                'label' => 'form.favourite_style',
                'help' => 'form.favourite_style_help',
                'attr' => ['rows' => 3],
            ])
            ->add('preferredTags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'label' => 'form.preferred_tags',
                'help' => 'form.preferred_tags_help',
                'autocomplete' => true,
                'query_builder' => function (EntityRepository $er) use ($larp) {
                    return $er->createQueryBuilder('t')
                        ->where('t.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('t.title', 'ASC');
                },
            ])
            ->add('unwantedTags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'label' => 'form.unwanted_tags',
                'help' => 'form.unwanted_tags_help',
                'autocomplete' => true,
                'query_builder' => function (EntityRepository $er) use ($larp) {
                    return $er->createQueryBuilder('t')
                        ->where('t.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('t.title', 'ASC');
                },
            ])
            ->add('choices', CollectionType::class, [
                'entry_type' => LarpCharacterSubmissionChoiceType::class,
                'entry_options' => ['larp' => $larp],
                'allow_add' => false,
                'allow_delete' => false,
                'label' => 'form.character_choices',
                'help' => 'form.character_choices_help',
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