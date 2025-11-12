<?php

namespace App\Domain\StoryObject\Form;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Form\DataTransformer\MoneyToFloatTransformer;
use App\Domain\StoryObject\Entity\Item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'item.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'item.description',
                'required' => false,
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('isCrafted', CheckboxType::class, [
                'label' => 'item.is_crafted',
                'required' => false,
            ])
            ->add('isPurchased', CheckboxType::class, [
                'label' => 'item.is_purchased',
                'required' => false,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'item.quantity',
            ])
            ->add('cost', MoneyType::class, [
                'label' => 'item.cost',
                'currency' => 'USD',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
            ])
            ->get('cost')->addModelTransformer(new MoneyToFloatTransformer())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
