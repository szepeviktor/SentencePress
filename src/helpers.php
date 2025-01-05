<?php

/**
 * Useful functions.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use function esc_url;
use function get_template_directory_uri;

/**
 * Return whether an array or a string is empty.
 *
 * Throw exception on int|float|bool|null|object|callable|resource
 *
 * @param mixed $thing
 */
function isEmpty($thing): bool
{
    if (\is_array($thing)) {
        return $thing === [];
    }

    if (\is_string($thing)) {
        return $thing === '';
    }

    throw new \InvalidArgumentException('Not a string nor an array.');
}

/**
 * Check whether a value is a non-empty array.
 *
 * @param mixed $array Array to be tested.
 */
function isNonEmptyArray($array): bool
{
    return is_array($array) && $array !== [];
}

/**
 * @see https://html.spec.whatwg.org/multipage/introduction.html#syntax-errors
 */
function htmlComment(string $comment): string
{
    // Replace two dashes with an &mdash to be on the safe side.
    return \sprintf('<!-- %s -->', \str_replace('--', '—', $comment));
}

/**
 * @param mixed $condition
 */
function ifPrint($condition, string $string): void
{
    if (! $condition) {
        return;
    }

    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    print $string;
}

function printAssetUri(string $path = ''): void
{
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
    print esc_url(
        \sprintf(
            '%s/assets%s',
            \dirname(get_template_directory_uri()),
            $path
        )
    );
}

/**
 * Prepare an SVG for inline display.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/SVG/Tutorial/SVG_In_HTML_Introduction
 *
 * @param array<string, string> $attrs
 */
function getInlineSvg(string $filename, array $attrs = [], int $ttl = 3600): string
{
    $base_dir = get_template_directory().'/assets/img/';
    $empty_svg = '<svg class="svg-empty"></svg>';

    $cache_key = 'file-'.md5($filename.serialize($attrs));
    $xml = wp_cache_get($cache_key, 'svg-contents');
    if ($xml !== false) {
        return $xml;
    }

    $contents = file_get_contents($base_dir.$filename);
    if ($contents === false || $contents === '') {
        return $empty_svg;
    }

    $document = new DOMDocument();
    $document->loadXML($contents);

    $svg_elems = $document->getElementsByTagName('svg');
    if ($svg_elems->length === 0) {
        return $empty_svg;
    }

    // May cause duplicate ID error
    //$svg_elems->item(0)->removeAttribute('id');

    foreach ($attrs as $attr_name => $attr_value) {
        $svg_elems->item(0)->setAttribute($attr_name, $attr_value);
    }

    // SVG version 1.1
    //$document->xmlVersion = '1.1';
    //$svg_elems->item(0)->setAttribute('version', '1.1');

    // Handle the SVG as an image
    $svg_elems->item(0)->setAttribute('role', 'img');

    $xml = $document->saveXML($svg_elems->item(0));

    wp_cache_set($cache_key, $xml, 'svg-contents', $ttl);

    return $xml;
}
