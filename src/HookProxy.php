<?php // phpcs:disable NeutronStandard.Functions.DisallowCallUserFunc.CallUserFunc

/**
 * Hook proxy for lazy loading.
 *
 * @author  Viktor Szépe <viktor@szepe.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/szepeviktor/SentencePress
 */

declare(strict_types=1);

namespace SzepeViktor\SentencePress;

use Closure;
use ReflectionClass;
use ReflectionMethod;

use function _wp_filter_build_unique_id;
use function add_filter;
use function remove_filter;

/**
 * Implement lazy hooking.
 */
trait HookProxy
{
    use HookAnnotation;

    /** @var array<string, \Closure(mixed ...$args): mixed> */
    protected $callablesAdded;

    protected function lazyHookFunction(
        string $actionTag,
        callable $callable,
        int $priority,
        int $argumentCount,
        string $filePath
    ): void {
        add_filter(
            $actionTag,
            $this->generateClosureWithFileLoad($actionTag, $callable, $filePath),
            $priority,
            $argumentCount
        );
    }

    protected function lazyHookStaticMethod(
        string $actionTag,
        callable $callable,
        int $priority,
        int $argumentCount
    ): void {
        add_filter(
            $actionTag,
            $this->generateClosure($actionTag, $callable),
            $priority,
            $argumentCount
        );
    }

    protected function lazyHookMethod(
        string $actionTag,
        callable $callable,
        int $priority,
        int $argumentCount,
        ?callable $injector = null
    ): void {
        add_filter(
            $actionTag,
            $this->generateClosureWithInjector($actionTag, $callable, $injector),
            $priority,
            $argumentCount
        );
    }

    /**
     * This is not really lazy hooking as class must be loaded to use reflections.
     *
     * @param class-string $className
     */
    protected function lazyHookAllMethods(
        string $className,
        int $defaultPriority = 10,
        ?callable $injector = null
    ): void {
        $classReflection = new ReflectionClass($className);
        // Look for hook tag in all public methods.
        foreach ($classReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Do not hook constructor.
            if ($method->isConstructor()) {
                continue;
            }
            $hookDetails = $this->getMetadata((string)$method->getDocComment(), $defaultPriority);
            if ($hookDetails === null) {
                continue;
            }

            add_filter(
                $hookDetails['tag'],
                $this->generateClosureWithInjector($hookDetails['tag'], [$className, $method->name], $injector),
                $hookDetails['priority'],
                $method->getNumberOfParameters()
            );
        }
    }

    protected function unhook(
        string $actionTag,
        callable $callable,
        int $priority
    ): void {
        $id = $this->buildUniqueId($actionTag, $callable);
        if (! array_key_exists($id, $this->callablesAdded)) {
            return;
        }

        remove_filter(
            $actionTag,
            $this->callablesAdded[$id],
            $priority
        );
        unset($this->callablesAdded[$id]);
    }

    // phpcs:disable NeutronStandard.Functions.TypeHint.NoReturnType

    protected function generateClosure(string $actionTag, callable $callable): Closure
    {
        $id = $this->buildUniqueId($actionTag, $callable);
        $this->callablesAdded[$id] = static function (...$args) use ($callable) {
            return call_user_func_array($callable, $args);
        };

        return $this->callablesAdded[$id];
    }

    protected function generateClosureWithFileLoad(string $actionTag, callable $callable, string $filePath): Closure
    {
        $id = $this->buildUniqueId($actionTag, $callable);
        $this->callablesAdded[$id] = static function (...$args) use ($filePath, $callable) {
            require_once $filePath;

            return call_user_func_array($callable, $args);
        };

        return $this->callablesAdded[$id];
    }

    protected function generateClosureWithInjector(string $actionTag, callable $callable, ?callable $injector): Closure
    {
        if (! is_array($callable)) {
            throw new \InvalidArgumentException('Callable is not an array: ' . var_export($callable, true));
        }

        $id = $this->buildUniqueId($actionTag, $callable);
        $this->callablesAdded[$id] = $injector === null
            ? static function (...$args) use ($callable) {
                return call_user_func_array($callable, $args);
            }
            : static function (...$args) use ($injector, $callable) {
                $instance = call_user_func($injector, $callable[0]);

                return call_user_func_array([$instance, $callable[1]], $args);
            };

        return $this->callablesAdded[$id];
    }

    protected function buildUniqueId(string $actionTag, callable $callable): string
    {
        return sprintf('%s/%s', $actionTag, _wp_filter_build_unique_id('', $callable, 0));
    }
}
// @TODO Measurements: w/o OPcache, OPcache with file read, OPcache without file read
// @TODO Add tests, remove_action, usage as filter with returned value,
//       one callable hooked to many action tags then removed
