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

use DOMDocument;
use DOMElement;

use function esc_url;
use function get_template_directory_uri;
use function wp_json_encode;

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
 * @param mixed $thing Array to be tested.
 */
function isNonEmptyArray($thing): bool
{
    return \is_array($thing) && $thing !== [];
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
function ifPrint($condition, string $content): void
{
    if (! $condition) {
        return;
    }

    // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
    print $content;
}

function printAssetUri(string $path = ''): void
{
    // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
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
 * @param string|false $path
 * @param array<string, string> $attrs
 *
 * phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps,Squiz.NamingConventions.ValidFunctionName.NotCamelCaps
 */
function get_inline_svg($path, array $attrs = [], int $ttl = 3600): string
{
    $empty_svg = '<svg class="svg-empty"></svg>';

    // phpcs:ignore PSR12NeutronRuleset.Strings.ConcatenationUsage.NotAllowed
    $cache_key = 'file-' . md5($path . wp_json_encode($attrs));
    $xml = wp_cache_get($cache_key, 'svg-contents');
    if ($xml !== false) {
        assert(is_string($xml));

        return $xml;
    }

    if ($path === false || ! file_exists($path)) {
        return $empty_svg;
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    $contents = file_get_contents($path);
    if ($contents === false || $contents === '') {
        return $empty_svg;
    }

    $document = new DOMDocument();
    $document->loadXML($contents);

    $svg_elems = $document->getElementsByTagName('svg');
    if ($svg_elems->length === 0) {
        return $empty_svg;
    }

    $svg_elem = $svg_elems->item(0);
    assert($svg_elem instanceof DOMElement, 'length === 0 makes sure we have element 0');

    // May cause duplicate ID error
    $svg_elem->removeAttribute('id');

    foreach ($attrs as $attr_name => $attr_value) {
        $svg_elem->setAttribute($attr_name, $attr_value);
    }

    // phpcs:disable Squiz.PHP.CommentedOutCode.Found
    // SVG version 1.1
    //$document->xmlVersion = '1.1';
    //$svg_elem->setAttribute('version', '1.1');

    // Handle the SVG as an image
    $svg_elem->setAttribute('role', 'img');

    $xml = $document->saveXML($svg_elem);
    assert(is_string($xml), 'saveXML returns false on failure');

    wp_cache_set($cache_key, $xml, 'svg-contents', $ttl);

    return $xml;
}

/**
 * Prepare an SVG from assets directory for inline display.
 *
 * @param array<string, string> $attrs
 */
function get_inline_svg_asset(string $filename, array $attrs = [], int $ttl = 3600): string
{
    return get_inline_svg(sprintf('%s/assets/img/%s', get_template_directory(), $filename), $attrs, $ttl);
}

/**
 * Prepare an SVG from Media for inline display.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/SVG/Tutorial/SVG_In_HTML_Introduction
 *
 * @param array<string, string> $attrs
 */
function get_inline_svg_media(int $attachment_id, array $attrs = [], int $ttl = 3600): string
{
    return get_inline_svg(get_attached_file($attachment_id), $attrs, $ttl);
}

/**
 * Prepare an SVG as an icon.
 */
function get_inline_svg_icon(string $filename, string $class_string = 'icon'): string
{
    return get_inline_svg_asset(
        sprintf('icon/%s', $filename),
        [
            'class' => $class_string,
            'width' => '24',
            'height' => '24',
        ]
    );
}
