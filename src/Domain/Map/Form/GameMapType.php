<?php

namespace App\Domain\Map\Form;

use App\Domain\Map\Entity\GameMap;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Range;

class GameMapType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'game_map.name',
                'attr' => [
                    'placeholder' => 'game_map.name_placeholder',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'game_map.description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'game_map.description_placeholder',
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'game_map.image_file',
                'required' => false,
                'mapped' => false,
                'help' => 'game_map.image_file_help',
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'game_map.image_file_invalid',
                    ]),
                ],
            ])
            ->add('gridRows', IntegerType::class, [
                'label' => 'game_map.grid_rows',
                'help' => 'game_map.grid_rows_help',
                'constraints' => [
                    new GreaterThan(['value' => 0]),
                    new LessThanOrEqual(['value' => 100]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                ],
            ])
            ->add('gridColumns', IntegerType::class, [
                'label' => 'game_map.grid_columns',
                'help' => 'game_map.grid_columns_help',
                'constraints' => [
                    new GreaterThan(['value' => 0]),
                    new LessThanOrEqual(['value' => 100]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                ],
            ])
            ->add('gridOpacity', NumberType::class, [
                'label' => 'game_map.grid_opacity',
                'help' => 'game_map.grid_opacity_help',
                'scale' => 2,
                'constraints' => [
                    new Range(['min' => 0, 'max' => 1]),
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.01,
                ],
            ])
            ->add('gridVisible', CheckboxType::class, [
                'label' => 'game_map.grid_visible',
                'required' => false,
                'help' => 'game_map.grid_visible_help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameMap::class,
            'translation_domain' => 'forms',
        ]);
    }
}
