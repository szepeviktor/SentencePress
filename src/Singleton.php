<?php

/**
 * Singleton helper.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

/**
 * Singleton trait.
 */
trait Singleton
{
    /** @var self|null */
    private static $instance;

    public static function getInstance(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct() {}
}
