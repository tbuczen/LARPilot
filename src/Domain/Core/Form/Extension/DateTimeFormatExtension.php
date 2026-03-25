<?php

declare(strict_types=1);

namespace App\Domain\Core\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeFormatExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [DateTimeType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'html5' => false,
            'format' => 'dd-MM-yyyy HH:mm',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (($options['widget'] ?? null) !== 'single_text') {
            return;
        }

        $view->vars['attr']['data-controller'] = 'datepicker';
        $view->vars['attr']['data-datepicker-enable-time-value'] = 'true';
    }
}
