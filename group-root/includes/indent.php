<?php
declare(strict_types=1);

/**
 * Output-buffer callback that re-indents generated HTML (two spaces per level) so the
 * page source stays tidy no matter how the PHP templates are nested.
 * <textarea>, <pre> and <script> bodies are left untouched.
 */
function reindent_html(string $html): string
{
    $void = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];
    $rawTags = ['textarea', 'pre', 'script', 'style'];
    $out = [];
    $depth = 0;
    $raw = null;

    $html = preg_replace_callback(
        '/<[a-zA-Z][^<>]*>/',
        fn ($m) => preg_replace('/\s*\R\s*/', ' ', $m[0]),
        $html
    );
    $html = preg_replace('/>[ \t]{2,}(?=<)/', ">\n", $html);

    foreach (preg_split('/\R/', $html) as $line) {
        if ($raw !== null) {
            $out[] = $line;
            if (stripos($line, '</' . $raw . '>') !== false) {
                $raw = null;
                $depth = max(0, $depth - 1);
            }
            continue;
        }

        $text = trim($line);
        if ($text === '') {
            continue;
        }

        preg_match_all('/<(\/?)([a-zA-Z][a-zA-Z0-9-]*)\b[^>]*?(\/?)>/', $text, $tags, PREG_SET_ORDER);
        $before = $depth;
        $min = $depth;
        $opensRaw = null;

        foreach ($tags as $tag) {
            $closing = $tag[1] === '/';
            $name = strtolower($tag[2]);
            if (in_array($name, $void, true) || $tag[3] === '/') {
                continue;
            }
            if ($closing) {
                $depth = max(0, $depth - 1);
                $min = min($min, $depth);
                if ($opensRaw === $name) {
                    $opensRaw = null;
                }
            } else {
                $depth++;
                if (in_array($name, $rawTags, true)) {
                    $opensRaw = $name;
                }
            }
        }

        $level = str_starts_with($text, '</') ? $min : $before;
        $out[] = str_repeat('  ', $level) . $text;

        if ($opensRaw !== null) {
            $raw = $opensRaw;
        }
    }

    return implode("\n", $out) . "\n";
}
