<?php

namespace App\DataFixtures\Dev;

use App\Domain\Account\Entity\Plan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PlanFixtures extends Fixture
{
    public const FREE_PLAN = 'plan_free';
    public const BASIC_PLAN = 'plan_basic';
    public const PRO_PLAN = 'plan_pro';
    public const ENTERPRISE_PLAN = 'plan_enterprise';

    public function load(ObjectManager $manager): void
    {
        // Free Plan - 1 LARP, basic features
        $freePlan = new Plan();
        $freePlan->setName('Free')
            ->setDescription('Perfect for trying out LARPilot with one event')
            ->setMaxLarps(1)
            ->setMaxParticipantsPerLarp(100)
            ->setStorageLimitMb(100)
            ->setHasGoogleIntegrations(false)
            ->setHasCustomBranding(false)
            ->setPriceInCents(0)
            ->setIsFree(true)
            ->setIsActive(true)
            ->setSortOrder(1);
        $manager->persist($freePlan);
        $this->addReference(self::FREE_PLAN, $freePlan);

        // Basic Plan - Pay per LARP (placeholder pricing)
        $basicPlan = new Plan();
        $basicPlan->setName('Basic')
            ->setDescription('For organizers running occasional events')
            ->setMaxLarps(null) // Pay per LARP, no hard limit
            ->setMaxParticipantsPerLarp(200)
            ->setStorageLimitMb(500)
            ->setHasGoogleIntegrations(true)
            ->setHasCustomBranding(false)
            ->setPriceInCents(2999) // $29.99 per LARP (placeholder)
            ->setIsFree(false)
            ->setIsActive(true)
            ->setSortOrder(2);
        $manager->persist($basicPlan);
        $this->addReference(self::BASIC_PLAN, $basicPlan);

        // Pro Plan - Multiple LARPs with advanced features
        $proPlan = new Plan();
        $proPlan->setName('Pro')
            ->setDescription('For professional LARP organizers')
            ->setMaxLarps(5) // 5 LARPs per month/year
            ->setMaxParticipantsPerLarp(500)
            ->setStorageLimitMb(2048) // 2GB
            ->setHasGoogleIntegrations(true)
            ->setHasCustomBranding(true)
            ->setPriceInCents(9999) // $99.99 per month (placeholder)
            ->setIsFree(false)
            ->setIsActive(true)
            ->setSortOrder(3);
        $manager->persist($proPlan);
        $this->addReference(self::PRO_PLAN, $proPlan);

        // Enterprise Plan - Unlimited everything
        $enterprisePlan = new Plan();
        $enterprisePlan->setName('Enterprise')
            ->setDescription('For large organizations running many events')
            ->setMaxLarps(null) // Unlimited
            ->setMaxParticipantsPerLarp(null) // Unlimited
            ->setStorageLimitMb(null) // Unlimited
            ->setHasGoogleIntegrations(true)
            ->setHasCustomBranding(true)
            ->setPriceInCents(29999) // $299.99 per month (placeholder)
            ->setIsFree(false)
            ->setIsActive(true)
            ->setSortOrder(4);
        $manager->persist($enterprisePlan);
        $this->addReference(self::ENTERPRISE_PLAN, $enterprisePlan);

        $manager->flush();
    }
}
