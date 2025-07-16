<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HtmlSanitizerExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('sanitize_html', [$this, 'sanitizeHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function sanitizeHtml(string $html): string
    {
        $html = html_entity_decode($html, ENT_HTML5, 'UTF-8');
        // Remove potentially dangerous elements
        $dangerous = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select', 'button'];
        foreach ($dangerous as $tag) {
            $html = preg_replace("/<{$tag}\b[^>]*>.*?<\/{$tag}>/is", '', $html);
            $html = preg_replace("/<{$tag}\b[^>]*\/>/is", '', $html);
        }

        // Remove dangerous attributes
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $html);
        $html = preg_replace('/\s*javascript\s*:/i', '', $html);

        return $html;
    }
}
