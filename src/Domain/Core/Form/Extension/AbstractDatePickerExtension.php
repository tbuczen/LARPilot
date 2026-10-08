<?php

declare(strict_types=1);

namespace App\Domain\Core\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractDatePickerExtension extends AbstractTypeExtension
{
    abstract protected function format(): string;

    abstract protected function withTime(): bool;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $format = $this->format();

        $resolver->setDefaults([
            'date_picker' => true,
            'range_end' => null,
            'widget' => static fn (Options $options, mixed $previous): mixed => $options['date_picker'] ? 'single_text' : $previous,
            'html5' => static fn (Options $options, mixed $previous): mixed => $options['date_picker'] ? false : $previous,
            'format' => static fn (Options $options, mixed $previous): mixed => $options['date_picker'] ? $format : $previous,
        ]);

        $resolver->setAllowedTypes('date_picker', 'bool');
        $resolver->setAllowedTypes('range_end', ['null', 'string']);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options['date_picker']) {
            return;
        }

        $attr = $view->vars['attr'];
        $attr['autocomplete'] = 'off';

        if ($this->isRangeEnd($form)) {
            $attr['data-date-picker-range-end'] = 'true';
            $view->vars['attr'] = $attr;

            return;
        }

        $controllers = array_filter(explode(' ', (string) ($attr['data-controller'] ?? '')));
        $controllers[] = 'date-picker';
        $attr['data-controller'] = implode(' ', array_unique($controllers));

        if ($this->withTime()) {
            $attr['data-date-picker-time-value'] = 'true';
        }

        if ($options['required']) {
            $attr['data-date-picker-required-value'] = 'true';
        }

        $rangeEnd = $options['range_end'];
        if ($rangeEnd !== null && $view->parent !== null && $form->getParent()?->has($rangeEnd)) {
            $parentId = (string) ($view->parent->vars['id'] ?? '');
            $attr['data-date-picker-end-value'] = $parentId === '' ? $rangeEnd : $parentId . '_' . $rangeEnd;
        }

        $view->vars['attr'] = $attr;
    }

    private function isRangeEnd(FormInterface $form): bool
    {
        $parent = $form->getParent();
        if ($parent === null) {
            return false;
        }

        foreach ($parent->all() as $sibling) {
            if ($sibling->getConfig()->getOption('range_end') === $form->getName()) {
                return true;
            }
        }

        return false;
    }
}
