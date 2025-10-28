<?php

namespace App\Tests\Domain\Integration\Form;

use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Form\Integrations\FileMappingType;
use App\Domain\Integrations\Form\Models\ExternalResourceMappingModel;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class FileMappingTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [new PreloadedExtension([], [])];
    }

    public function testCharacterDocSubFormAppears(): void
    {
        $model = new ExternalResourceMappingModel(ResourceType::CHARACTER_DOC);
        $form = $this->factory->create(FileMappingType::class, $model, [
            'mimeType' => 'application/vnd.google-apps.document',
        ]);

        $this->assertTrue($form->get('mappings')->has('title'));
        $this->assertTrue($form->get('mappings')->has('description'));
    }

    public function testEventDocSubFormAppears(): void
    {
        $model = new ExternalResourceMappingModel(ResourceType::EVENT_DOC);
        $form = $this->factory->create(FileMappingType::class, $model, [
            'mimeType' => 'application/vnd.google-apps.document',
        ]);

        $this->assertTrue($form->get('mappings')->has('eventName'));
        $this->assertTrue($form->get('mappings')->has('description'));
    }

    public function testAllowedTypesForDocument(): void
    {
        $type = new FileMappingType();
        $allowed = $type->getAllowedResourceTypes('application/vnd.google-apps.document');
        $this->assertContains(ResourceType::CHARACTER_DOC, $allowed);
        $this->assertContains(ResourceType::EVENT_DOC, $allowed);
    }

    public function testSubFormMappingTypes(): void
    {
        $this->assertSame(
            \App\Domain\Integrations\Form\Integrations\CharacterDocMappingType::class,
            ResourceType::CHARACTER_DOC->getSubForm()
        );
        $this->assertSame(
            \App\Domain\Integrations\Form\Integrations\EventDocMappingType::class,
            ResourceType::EVENT_DOC->getSubForm()
        );
    }
}
