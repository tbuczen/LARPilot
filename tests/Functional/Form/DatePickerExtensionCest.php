<?php

declare(strict_types=1);

namespace Tests\Functional\Form;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Tests\Support\FunctionalTester;

class DatePickerExtensionCest
{
    public function dateTimeFieldUsesPickerAndParsesIsoValue(FunctionalTester $I): void
    {
        $I->wantTo('verify a DateTimeType field renders the picker and accepts Y-m-d H:i values');

        $form = $this->formFactory($I)->createNamedBuilder('event', FormType::class, null, ['csrf_protection' => false])
            ->add('dueDate', DateTimeType::class, ['required' => false])
            ->getForm();

        $view = $form->createView()->children['dueDate'];
        $I->assertNotSame('datetime-local', $view->vars['type'] ?? null);
        $I->assertSame('date-picker', $view->vars['attr']['data-controller']);
        $I->assertSame('true', $view->vars['attr']['data-date-picker-time-value']);
        $I->assertArrayNotHasKey('data-date-picker-required-value', $view->vars['attr']);

        $form->submit(['dueDate' => '2026-06-12 18:05']);

        $I->assertTrue($form->isValid());
        $I->assertSame('2026-06-12 18:05', $form->get('dueDate')->getData()->format('Y-m-d H:i'));
    }

    public function dateFieldUsesPickerWithoutTime(FunctionalTester $I): void
    {
        $I->wantTo('verify a DateType field renders the picker without time and accepts Y-m-d values');

        $form = $this->formFactory($I)->createNamedBuilder('filter', FormType::class, null, ['csrf_protection' => false])
            ->add('day', DateType::class)
            ->getForm();

        $view = $form->createView()->children['day'];
        $I->assertSame('date-picker', $view->vars['attr']['data-controller']);
        $I->assertArrayNotHasKey('data-date-picker-time-value', $view->vars['attr']);
        $I->assertSame('true', $view->vars['attr']['data-date-picker-required-value']);

        $form->submit(['day' => '2026-06-12']);

        $I->assertTrue($form->isValid());
        $I->assertSame('2026-06-12', $form->get('day')->getData()->format('Y-m-d'));
    }

    public function rangeStartPointsToEndAndEndHasNoOwnPicker(FunctionalTester $I): void
    {
        $I->wantTo('verify range_end merges a start/end pair into one picker');

        $form = $this->formFactory($I)->createNamedBuilder('larp', FormType::class, null, ['csrf_protection' => false])
            ->add('startDate', DateTimeType::class, ['range_end' => 'endDate'])
            ->add('endDate', DateTimeType::class)
            ->getForm();

        $view = $form->createView();
        $start = $view->children['startDate']->vars['attr'];
        $end = $view->children['endDate']->vars['attr'];

        $I->assertSame('date-picker', $start['data-controller']);
        $I->assertSame('larp_endDate', $start['data-date-picker-end-value']);
        $I->assertArrayNotHasKey('data-controller', $end);
        $I->assertSame('true', $end['data-date-picker-range-end']);

        $form->submit(['startDate' => '2026-06-12 18:00', 'endDate' => '2026-06-14 16:00']);

        $I->assertTrue($form->isValid());
        $I->assertSame('2026-06-14 16:00', $form->get('endDate')->getData()->format('Y-m-d H:i'));
    }

    public function pickerCanBeTurnedOff(FunctionalTester $I): void
    {
        $I->wantTo('verify date_picker false keeps Symfony defaults');

        $form = $this->formFactory($I)->createNamedBuilder('plain', FormType::class, null, ['csrf_protection' => false])
            ->add('day', DateType::class, ['date_picker' => false, 'widget' => 'single_text'])
            ->getForm();

        $view = $form->createView()->children['day'];
        $I->assertSame('date', $view->vars['type']);
        $I->assertArrayNotHasKey('data-controller', $view->vars['attr']);
    }

    private function formFactory(FunctionalTester $I): FormFactoryInterface
    {
        return $I->grabService('form.factory');
    }
}
