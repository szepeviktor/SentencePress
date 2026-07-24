<?php

/**
 * Immutable snapshot of the detected WordPress context.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use function _doing_it_wrong;
use function array_keys;
use function array_unique;
use function array_values;
use function function_exists;
use function sprintf;

/**
 * Store the contexts observable at a specific point in the request lifecycle.
 */
final class ContextSnapshot
{
    /** @var array<string, true> */
    private static array $reportedUnresolvedFlags = [];

    /** @var array<string, \SzepeViktor\SentencePress\ContextFlag> */
    private readonly array $flags;

    /**
     * @param list<\SzepeViktor\SentencePress\ContextFlag> $flags
     */
    public function __construct(
        private readonly PrimaryContext $primary,
        array $flags,
        private readonly bool $queryResolved,
        private readonly bool $screenResolved = false
    ) {
        $indexedFlags = [];

        foreach ($flags as $flag) {
            $indexedFlags[$flag->value] = $flag;
        }

        $this->flags = $indexedFlags;
    }

    public function primary(): PrimaryContext
    {
        return $this->primary;
    }

    public function is(PrimaryContext $context): bool
    {
        return $this->primary === $context;
    }

    public function has(ContextFlag $flag): bool
    {
        if (! $this->queryResolved && $flag->requiresResolvedQuery()) {
            self::reportUnresolvedFlag(
                $flag,
                'the main query is resolved',
                'check ContextSnapshot::queryResolved() first'
            );

            return false;
        }

        if (
            ! $this->screenResolved
            && $this->primary === PrimaryContext::Admin
            && $flag->requiresResolvedScreen()
        ) {
            self::reportUnresolvedFlag(
                $flag,
                'the current admin screen is resolved',
                'check ContextSnapshot::screenResolved() first'
            );

            return false;
        }

        return isset($this->flags[$flag->value]);
    }

    /**
     * @return list<\SzepeViktor\SentencePress\ContextFlag>
     */
    public function flags(): array
    {
        return array_values($this->flags);
    }

    /**
     * Return unique string representations of the primary context and flags.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return array_values(
            array_unique([
                $this->primary->value,
                ...array_keys($this->flags),
            ])
        );
    }

    /**
     * Whether main-query conditional flags were available during detection.
     */
    public function queryResolved(): bool
    {
        return $this->queryResolved;
    }

    /**
     * Whether the current WordPress admin screen was available during detection.
     */
    public function screenResolved(): bool
    {
        return $this->screenResolved;
    }

    private static function reportUnresolvedFlag(
        ContextFlag $flag,
        string $requiredState,
        string $resolutionCheck
    ): void {
        if (
            isset(self::$reportedUnresolvedFlags[$flag->value])
            || ! function_exists('_doing_it_wrong')
        ) {
            return;
        }

        self::$reportedUnresolvedFlags[$flag->value] = true;

        _doing_it_wrong(
            sprintf('%s::has', self::class),
            sprintf(
                'The "%s" context flag cannot be determined before %s. Call Context::detect() later or %s.',
                $flag->value,
                $requiredState,
                $resolutionCheck
            ),
            '0.5.0'
        );
    }
}
