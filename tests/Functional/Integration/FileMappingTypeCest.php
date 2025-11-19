<?php

declare(strict_types=1);

namespace Tests\Functional\Integration;

use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Form\Integrations\FileMappingType;
use App\Domain\Integrations\Form\Models\ExternalResourceMappingModel;
use Symfony\Component\Form\FormFactoryInterface;
use Tests\Support\FunctionalTester;

class FileMappingTypeCest
{
    private FormFactoryInterface $formFactory;

    public function _before(FunctionalTester $I): void
    {
        $this->formFactory = $I->grabService('form.factory');
    }

    public function characterDocSubFormAppears(FunctionalTester $I): void
    {
        $I->wantTo('verify that character doc sub-form appears with correct fields');

        $model = new ExternalResourceMappingModel(ResourceType::CHARACTER_DOC);
        $form = $this->formFactory->create(FileMappingType::class, $model, [
            'mimeType' => 'application/vnd.google-apps.document',
        ]);

        $I->assertTrue($form->get('mappings')->has('title'));
        $I->assertTrue($form->get('mappings')->has('description'));
    }

    public function eventDocSubFormAppears(FunctionalTester $I): void
    {
        $I->wantTo('verify that event doc sub-form appears with correct fields');

        $model = new ExternalResourceMappingModel(ResourceType::EVENT_DOC);
        $form = $this->formFactory->create(FileMappingType::class, $model, [
            'mimeType' => 'application/vnd.google-apps.document',
        ]);

        $I->assertTrue($form->get('mappings')->has('eventName'));
        $I->assertTrue($form->get('mappings')->has('description'));
    }

    public function allowedTypesForDocumentAreCorrect(FunctionalTester $I): void
    {
        $I->wantTo('verify that allowed resource types are correct for documents');

        $type = new FileMappingType();
        $allowed = $type->getAllowedResourceTypes('application/vnd.google-apps.document');

        $I->assertContains(ResourceType::CHARACTER_DOC, $allowed);
        $I->assertContains(ResourceType::EVENT_DOC, $allowed);
    }

    public function subFormMappingTypesAreCorrect(FunctionalTester $I): void
    {
        $I->wantTo('verify that sub-form mapping types are correctly configured');

        $I->assertSame(
            \App\Domain\Integrations\Form\Integrations\CharacterDocMappingType::class,
            ResourceType::CHARACTER_DOC->getSubForm()
        );
        $I->assertSame(
            \App\Domain\Integrations\Form\Integrations\EventDocMappingType::class,
            ResourceType::EVENT_DOC->getSubForm()
        );
    }
}
