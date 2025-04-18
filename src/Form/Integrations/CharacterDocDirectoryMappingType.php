<?php

namespace App\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class CharacterDocDirectoryMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('groupByFaction', CheckboxType::class, [
                'label' => 'form.mapping.directory.group_by_faction',
                'translation_domain' => 'forms',
            ])
            ;
    }
}
