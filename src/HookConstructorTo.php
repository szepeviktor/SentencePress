<?php // phpcs:disable NeutronStandard.MagicMethods.RiskyMagicMethod.RiskyMagicMethod

/**
 * Ultra simple hooking for class constructor.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use ReflectionClass;

use function add_action;

/**
 * Hook class constructor on to a specific action.
 *
 * Only actions are supported.
 * Example call with priority zero.
 *
 *     HookConstructorTo::{'acf/init'}(MyClass::class, 0);
 */
class HookConstructorTo
{
    protected const DEFAULT_PRIORITY = 10;

    /**
     * Hook to the action in the method name.
     *
     * @param string $actionTag
     * @param array{class-string, ?int} $arguments
     */
    public static function __callStatic(string $actionTag, array $arguments): void
    {
        if ($arguments === []) {
            throw new \ArgumentCountError('Class name must be supplied.');
        }

        // phpcs:ignore SlevomatCodingStandard.PHP.RequireExplicitAssertion.RequiredExplicitAssertion
        /** @var class-string $class */
        $class = $arguments[0];

        $constructor = (new ReflectionClass($class))->getConstructor();
        if ($constructor === null) {
            throw new \ErrorException('The class must have a constructor defined.');
        }

        // Hook the constructor.
        add_action(
            $actionTag,
            static function () use ($class): void {
                // Pass hook parameters to constructor.
                $args = func_get_args();
                // phpcs:ignore NeutronStandard.Functions.VariableFunctions.VariableFunction
                new $class(...$args);
            },
            \intval($arguments[1] ?? self::DEFAULT_PRIORITY),
            $constructor->getNumberOfParameters()
        );
    }
}
