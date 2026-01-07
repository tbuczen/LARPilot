<?php

declare(strict_types=1);

namespace App\Domain\Map\Form;

use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\Map\Entity\Enum\LocationType;
use App\Domain\Map\Entity\Enum\MarkerShape;
use App\Domain\Map\Entity\MapLocation;
use App\Domain\StoryObject\Entity\Place;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

class MapLocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $larp = $options['larp'] ?? null;

        $builder
            ->add('name', TextType::class, [
                'label' => 'map_location.name',
                'attr' => [
                    'placeholder' => 'map_location.name_placeholder',
                ],
            ])
            ->add('positionX', HiddenType::class, [
                'attr' => [
                    'data-map-marker-editor-target' => 'positionX',
                ],
            ])
            ->add('positionY', HiddenType::class, [
                'attr' => [
                    'data-map-marker-editor-target' => 'positionY',
                ],
            ])
            ->add('shape', EnumType::class, [
                'label' => 'map_location.shape',
                'class' => MarkerShape::class,
                'choice_label' => fn (MarkerShape $shape) => 'enum.marker_shape.' . $shape->value,
                'attr' => [
                    'data-map-marker-editor-target' => 'shape',
                    'data-action' => 'change->map-marker-editor#onShapeChange',
                ],
            ])
            ->add('type', EnumType::class, [
                'label' => 'map_location.type',
                'class' => LocationType::class,
                'required' => false,
                'placeholder' => 'map_location.type_placeholder',
                'choice_label' => fn (LocationType $type) => 'enum.location_type.' . $type->value,
            ])
            ->add('color', ColorType::class, [
                'label' => 'map_location.color',
                'required' => false,
                'help' => 'map_location.color_help',
                'attr' => [
                    'data-map-marker-editor-target' => 'color',
                    'data-action' => 'change->map-marker-editor#onColorChange',
                ],
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'map_location.capacity',
                'required' => false,
                'help' => 'map_location.capacity_help',
                'constraints' => [
                    new GreaterThan(['value' => 0]),
                ],
                'attr' => [
                    'min' => 1,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'map_location.description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'map_location.description_placeholder',
                    'data-controller' => 'wysiwyg',
                ],
            ])
        ;

        if ($larp) {
            $builder
                ->add('tags', EntityType::class, [
                    'label' => 'map_location.tags',
                    'choice_label' => 'title',
                    'class' => Tag::class,
                    'required' => false,
                    'multiple' => true,
                    'autocomplete' => true,
                    'help' => 'map_location.tags_help',
                    'query_builder' => fn (TagRepository $repo) => $repo->createQueryBuilder('t')
                        ->where('t.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('t.title', 'ASC'),
                ])
                ->add('place', EntityType::class, [
                    'label' => 'map_location.place',
                    'choice_label' => 'title',
                    'class' => Place::class,
                    'required' => false,
                    'placeholder' => 'map_location.place_placeholder',
                    'help' => 'map_location.place_help',
                    'query_builder' => function ($repository) use ($larp) {
                        return $repository->createQueryBuilder('p')
                            ->where('p.larp = :larp')
                            ->setParameter('larp', $larp)
                            ->orderBy('p.title', 'ASC');
                    },
                    'autocomplete' => true,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MapLocation::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);
    }
}
