<?php declare(strict_types=1);

namespace Phact\Container\Builder;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

class DependenciesResolver implements DependenciesResolverInterface
{
    /**
     * Constructors dependencies
     * @var array
     */
    protected $constructors = [];

    /**
     * Callable dependencies
     * @var array
     */
    protected $callables = [];

    /**
     * {@inheritDoc}
     */
    public function resolveConstructorDependencies(string $className, ?string $constructMethod = null): array
    {
        $key = $className . ($constructMethod ? '::' . $constructMethod : '');
        if (!isset($this->constructors[$key])) {
            $reflection = new ReflectionClass($className);
            $dependencies = [];
            if ($constructMethod === null) {
                $constructor = $reflection->getConstructor();
            } else {
                $constructor = new \ReflectionMethod($className, $constructMethod);
            }
            if ($constructor) {
                $dependencies = $this->resolveFunctionDependencies($constructor);
            }
            $this->constructors[$key] = $dependencies;
        }
        return $this->constructors[$key];
    }


    /**
     * {@inheritDoc}
     */
    public function resolveCallableDependencies(callable $callable): array
    {
        is_callable($callable, false, $name);
        if (!isset($this->callables[$name])) {
            if (is_array($callable)) {
                $reflection = new ReflectionMethod($callable[0], $callable[1]);
            } elseif (is_object($callable) && !$callable instanceof Closure) {
                $reflection = new ReflectionMethod($callable, '__invoke');
            } else {
                $reflection = new ReflectionFunction($callable);
            }
            $this->callables[$name] = $this->resolveFunctionDependencies($reflection);
        }
        return $this->callables[$name];
    }

    /**
     * Fetch dependencies from function by reflection
     *
     * @param ReflectionFunctionAbstract $reflection
     * @return Dependency[] Dependencies
     * @throws ReflectionException
     */
    protected function resolveFunctionDependencies(ReflectionFunctionAbstract $reflection): array
    {
        $dependencies = [];
        foreach ($reflection->getParameters() as $param) {
            if ($param->isVariadic()) {
                break;
            }

            list($type, $value) = $this->getTypeValueByParameter($param);

            $dependencies[] = new Dependency(
                $type,
                $param->name,
                $value
            );
        }
        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return array
     * @throws ReflectionException
     */
    protected function getTypeValueByParameter(ReflectionParameter $parameter): array
    {
        $value = null;
        $type = DependencyInterface::TYPE_VALUE;

        if ($c = $parameter->getClass()) {
            $type = DependencyInterface::TYPE_REQUIRED;
            if ($parameter->allowsNull()) {
                $type = DependencyInterface::TYPE_OPTIONAL;
            }
            $value = $c->name;
        } elseif ($parameter->isDefaultValueAvailable()) {
            $value = $parameter->getDefaultValue();
        }
        return [$type, $value];
    }
}
