<?php
namespace App\Twig\Components;

use App\Form\Integrations\FileMappingType;
use App\Form\Models\ExternalResourceMappingModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ExternalResourceMappingForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?ExternalResourceMappingModel $formData = null;

    protected function instantiateForm(): FormInterface
    {
        $mappingType = $this->formData ?? new ExternalResourceMappingModel();
        return $this->createForm(FileMappingType::class, $mappingType);
    }
}