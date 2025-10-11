<?php

namespace App\Form;

use App\Entity\Enum\LocationType;
use App\Entity\MapLocation;
use App\Entity\StoryObject\Place;
use App\Form\DataTransformer\JsonToArrayTransformer;
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
                'label' => 'form.map_location.name',
                'attr' => [
                    'placeholder' => 'form.map_location.name_placeholder',
                ],
            ])
            ->add('gridCoordinates', HiddenType::class, [
                'label' => 'form.map_location.grid_coordinates',
                'help' => 'form.map_location.grid_coordinates_help',
                'attr' => [
                    'data-map-location-target' => 'coordinates',
                ],
            ])
            ->add('type', EnumType::class, [
                'label' => 'form.map_location.type',
                'class' => LocationType::class,
                'required' => false,
                'placeholder' => 'form.map_location.type_placeholder',
                'choice_label' => fn (LocationType $type) => 'enum.location_type.' . $type->value,
            ])
            ->add('color', ColorType::class, [
                'label' => 'form.map_location.color',
                'required' => false,
                'help' => 'form.map_location.color_help',
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'form.map_location.capacity',
                'required' => false,
                'help' => 'form.map_location.capacity_help',
                'constraints' => [
                    new GreaterThan(['value' => 0]),
                ],
                'attr' => [
                    'min' => 1,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.map_location.description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'form.map_location.description_placeholder',
                    'data-controller' => 'wysiwyg',
                ],
            ])
        ;

        if ($larp) {
            $builder->add('place', EntityType::class, [
                'label' => 'form.map_location.place',
                'choice_label' => 'title',
                'class' => Place::class,
                'required' => false,
                'placeholder' => 'form.map_location.place_placeholder',
                'help' => 'form.map_location.place_help',
                'query_builder' => function ($repository) use ($larp) {
                    return $repository->createQueryBuilder('p')
                        ->where('p.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('p.title', 'ASC');
                },
                'autocomplete' => true,
            ]);
        }

        $builder->get('gridCoordinates')
            ->addModelTransformer(new JsonToArrayTransformer());

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
