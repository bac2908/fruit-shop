<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class SafeHtmlService
{
    private array $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4',
        'ul', 'ol', 'li', 'blockquote', 'a', 'table', 'thead', 'tbody',
        'tr', 'th', 'td',
    ];

    private array $blockedTags = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'button', 'textarea', 'select', 'option', 'svg', 'math',
    ];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="safe-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $document->getElementById('safe-root');
        if (! $root) {
            return '';
        }

        $this->sanitizeChildren($root);

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, $this->blockedTags, true)) {
                    $parent->removeChild($node);
                    continue;
                }

                if (! in_array($tag, $this->allowedTags, true)) {
                    $this->sanitizeChildren($node);
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    $allowed = $tag === 'a' && in_array($name, ['href', 'title', 'target', 'rel'], true);
                    if (! $allowed) {
                        $node->removeAttribute($attribute->name);
                    }
                }

                if ($tag === 'a') {
                    $href = trim((string) $node->getAttribute('href'));
                    if ($href !== '' && ! preg_match('/^(https?:\/\/|mailto:|tel:|\/|#)/i', $href)) {
                        $node->removeAttribute('href');
                    }
                    if ($node->getAttribute('target') === '_blank') {
                        $node->setAttribute('rel', 'noopener noreferrer');
                    }
                }
            }

            if ($node->parentNode) {
                $this->sanitizeChildren($node);
            }
        }
    }
}
