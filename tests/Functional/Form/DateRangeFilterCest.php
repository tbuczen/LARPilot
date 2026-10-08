<?php

declare(strict_types=1);

namespace Tests\Functional\Form;

use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\EventPlanning\Form\Filter\ScheduledEventFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

class DateRangeFilterCest
{
    public function publicLarpListShowsOnlyLarpsWithinChosenDates(FunctionalTester $I): void
    {
        $I->wantTo('verify the public LARP date range filter keeps LARPs that start and end inside the period');

        $organizer = UserFactory::createApprovedUser();
        $inside = LarpFactory::createPublishedLarp($organizer, 'Salt Road Inside');
        $inside->_real()->setStartDate(new \DateTime('2030-06-12 18:00'));
        $inside->_real()->setEndDate(new \DateTime('2030-06-14 16:00'));
        $endsOnLastDay = LarpFactory::createPublishedLarp($organizer, 'Salt Road Last Day');
        $endsOnLastDay->_real()->setStartDate(new \DateTime('2030-06-28 10:00'));
        $endsOnLastDay->_real()->setEndDate(new \DateTime('2030-06-30 22:00'));
        $overlapsEnd = LarpFactory::createPublishedLarp($organizer, 'Salt Road Overlap');
        $overlapsEnd->_real()->setStartDate(new \DateTime('2030-06-29 10:00'));
        $overlapsEnd->_real()->setEndDate(new \DateTime('2030-07-02 16:00'));
        $before = LarpFactory::createPublishedLarp($organizer, 'Salt Road Before');
        $before->_real()->setStartDate(new \DateTime('2030-05-20 10:00'));
        $before->_real()->setEndDate(new \DateTime('2030-05-22 16:00'));
        $I->getEntityManager()->flush();

        $I->amOnPage('/?' . http_build_query([
            'larp_public_filter' => ['startDate' => '2030-06-01', 'endDate' => '2030-06-30'],
        ]));

        $I->seeResponseCodeIsSuccessful();
        $I->see('Salt Road Inside');
        $I->see('Salt Road Last Day');
        $I->dontSee('Salt Road Overlap');
        $I->dontSee('Salt Road Before');
        $I->see('12-06-2030');
    }

    public function scheduledEventFilterQueriesStartAndEndTimes(FunctionalTester $I): void
    {
        $I->wantTo('verify the scheduled event date range filter builds a valid query on startTime/endTime');

        $larp = LarpFactory::new()->create()->_real();
        $form = $I->grabService('form.factory')->create(ScheduledEventFilterType::class, null, ['larp' => $larp]);
        $form->submit(['startDate' => '2030-06-01', 'endDate' => '2030-06-30']);

        $queryBuilder = $I->getEntityManager()->getRepository(ScheduledEvent::class)->createQueryBuilder('e')
            ->where('e.larp = :larp')
            ->setParameter('larp', $larp);
        $I->grabService(FilterBuilderUpdaterInterface::class)->addFilterConditions($form, $queryBuilder);

        $dql = $queryBuilder->getDQL();
        $I->assertStringContainsString('e.startTime >= :date_range_e_startDate', $dql);
        $I->assertStringContainsString('COALESCE(e.endTime, e.startTime) < :date_range_e_endDate', $dql);
        $I->assertSame([], $queryBuilder->getQuery()->getResult());
    }

    public function publicLarpListRendersCombinedDatePicker(FunctionalTester $I): void
    {
        $I->wantTo('verify the public LARP filter renders one picker for the date range');

        $I->amOnPage('/');

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('input#larp_public_filter_startDate[data-controller="date-picker"][data-date-picker-end-value="larp_public_filter_endDate"]');
        $I->seeElement('input#larp_public_filter_endDate[data-date-picker-range-end="true"]');
        $I->dontSeeElement('input#larp_public_filter_endDate[data-controller]');
    }
}
