<?php // phpcs:disable NeutronStandard.Functions.DisallowCallUserFunc.CallUserFunc,SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint

/**
 * Hook proxy for lazy loading using a central array.
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
use function current_filter;
use function remove_filter;

/**
 * Implement lazy hooking.
 * @phpstan-ignore trait.unused
 */
trait HookProxyListed
{
    use HookAnnotation;

    /** @var array<string, list<array{callable:\Closure(mixed ...$args): mixed, ?filePath:string, ?injector:callable}>> */
    protected $callablesAdded;

    /** @var list<array{callable:\Closure(mixed ...$args): mixed, ?filePath:string, ?injector:callable}> */
    protected $currentCallables;

    /**
     * @return mixed
     *
     * phpcs:disable NeutronStandard.Functions.TypeHint.NoReturnType
     */
    public function receiver(...$args)
    {
        // @FIXME Multiple (priority) hooking of receiver is not possible!
        $this->currentCallables = $this->callablesAdded[current_filter()];

        return call_user_func_array('TODO', $args);
    }

    protected function lazyHookFunction(
        string $actionTag,
        callable $callback,
        int $priority,
        int $argumentCount,
        string $filePath
    ): void {
        $this->callablesAdded[$actionTag][$priority] = [
            'callback' => $callback,
            'argumentCount' => $argumentCount,
            'filePath' => $filePath,
        ];
        add_filter(
            $actionTag,
            [$this, 'receiver'],
            $priority,
            $argumentCount
        );
    }

    protected function lazyHookStaticMethod(
        string $actionTag,
        callable $callback,
        int $priority,
        int $argumentCount
    ): void {
        add_filter(
            $actionTag,
            $this->generateClosure($actionTag, $callback),
            $priority,
            $argumentCount
        );
    }

    protected function lazyHookMethod(
        string $actionTag,
        callable $callback,
        int $priority,
        int $argumentCount,
        ?callable $injector = null
    ): void {
        add_filter(
            $actionTag,
            $this->generateClosureWithInjector($actionTag, $callback, $injector),
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
        callable $callback,
        int $priority
    ): void {
        $id = $this->buildUniqueId($actionTag, $callback);
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

    protected function generateClosure(string $actionTag, callable $callback): Closure
    {
        $id = $this->buildUniqueId($actionTag, $callback);
        $this->callablesAdded[$id] = static function (...$args) use ($callback) {
            return call_user_func_array($callback, $args);
        };

        return $this->callablesAdded[$id];
    }

    protected function generateClosureWithFileLoad(string $actionTag, callable $callback, string $filePath): Closure
    {
        $id = $this->buildUniqueId($actionTag, $callback);
        $this->callablesAdded[$id] = static function (...$args) use ($filePath, $callback) {
            require_once $filePath;

            return call_user_func_array($callback, $args);
        };

        return $this->callablesAdded[$id];
    }

    protected function generateClosureWithInjector(string $actionTag, callable $callback, ?callable $injector): Closure
    {
        if (! is_array($callback)) {
            throw new \InvalidArgumentException(
                sprintf('Callable is not an array: %s', var_export($callback, true))
            );
        }

        $id = $this->buildUniqueId($actionTag, $callback);
        $this->callablesAdded[$id] = $injector === null
            ? static function (...$args) use ($callback) {
                return call_user_func_array($callback, $args);
            }
            : static function (...$args) use ($injector, $callback) {
                $instance = call_user_func($injector, $callback[0]);

                return call_user_func_array([$instance, $callback[1]], $args);
            };

        return $this->callablesAdded[$id];
    }

    protected function buildUniqueId(callable $callback): string
    {
        return _wp_filter_build_unique_id('', $callback, 0);
    }
}
// TODO Measurements: w/o OPcache, OPcache with file read, OPcache without file read
// TODO Add tests, remove_action, usage as filter with returned value,
//      one callable hooked to many action tags then removed
