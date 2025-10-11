<?php

namespace App\Tests\Controller;

use App\Entity\Enum\LarpIncidentStatus;
use App\Entity\Larp;
use App\Entity\LarpIncident;
use App\Entity\Location;
use App\Form\Filter\LarpIncidentFilterType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LarpIncidentsTemplateTest extends KernelTestCase
{
    public function testTemplateRenders(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $twig = $container->get('twig');
        $formFactory = $container->get('form.factory');
        $container->get('request_stack')->push(new \Symfony\Component\HttpFoundation\Request());

        $larp = new Larp();
        $larp->setTitle('Test');
        $larp->setSlug('test');
        $larp->setDescription('d');
        $larp->setStartDate(new \DateTime());
        $larp->setEndDate(new \DateTime());
        $larp->setLocation(new Location());

        $incident = new LarpIncident();
        $incident->setLarp($larp);
        $incident->setCaseId('123');
        $incident->setStatus(LarpIncidentStatus::NEW);

        $html = $twig->render('backoffice/larp/list.html.twig', [
            'larp' => $larp,
            'incidents' => [$incident],
        ]);

        $this->assertStringContainsString('123', $html);
    }

    public function testFilteringWorks(): void
    {
        self::bootKernel();
        $formFactory = self::getContainer()->get('form.factory');

        $form = $formFactory->create(LarpIncidentFilterType::class);
        $form->submit([
            'status' => [LarpIncidentStatus::CLOSED->value],
        ]);

        $larp = new Larp();
        $larp->setTitle('Test');

        $incidentA = new LarpIncident();
        $incidentA->setLarp($larp);
        $incidentA->setCaseId('A');
        $incidentA->setStatus(LarpIncidentStatus::NEW);

        $incidentB = new LarpIncident();
        $incidentB->setLarp($larp);
        $incidentB->setCaseId('B');
        $incidentB->setStatus(LarpIncidentStatus::CLOSED);

        $data = $form->getData();
        $incidents = [$incidentA, $incidentB];
        $filtered = array_values(array_filter($incidents, function (LarpIncident $i) use ($data): bool {
            if (!empty($data['status']) && !in_array($i->getStatus(), $data['status'], true)) {
                return false;
            }

            if (!empty($data['caseId']) && $i->getCaseId() !== $data['caseId']) {
                return false;
            }

            return true;
        }));

        $this->assertCount(1, $filtered);
        $this->assertSame('B', $filtered[0]->getCaseId());
    }
}
