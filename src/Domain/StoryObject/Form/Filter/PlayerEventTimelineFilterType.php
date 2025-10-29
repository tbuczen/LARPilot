<?php

namespace App\Domain\StoryObject\Form\Filter;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\EventCategory;
use App\Domain\StoryObject\Entity\Faction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlayerEventTimelineFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];
        /** @var LarpParticipant|null $participant */
        $participant = $options['participant'];

        if (!$participant) {
            return;
        }

        // Get participant's characters
        $characters = $participant->getLarpCharacters();

        // Get participant's factions
        $factions = [];
        foreach ($characters as $character) {
            foreach ($character->getFactions() as $faction) {
                $factions[$faction->getId()->toRfc4122()] = $faction;
            }
        }

        $builder
            ->add('category', EnumType::class, [
                'class' => EventCategory::class,
                'label' => 'lore.filter_by_category',
                'required' => false,
                'placeholder' => 'common.all',
                'choice_label' => fn (EventCategory $category) => 'event.category.' . $category->value,
                'data_extraction_method' => 'default',
            ])
        ;

        if ($characters->count() > 0) {
            $builder->add('character', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'title',
                'label' => 'lore.filter_by_character',
                'required' => false,
                'placeholder' => 'common.all',
                'choices' => $characters->toArray(),
                'data_extraction_method' => 'default',
            ]);
        }

        if (count($factions) > 0) {
            $builder->add('faction', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'label' => 'lore.filter_by_faction',
                'required' => false,
                'placeholder' => 'common.all',
                'choices' => array_values($factions),
                'data_extraction_method' => 'default',
            ]);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'player_event_timeline_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null,
            'participant' => null,
        ]);

        $resolver->setRequired(['larp']);
        $resolver->setAllowedTypes('larp', Larp::class);
        $resolver->setAllowedTypes('participant', ['null', LarpParticipant::class]);
    }
}
