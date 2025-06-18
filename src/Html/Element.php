<?php

/**
 * Create an HTML element.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress\Html;

use Traversable;

use function esc_attr;
use function esc_url;
use function sanitize_key;

/**
 * Create an HTML element with attributes.
 */
class Element
{
    public const SPACE = ' ';

    public const LESS_THAN_SIGN = '<';

    public const GREATER_THAN_SIGN = '>';

    public const SOLIDUS = '/';

    public const EQUALS_SIGN = '=';

    public const QUOTATION_MARK = '"';

    /** @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements */
    public const VOIDS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
        'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    protected string $tagName;

    /** @var array<string, string|null> */
    protected array $attributes;

    protected string $content;

    protected bool $isVoid;

    /**
     * Create an HTML element with pure PHP.
     *
     * @param array<string, string|null> $attributes
     * @param string|\Traversable<int, string> $content Raw HTML content.
     * @throws \Exception
     */
    public function __construct(string $tagName = 'div', array $attributes = [], $content = '')
    {
        $this->tagName = sanitize_key($tagName);
        $this->attributes = $attributes;
        $this->content = $content instanceof Traversable
            ? \implode(\iterator_to_array($content))
            : $content;
        $this->isVoid = \in_array($this->tagName, self::VOIDS, true);

        if ($this->isVoid && $this->content !== '') {
            throw new \Exception('Void HTML element with content.');
        }
    }

    /**
     * @param array{tag: string, attrs: array<string, string|null>} $skeleton
     * @throws \Exception
     */
    public static function fromSkeleton(array $skeleton, string $content = ''): self
    {
        // @phpstan-ignore isset.offset,isset.offset
        if (! isset($skeleton['tag'], $skeleton['attrs'])) {
            throw new \Exception('Skeleton array needs tag and attrs elements.');
        }

        return new self($skeleton['tag'], $skeleton['attrs'], $content);
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param array<string, string|null> $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array<string, string|null>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttribute(string $attributeName, string $attributeValue): void
    {
        $this->attributes[$attributeName] = $attributeValue;
    }

    public function setBooleanWhen(string $attribute, bool $condition): void
    {
        if (! $condition) {
            return;
        }

        $this->attributes[$attribute] = null;
    }

    public function render(): string
    {
        $attributeString = $this->getAttributeString();

        if ($attributeString !== '') {
            $attributeString = sprintf('%s%s', self::SPACE, $attributeString);
        }

        // Element.
        if ($this->isVoid) {
            return \implode([
                self::LESS_THAN_SIGN,
                $this->tagName,
                $attributeString,
                self::GREATER_THAN_SIGN,
            ]);
        }

        return \implode([
            self::LESS_THAN_SIGN,
            $this->tagName,
            $attributeString,
            self::GREATER_THAN_SIGN,
            $this->content,
            self::LESS_THAN_SIGN,
            self::SOLIDUS,
            $this->tagName,
            self::GREATER_THAN_SIGN,
        ]);
    }

    /**
     * Create an HTML attribute string from an array.
     */
    protected function getAttributeString(): string
    {
        $attributeStrings = [];
        foreach ($this->attributes as $attributeName => $attributeValue) {
            $attributeName = \preg_replace('/[^a-z0-9-]/', '', \strtolower($attributeName));

            // Boolean attributes.
            if ($attributeValue === null) {
                $attributeStrings[] = $attributeName;
                continue;
            }

            $attributeStrings[] = \implode([
                $attributeName,
                self::EQUALS_SIGN,
                self::QUOTATION_MARK,
                \in_array($attributeName, ['href', 'src'], true)
                    ? esc_url($attributeValue)
                    : esc_attr($attributeValue),
                self::QUOTATION_MARK,
            ]);
        }

        return \implode(self::SPACE, $attributeStrings);
    }
}
