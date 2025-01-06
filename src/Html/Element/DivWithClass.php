<?php

/**
 * Create a div element with classes.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress\Html\Element;

use SzepeViktor\SentencePress\Html\Element;

class DivWithClass extends Element
{
    public function __construct(string $classString, string $content = '') {
        parent::__construct('div', ['class' => $classString], $content);
    }
}
