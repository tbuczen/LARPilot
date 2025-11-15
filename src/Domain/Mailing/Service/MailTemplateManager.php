<?php

namespace App\Domain\Mailing\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Mailing\Dto\MailTemplateDefinition;
use App\Domain\Mailing\Entity\MailTemplate;
use App\Domain\Mailing\Repository\MailTemplateRepository;

class MailTemplateManager
{
    public function __construct(
        private readonly MailTemplateRepository $repository,
        private readonly MailTemplateDefinitionProvider $definitionProvider,
    ) {
    }

    public function ensureTemplatesForLarp(Larp $larp): void
    {
        $existing = $this->repository->findBy(['larp' => $larp]);
        $existingByType = [];
        foreach ($existing as $template) {
            $existingByType[$template->getType()->value] = $template;
        }

        $definitions = $this->definitionProvider->getDefinitions();
        $needsFlush = false;

        foreach ($definitions as $type => $definition) {
            if (!isset($existingByType[$type])) {
                $template = $this->createTemplateFromDefinition($larp, $definition);
                $this->repository->save($template, false);
                $needsFlush = true;

                continue;
            }

            $template = $existingByType[$type];
            if ($template->getAvailablePlaceholders() !== $definition->placeholders) {
                $template->setAvailablePlaceholders($definition->placeholders);
                $this->repository->save($template, false);
                $needsFlush = true;
            }
        }

        if ($needsFlush) {
            $this->repository->flush();
        }
    }

    /**
     * @return array<string, MailTemplateDefinition>
     */
    public function getTemplateDefinitions(): array
    {
        return $this->definitionProvider->getDefinitions();
    }

    public function getDefinitionForType(MailTemplate $template): ?MailTemplateDefinition
    {
        return $this->definitionProvider->getDefinition($template->getType());
    }

    private function createTemplateFromDefinition(Larp $larp, MailTemplateDefinition $definition): MailTemplate
    {
        $template = new MailTemplate();
        $template->setLarp($larp);
        $template->setType($definition->type);
        $template->setName($definition->name);
        $template->setSubject($definition->defaultSubject);
        $template->setBody($definition->defaultBody);
        $template->setEnabled(true);
        $template->setAvailablePlaceholders($definition->placeholders);

        return $template;
    }
}
