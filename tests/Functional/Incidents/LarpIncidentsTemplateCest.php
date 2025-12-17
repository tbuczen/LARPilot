<?php

declare(strict_types=1);

namespace Tests\Functional\Incidents;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Location;
use App\Domain\Incidents\Entity\Enum\LarpIncidentStatus;
use App\Domain\Incidents\Entity\LarpIncident;
use App\Domain\Incidents\Form\Filter\LarpIncidentFilterType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Tests\Support\FunctionalTester;
use Twig\Environment;

class LarpIncidentsTemplateCest
{
    private Environment $twig;
    private FormFactoryInterface $formFactory;

    public function _before(FunctionalTester $I): void
    {
        $this->twig = $I->grabService('twig');
        $this->formFactory = $I->grabService('form.factory');

        // Ensure request stack has a request
        $requestStack = $I->grabService('request_stack');
        if (!$requestStack->getCurrentRequest()) {
            $requestStack->push(new Request());
        }
    }

    public function templateRendersIncidentList(FunctionalTester $I): void
    {
        $I->wantTo('verify that incident list template renders correctly');

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

        $html = $this->twig->render('backoffice/larp/incident/list.html.twig', [
            'larp' => $larp,
            'incidents' => [$incident],
        ]);

        $I->assertStringContainsString('123', $html);
    }

    public function filteringWorksByStatus(FunctionalTester $I): void
    {
        $I->wantTo('verify that filtering incidents by status works correctly');

        $form = $this->formFactory->create(LarpIncidentFilterType::class);
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

        $I->assertCount(1, $filtered);
        $I->assertSame('B', $filtered[0]->getCaseId());
    }
}
