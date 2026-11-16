<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allowlist sanitizer for rich-text content (TipTap StarterKit + TextAlign + Link output) that is
 * later rendered with v-html on public pages.
 *
 * The output is rebuilt from scratch rather than stripped in place: only allowlisted tags and
 * attributes are ever emitted and every text node is re-escaped, so nothing the parser tolerates
 * but browsers interpret differently can slip through.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'code', 'pre', 'blockquote',
        'ul', 'ol', 'li', 'a',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    private const VOID_TAGS = ['br', 'hr'];

    /** Elements dropped together with everything inside them. Unknown elements are unwrapped instead. */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript',
        'svg', 'math', 'form', 'input', 'button', 'select', 'textarea', 'head', 'title', 'meta', 'link',
    ];

    private const ALLOWED_URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        // Render every top-level node: a stray "</div>" in the input closes the wrapper early,
        // and the remaining content must still go through the allowlist rather than vanish.
        return self::renderChildren($document);
    }

    private static function renderChildren(DOMNode $node): string
    {
        $output = '';

        foreach ($node->childNodes as $child) {
            $output .= self::renderNode($child);
        }

        return $output;
    }

    private static function renderNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return self::escape($node->textContent);
        }

        if (! $node instanceof DOMElement) {
            return ''; // comments, processing instructions, CDATA
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
            return '';
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            return self::renderChildren($node);
        }

        $attributes = self::renderAttributes($tag, $node);

        if (in_array($tag, self::VOID_TAGS, true)) {
            return "<{$tag}{$attributes}>";
        }

        return "<{$tag}{$attributes}>".self::renderChildren($node)."</{$tag}>";
    }

    private static function renderAttributes(string $tag, DOMElement $node): string
    {
        $attributes = [];

        $align = self::textAlign($node->getAttribute('style'));
        if ($align !== null) {
            $attributes['style'] = "text-align: {$align}";
        }

        if ($tag === 'a') {
            $href = self::safeUrl($node->getAttribute('href'));
            if ($href !== null) {
                $attributes['href'] = $href;
            }

            if ($node->getAttribute('target') === '_blank') {
                $attributes['target'] = '_blank';
            }

            $attributes['rel'] = 'noopener noreferrer nofollow';
        }

        $rendered = '';
        foreach ($attributes as $name => $value) {
            $rendered .= ' '.$name.'="'.self::escape($value).'"';
        }

        return $rendered;
    }

    private static function textAlign(string $style): ?string
    {
        if (preg_match('/^\s*text-align\s*:\s*(left|center|right|justify)\s*;?\s*$/i', $style, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private static function safeUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        // Browsers ignore control characters and whitespace when reading a scheme ("java\tscript:").
        $compact = preg_replace('/[\x00-\x20\x7F]+/', '', $url);

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $matches)) {
            return in_array(strtolower($matches[1]), self::ALLOWED_URL_SCHEMES, true) ? $url : null;
        }

        // No scheme: relative path, fragment or query. A colon before the first "/" would make it a scheme.
        return str_contains(strtok($compact, '/?#') ?: '', ':') ? null : $url;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
