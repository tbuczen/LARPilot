<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HtmlSanitizerExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('sanitize_html', $this->sanitizeHtml(...), ['is_safe' => ['html']]),
        ];
    }

    public function sanitizeHtml(?string $html): string
    {
        if ($html === null) {
            return '-';
        }

        $html = html_entity_decode($html, ENT_HTML5, 'UTF-8');
        // Remove potentially dangerous elements
        $dangerous = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select', 'button'];
        foreach ($dangerous as $tag) {
            $html = preg_replace("/<{$tag}\b[^>]*>.*?<\/{$tag}>/is", '', (string) $html);
            $html = preg_replace("/<{$tag}\b[^>]*\/>/is", '', (string) $html);
        }

        // Remove dangerous attributes
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', (string) $html);
        $html = preg_replace('/\s*javascript\s*:/i', '', (string) $html);

        return $html;
    }
}
