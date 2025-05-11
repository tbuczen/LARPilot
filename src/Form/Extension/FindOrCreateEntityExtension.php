<?php

namespace App\Form\Extension;


use App\Entity\Enum\TargetType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FindOrCreateEntityExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    )
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $tomSelectOptions = $options['tom_select_options'] ?? [];
        if (($tomSelectOptions['create'] ?? false) && ($tomSelectOptions['persist'] ?? false) === false) {
            $larp = $form->getRoot()->getConfig()->getOptions()['larp'] ?? null;
            $class = $options['class'] ?? null;

            if ($larp && method_exists($class, 'getTargetType')) {
                /** @var TargetType $targetType */
                $targetType = $class::getTargetType();

                $existing = $view->vars['attr']['data-controller'] ?? '';

                $controllers = explode(' ', $existing);
                $controllers = array_diff($controllers, ['custom-autocomplete']);
                // wstaw custom-autocomplete na POCZĄTEK - kolejność ma znaczenie dla JS
                array_unshift($controllers, 'custom-autocomplete');

                $view->vars['attr']['data-controller'] = implode(' ', $controllers);

                $view->vars['attr']['data-custom-autocomplete-url-value'] = $this->urlGenerator->generate('backoffice_custom_autocomplete_create', [
                    'larp' => $larp->getId(),
                ]);
                $view->vars['attr']['data-custom-autocomplete-type-value'] = $targetType->value;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'tom_select_options' => [],
        ]);
    }
}