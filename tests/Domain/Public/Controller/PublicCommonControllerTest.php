<?php

namespace App\Tests\Domain\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicCommonControllerTest extends WebTestCase
{
    public function testSwitchLocale(): void
    {
        $client = static::createClient();
        $client->request('GET', '/switch-locale/de', [], [], ['HTTP_REFERER' => '/']);

        $this->assertResponseRedirects('/');
        $session = $client->getRequest()->getSession();
        $this->assertSame('de', $session->get('_locale'));
    }
}
