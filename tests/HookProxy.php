<?php

declare(strict_types=1);

namespace SzepeViktor\SentencePress\Tests;

use SzepeViktor\SentencePress\HookProxy as Hook;

class HookProxy
{
    use Hook;

    public function __construct()
    {
        $this->lazyHookMethod('init', [$this, 'init'], 10, 0);
    }

    public function init(): bool
    {
        return true;
    }
}
