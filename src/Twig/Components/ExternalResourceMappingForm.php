<?php

namespace App\Twig\Components;

use App\Domain\Integrations\DTO\SpreadsheetPreview;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Form\Integrations\FileMappingType;
use App\Domain\Integrations\Form\Models\ExternalResourceMappingModel;
use App\Domain\Integrations\Service\SpreadsheetMappingPreviewProvider;
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

    #[LiveProp]
    public ?string $mimeType = null;

    #[LiveProp]
    public ?SharedFile $sharedFile = null;

    public function __construct(
        private readonly SpreadsheetMappingPreviewProvider $spreadsheetMappingPreviewProvider,
    ) {
    }

    public function getSpreadsheetPreview(): ?SpreadsheetPreview
    {
        if (!$this->sharedFile instanceof SharedFile) {
            return null;
        }

        return $this->spreadsheetMappingPreviewProvider->previewFor($this->sharedFile, $this->currentMetaValues());
    }

    protected function instantiateForm(): FormInterface
    {
        $formData = $this->formData ?? new ExternalResourceMappingModel();

        if (!$this->sharedFile instanceof SharedFile) {
            return $this->createForm(FileMappingType::class, $formData, [
                'mimeType' => $this->mimeType,
            ]);
        }

        return $this->createForm(
            FileMappingType::class,
            $formData,
            $this->spreadsheetMappingPreviewProvider->formOptionsFor($this->sharedFile, $this->currentMetaValues())
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function currentMetaValues(): array
    {
        $submittedMeta = $this->formValues['meta'] ?? null;
        if (is_array($submittedMeta)) {
            return $submittedMeta;
        }

        return $this->formData->meta ?? [];
    }
}
