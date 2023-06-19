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

use Traversable;

use function esc_attr;
use function esc_html;
use function esc_url;
use function get_template_directory_uri;
use function sanitize_key;

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
 * Create an HTML attribute string from an array.
 *
 * @param array<string, string|null> $attrs HTML attributes.
 */
function tagAttrString(array $attrs = []): string
{
    // Attributes.
    $attrStrings = [];
    foreach ($attrs as $attrName => $attrValue) {
        $attrName = \preg_replace('/[^a-z0-9-]/', '', \strtolower($attrName));
        // Boolean attributes.
        if ($attrValue === null) {
            $attrStrings[] = \sprintf('%s', $attrName);
            continue;
        }

        $attrStrings[] = \sprintf(
            '%s="%s"',
            $attrName,
            \in_array($attrName, ['href', 'src'], true)
                ? esc_url($attrValue)
                : esc_attr($attrValue)
        );
    }

    return \implode(' ', $attrStrings);
}

/**
 * Create an HTML element with pure PHP.
 *
 * @see https://www.w3.org/TR/html/syntax.html#void-elements
 *
 * @param array<string, string|null> $attrs HTML attributes.
 * @param string|\Traversable<int, string> $content Raw HTML content.
 * @throws \Exception
 */
function tag(string $name = 'div', array $attrs = [], $content = ''): string
{
    $voids = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
        'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    $name = sanitize_key($name);
    if ($content instanceof Traversable) {
        $content = \implode('', \iterator_to_array($content));
    }

    // Void elements.
    $isVoid = \in_array($name, $voids, true);
    if ($isVoid && $content !== '') {
        throw new \Exception('Void HTML element with content.');
    }

    $attrString = tagAttrString($attrs);

    // Element.
    if ($isVoid) {
        return \sprintf('<%s%s>', $name, $attrString);
    }

    return \sprintf('<%s%s>%s</%s>', $name, $attrString, $content, $name);
}

/**
 * Create an HTML list.
 *
 * @param string $name Parent tag name.
 * @param array<string, string> $attrs HTML attributes of the parent.
 * @param array<int, string> $childrenContent Raw HTML content of children.
 * @param string $childTagName Name of children tags.
 */
function tagList(
    string $name = 'ul',
    array $attrs = [],
    array $childrenContent = [],
    string $childTagName = 'li'
): string {
    $content = \array_map(
        static function (string $child) use ($childTagName): string {
            return \sprintf('<%s>%s</%s>', $childTagName, $child, $childTagName);
        },
        $childrenContent
    );

    return tag($name, $attrs, \implode('', $content));
}

/**
 * Create a DIV element with classes.
 */
function tagDivClass(string $classes, string $htmlContent = ''): string
{
    return tag('div', ['class' => $classes], $htmlContent);
}

/**
 * Create an H3 element with classes.
 */
function tagH3Class(string $classes, string $htmlContent = ''): string
{
    return tag('h3', ['class' => $classes], $htmlContent);
}

/**
 * Create an HTML element from tag name and array of attributes.
 *
 * @param array{tag: string, attrs: array<string, string|null>} $skeleton
 */
function tagFromSkeleton(array $skeleton, string $htmlContent = ''): string
{
    return tag($skeleton['tag'], $skeleton['attrs'], $htmlContent);
}

/**
 * Create a select element.
 *
 * @param array<string, string> $attrs HTML attributes of the select.
 * @param array<string, string> $options Option elements value=>item.
 */
function tagSelect(array $attrs, array $options, string $currentValue = ''): string
{
    $optionElements = \array_map(
        static function (string $optionValue, string $optionItem) use ($currentValue): string {
            return tag(
                'option',
                \array_merge(
                    ['value' => $optionValue],
                    $optionValue === $currentValue ? ['selected' => null] : []
                ),
                esc_html($optionItem)
            );
        },
        \array_keys($options),
        $options
    );

    return tag(
        'select',
        $attrs,
        \implode('', $optionElements)
    );
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
    print esc_url(\sprintf(
        '%s/assets%s',
        \dirname(get_template_directory_uri()),
        $path
    ));
}
