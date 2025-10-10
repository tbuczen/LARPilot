<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\RecruitmentProposal;
use App\Repository\StoryObject\CharacterRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecruitmentProposalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('character', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'title',
                'label' => 'form.proposal.character',
                'query_builder' => fn (CharacterRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp),
                'autocomplete' => true,
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'form.proposal.comment',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecruitmentProposal::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
