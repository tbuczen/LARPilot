<?php
namespace App\Twig\Components;

use App\Form\Integrations\SpreadsheetMappingType;
use App\Form\Models\SpreadsheetMappingModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class SpreadsheetMappingForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?SpreadsheetMappingModel $formData = null;

    protected function instantiateForm(): FormInterface
    {
        $mappingType = $this->formData ?? new SpreadsheetMappingModel();
        return $this->createForm(SpreadsheetMappingType::class, $mappingType);
    }
}