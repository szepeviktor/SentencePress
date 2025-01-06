<?php

/**
 * Create a select element.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress\Html\Element;

use SzepeViktor\SentencePress\Html\Element;

use function esc_html;

/**
 * Create a select element with attributes froma list of option elements.
 */
class SelectElement extends Element
{
    /**
     * @param array<string, string> $attributes HTML attributes of the select element.
     * @param array<string, string> $options Option elements value=>raw_item.
     */
    public function __construct(array $attributes = [], array $options = [], string $currentValue = '') {
        parent::__construct(
            'select',
            $attributes,
            \implode(
                \array_map(
                    static function (string $optionValue, string $optionItem) use ($currentValue): string {
                        return (new Element(
                            'option',
                            \array_merge(
                                ['value' => $optionValue],
                                $optionValue === $currentValue ? ['selected' => null] : []
                            ),
                            esc_html($optionItem)
                        ))->render();
                    },
                    \array_keys($options),
                    $options
                )
            )
        );
    }
}
