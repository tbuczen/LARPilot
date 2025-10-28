<?php

namespace App\Tests\Domain\StoryMarketplace\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecruitmentControllerTest extends WebTestCase
{
    public function testRecruitmentRouteRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/backoffice/larp/00000000-0000-0000-0000-000000000000/story/thread/123/recruitment');
        $this->assertTrue($client->getResponse()->isRedirection() || $client->getResponse()->isClientError());
    }
}
