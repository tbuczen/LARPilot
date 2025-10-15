<?php

namespace App\Twig\Components;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\ParticipantCodeValidator;
use App\Domain\Incidents\Entity\LarpIncident;
use App\Domain\Incidents\Form\LarpIncidentType;
use App\Domain\Incidents\Repository\LarpIncidentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('incident_form')]
class IncidentFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public Larp $larp;

    public function __construct(
        private readonly LarpIncidentRepository $repository,
        private readonly ParticipantCodeValidator $validator,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        $incident = new LarpIncident();
        $incident->setLarp($this->larp);
        $incident->setCreatedAt(new \DateTimeImmutable());

        return $this->createForm(LarpIncidentType::class, $incident);
    }

    #[LiveAction]
    public function submit(): void
    {
        $this->submitForm();
        /** @var LarpIncident $incident */
        $incident = $this->getForm()->getData();
        if (!$this->validator->validate($incident->getReportCode(), $incident->getLarp())) {
            $this->getForm()->get('reportCode')->addError(new FormError('Invalid code'));
            return;
        }
        $incident->setCaseId(Uuid::v4()->toRfc4122());
        $this->repository->save($incident);
    }
}
