<?php

namespace App\Domain\StoryObject\Form;

use App\Domain\Core\Entity\Enum\Gender;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Repository\FactionRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];
        /** @var LarpParticipant|null $currentParticipant */
        $currentParticipant = $options['current_participant'];

        // Check if current user can assign story writers
        $canAssignStoryWriter = false;
        if ($currentParticipant) {
            $roles = $currentParticipant->getRoles();
            $canAssignStoryWriter = in_array(ParticipantRole::ORGANIZER, $roles, true)
                || in_array(ParticipantRole::MAIN_STORY_WRITER, $roles, true);
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'character.name',
            ])
            ->add('inGameName', TextType::class, [
                'label' => 'character.in_game_name',
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'character.gender',
                'choices' => Gender::cases(),
                'choice_label' => fn (Gender $gender) => $gender->name,
                'choice_value' => fn (?Gender $gender) => $gender?->value,
                'required' => false,
            ])
            ->add('preferredGender', ChoiceType::class, [
                'label' => 'character.preferred_gender',
                'choices' => Gender::cases(),
                'choice_label' => fn (Gender $gender) => $gender->name,
                'choice_value' => fn (?Gender $gender) => $gender?->value,
                'required' => false,
                'placeholder' => 'character.preferred_gender_placeholder',
                'help' => 'character.preferred_gender_help',
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'label' => 'character.tag',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'choose',
                'tom_select_options' => [
                    'create' => true,
                    'persist' => false,
                ],
                'query_builder' => fn (TagRepository $repo): QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'character.description',
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('availableForRecruitment', CheckboxType::class, [
                'label' => 'character.available_for_recruitment',
                'required' => false,
            ])
            ->add('factions', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'label' => 'character.faction',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'character.choose_faction',
                'query_builder' => fn (FactionRepository $repo): QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
            ])
            ->add('storyWriter', EntityType::class, [
                'class' => LarpParticipant::class,
                'choice_label' => fn (LarpParticipant $participant) => $participant->getUser()?->getUsername() ?? 'Unknown',
                'label' => 'character.story_writer',
                'required' => false,
                'autocomplete' => true,
                'placeholder' => 'character.choose_story_writer',
                'disabled' => !$canAssignStoryWriter,
                'help' => $canAssignStoryWriter ? null : 'character.story_writer_permission_denied',
                'query_builder' => function (LarpParticipantRepository $repo) use ($larp): QueryBuilder {
                    $qb = $repo->createQueryBuilder('p')
                        ->join('p.user', 'u')
                        ->addSelect('u')
                        ->andWhere('p.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('u.username', 'ASC');

                    $roles = ParticipantRole::getStoryWriters();
                    $orX = $qb->expr()->orX();

                    foreach ($roles as $i => $role) {
                        $orX->add("JSONB_EXISTS(p.roles, :role_$i) = true");
                        $qb->setParameter("role_$i", $role);
                    }
                    $qb->andWhere($orX);
                    return $qb;
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
            'translation_domain' => 'forms',
            'larp' => null,
            'current_participant' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
        $resolver->setAllowedTypes('current_participant', ['null', LarpParticipant::class]);
    }
}
