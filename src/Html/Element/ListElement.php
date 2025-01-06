<?php

/**
 * Create a list element.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress\Html\Element;

use SzepeViktor\SentencePress\Html\Element;

/**
 * Create a list element with attributes from a list of children.
 */
class ListElement extends Element
{
    /**
     * @param array<string, string> $attributes HTML attributes of the parent.
     * @param list<string> $childrenContent Raw HTML content of children.
     * @param string $childTagName Name of children tags.
     */
    public function __construct(
        string $tagName = 'ul',
        array $attributes = [],
        array $childrenContent = [],
        string $childTagName = 'li'
    ) {
        parent::__construct(
            $tagName,
            $attributes,
            \implode(
                array_map(
                    static function (string $childContent) use ($childTagName): string {
                        return \implode(
                            [
                                self::LESS_THAN_SIGN,
                                $childTagName,
                                self::GREATER_THAN_SIGN,
                                $childContent,
                                self::LESS_THAN_SIGN,
                                self::SOLIDUS,
                                $childTagName,
                                self::GREATER_THAN_SIGN,
                            ]
                        );
                    },
                    $childrenContent
                )
            )
        );
    }
}
