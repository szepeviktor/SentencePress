<?php // phpcs:disable NeutronStandard.MagicMethods.RiskyMagicMethod.RiskyMagicMethod

/**
 * Ultra simple hooking for init() method.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use ReflectionClass;

use function add_filter;

/**
 * Hook init() method on to a specific action or filter.
 *
 * Example call with priority zero.
 *
 *     HookInitTo::plugins_loaded(MyClass::class, 0);
 */
class HookInitTo
{
    protected const DEFAULT_PRIORITY = 10;

    /**
     * Hook to the action in the method name.
     *
     * @param string $actionTag
     * @param array{0?: class-string, 1?: int} $arguments
     *
     * @throws \ArgumentCountError
     * @throws \ReflectionException
     */
    public static function __callStatic(string $actionTag, array $arguments): void
    {
        if (!isset($arguments[0])) {
            throw new \ArgumentCountError('Class name must be supplied.');
        }

        // phpcs:ignore SlevomatCodingStandard.PHP.RequireExplicitAssertion.RequiredExplicitAssertion
        /** @var class-string $class */
        $class = $arguments[0];

        $initMethod = (new ReflectionClass($class))->getMethod('init');

        // Hook 'init' method.
        add_filter(
            $actionTag,
            // phpcs:ignore NeutronStandard.Functions.TypeHint.NoReturnType
            static function () use ($class) {
                // phpcs:ignore NeutronStandard.Functions.VariableFunctions.VariableFunction
                $instance = new $class();
                // Pass hook parameters to init()
                $args = func_get_args();

                return $instance->init(...$args);
            },
            \intval($arguments[1] ?? self::DEFAULT_PRIORITY),
            $initMethod->getNumberOfParameters()
        );
    }
}
