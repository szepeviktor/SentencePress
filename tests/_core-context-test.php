<?php

/**
 * Return detected WordPress contexts.
 *
 * @return string
 */
function context_test() {
    return implode(
        ',',
        \SzepeViktor\SentencePress\Context::detect()->all()
    );
}
