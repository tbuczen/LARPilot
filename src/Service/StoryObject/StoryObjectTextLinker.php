<?php

namespace App\Service\StoryObject;

use App\Entity\Larp;
use App\Repository\StoryObject\StoryObjectRepository;
use Symfony\Component\Uid\Uuid;

final readonly class StoryObjectTextLinker
{
    public function __construct(
        private StoryObjectRepository $storyObjectRepository,
        private StoryObjectRouter $router,
    ) {
    }

    public function finalizeMentions(string $html, Larp $larp): string
    {
        if ($html === '') {
            return $html;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//*[@data-story-object-id]') as $node) {
            $id = $node->getAttribute('data-story-object-id');
            $object = $this->storyObjectRepository->find(Uuid::fromString($id));
            if (!$object) {
                continue;
            }
            if ($object->getLarp()?->getId()->toRfc4122() !== $larp->getId()->toRfc4122()) {
                continue;
            }
            $href = $this->router->getEditUrl($object, $larp);
            if (!$href) {
                continue;
            }
            $link = $dom->createElement('a');
            $link->setAttribute('href', $href);
            $link->nodeValue = $node->textContent;
            $node->parentNode->replaceChild($link, $node);
        }
        return $dom->saveHTML();
    }
}
