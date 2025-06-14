<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\StoryObject\Item;
use Money\Currency;
use Money\Money;
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
                'label' => 'form.item.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.item.description',
                'required' => false,
            ])
            ->add('isCrafted', CheckboxType::class, [
                'label' => 'form.item.is_crafted',
                'required' => false,
            ])
            ->add('isPurchased', CheckboxType::class, [
                'label' => 'form.item.is_purchased',
                'required' => false,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'form.item.quantity',
            ])
            ->add('cost', MoneyType::class, [
                'label' => 'form.item.cost',
                'currency' => 'USD',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
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
