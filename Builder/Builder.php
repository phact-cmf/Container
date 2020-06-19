<?php declare(strict_types=1);

namespace Phact\Container\Builder;

use Closure;
use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Details\CallInterface;
use Phact\Container\Details\PropertyInterface;
use Phact\Container\Exceptions\InvalidConfigurationException;
use Phact\Container\Exceptions\InvalidFactoryException;
use Phact\Container\Exceptions\NotFoundException;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

class Builder implements BuilderInterface
{
    protected const DEPENDENCY_VALUE = 1;
    protected const DEPENDENCY_OBJECT_VALUE_REQUIRED = 2;
    protected const DEPENDENCY_OBJECT_VALUE_OPTIONAL = 3;
    protected const DEPENDENCY_REFERENCE_REQUIRED = 4;
    protected const DEPENDENCY_REFERENCE_OPTIONAL = 5;

    /**
     * Constructors dependencies
     * @var array
     */
    protected $constructors = [];

    /**
     * Callables dependencies
     * @var array
     */
    protected $callables = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $autoWire = true;

    public function __construct(bool $autoWire = true)
    {
        $this->autoWire = $autoWire;
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function construct(DefinitionInterface $definition): object
    {
        if ($definition->getFactory()) {
            $object = $this->makeObjectWithFactory($definition);
        } else {
            $object = $this->makeObjectSelf($definition);
        }
        return $object;
    }

    public function configure(object $object, DefinitionInterface $definition): object
    {
        $this->applyProperties($object, $definition->getProperties());
        $this->applyCalls($object, $definition->getCalls());
        return $object;
    }

    public function invoke(callable $callable, array $arguments = [])
    {
        return $this->call($callable, $arguments);
    }

    public function inflect(object $object, InflectionInterface $inflection): object
    {
        $this->applyCalls($object, $inflection->getCalls());
        $this->applyProperties($object, $inflection->getProperties());
        return $object;
    }

    /**
     * @param object $object
     * @param PropertyInterface[] $properties
     */
    protected function applyProperties(object $object, array $properties): void
    {
        foreach ($properties as $property) {
            $object->{$property->getName()} = $property->getValue();
        }
    }

    /**
     * @param object $object
     * @param CallInterface[] $calls
     * @throws NotFoundException
     * @throws ReflectionException
     */
    protected function applyCalls(object $object, array $calls): void
    {
        foreach ($calls as $call) {
            $this->call([$object, $call->getMethod()], $call->getArguments());
        }
    }

    /**
     * @param callable $callable
     * @param array $arguments
     * @return mixed
     * @throws NotFoundException
     * @throws ReflectionException
     */
    protected function call(callable $callable, array $arguments = [])
    {
        $dependencies = [];
        if ($this->autoWire) {
            $dependencies = $this->fetchCallableDependencies($callable);
        }
        $parameters = $this->buildParameters($arguments);
        $args = $this->buildArguments($dependencies, $parameters);
        return call_user_func_array($callable, $args);
    }

    protected function makeObjectSelf(DefinitionInterface $definition)
    {
        $dependencies = [];

        $className = $definition->getClass();
        if ($this->autoWire) {
            $dependencies = $this->fetchConstructorDependencies($className, $definition->getConstructMethod());
        }
        $parameters = $this->buildParameters($definition->getArguments());
        $arguments = $this->buildArguments($dependencies, $parameters);

        return $this->constructObject($definition->getClass(), $arguments, $definition->getConstructMethod());
    }

    protected function makeObjectWithFactory(DefinitionInterface $definition)
    {
        $factory = $definition->getFactory();

        if (!is_callable($factory) || is_array($factory)) {
            $factory = $this->buildFactoryFromNonCallable($definition);
        }

        $dependencies = [];
        if ($this->autoWire) {
            $dependencies = $this->fetchCallableDependencies($factory);
        }
        $parameters = $this->buildParameters($definition->getArguments());
        $arguments = $this->buildArguments($dependencies, $parameters);
        return call_user_func_array($factory, $arguments);
    }

    protected function buildFactoryFromNonCallable(DefinitionInterface $definition): callable
    {
        $factory = $definition->getFactory();
        $factoryId = null;
        $factoryMethod = null;
        if (is_string($factory)) {
            $factoryId = $this->fetchDependencyId($factory) ?: $factory;
            $factoryMethod = $definition->getConstructMethod() ?: '__invoke';
        } elseif (is_array($factory) && \count($factory) === 2) {
            $factoryId = $this->fetchDependencyId($factory[0]) ?: $factory[0];
            $factoryMethod = $factory[1];
        }
        if (!$this->container) {
            throw new InvalidConfigurationException('Please, provide container for usage non-callable factories');
        }
        if ($factoryId && $factoryMethod && $this->container->has($factoryId)) {
            $factoryResolved = $this->container->get($factoryId);
            return [$factoryResolved, $factoryMethod];
        }
        throw new InvalidFactoryException('Incorrect factory provided');
    }

    protected function constructObject(string $className, array $arguments, ?string $constructMethod = null)
    {
        if ($constructMethod !== null) {
            if ($arguments) {
                $obj = $className::$constructMethod(...$arguments);
            } else {
                $obj = $className::$constructMethod();
            }
        } else {
            if ($arguments) {
                $obj = new $className(...$arguments);
            } else {
                $obj = new $className;
            }
        }
        return $obj;
    }

    /**
     * Read callable dependencies
     *
     * @param $callable
     * @return array
     * @throws ReflectionException
     */
    protected function fetchCallableDependencies(callable $callable): array
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
            $this->callables[$name] = $this->fetchFunctionDependencies($reflection);
        }
        return $this->callables[$name];
    }

    /**
     * Read constructor dependencies as array
     *
     * @param string $className
     * @param string|null $constructMethod
     * @return array
     * @throws ReflectionException
     */
    protected function fetchConstructorDependencies(string $className, ?string $constructMethod = null): array
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
                $dependencies = $this->fetchFunctionDependencies($constructor);
            }
            $this->constructors[$key] = $dependencies;
        }
        return $this->constructors[$key];
    }


    /**
     * Fetch dependencies from function by reflection
     *
     * @param ReflectionFunctionAbstract $reflection
     * @return Dependency[] Dependencies
     */
    protected function fetchFunctionDependencies(ReflectionFunctionAbstract $reflection): array
    {
        $dependencies = [];
        foreach ($reflection->getParameters() as $param) {
            if ($param->isVariadic()) {
                break;
            }

            [$type, $value] = $this->getTypeValueByParameter($param);

            $dependencies[] = new Dependency(
                $type,
                $param->getName(),
                $value
            );
        }
        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return array
     */
    protected function getTypeValueByParameter(ReflectionParameter $parameter): array
    {
        $value = null;
        $type = self::DEPENDENCY_VALUE;

        if ($c = $parameter->getClass()) {
            $type = self::DEPENDENCY_OBJECT_VALUE_REQUIRED;
            if ($parameter->allowsNull()) {
                $type = self::DEPENDENCY_OBJECT_VALUE_OPTIONAL;
            }
            $value = $c->getName();
        } elseif ($parameter->isDefaultValueAvailable()) {
            $value = $parameter->getDefaultValue();
        }
        return [$type, $value];
    }

    /**
     * Build function attributes for type-value representation
     *
     * @param array $attributes
     * @return Parameter[]
     */
    protected function buildParameters($attributes = []): array
    {
        $parameters = [];
        foreach ($attributes as $key => $attribute) {
            $parameters[$key] = $this->buildParameter($attribute);
        }
        return $parameters;
    }

    /**
     * Fetching attribute value
     *
     * @param $value
     * @return Parameter
     */
    protected function buildParameter($value): Parameter
    {
        $type = self::DEPENDENCY_VALUE;
        if (\is_string($value) && 0 === strpos($value, '@')) {
            $type = self::DEPENDENCY_REFERENCE_REQUIRED;
            if (0 === strpos($value, '@?')) {
                $value = substr($value, 2);
                $type = self::DEPENDENCY_REFERENCE_OPTIONAL;
            } elseif (0 === strpos($value, '@@')) {
                $value = substr($value, 1);
                $type = self::DEPENDENCY_VALUE;
            } else {
                $value = substr($value, 1);
            }
        }
        return new Parameter($type, $value);
    }

    /**
     * @param string $value
     * @return string|null
     */
    protected function fetchDependencyId(string $value): ?string
    {
        if (0 === strpos($value, '@')) {
            return substr($value, 1);
        }
        return null;
    }

    /**
     * @param Dependency[] $dependencies
     * @param Parameter[] $parameters
     * @return array
     * @throws NotFoundException
     */
    protected function buildArguments(array $dependencies, array $parameters): array
    {
        $arguments = [];
        if ($dependencies) {
            $arguments = $this->buildArgumentsFromDependencies($dependencies, $parameters);
        } else {
            foreach ($parameters as $parameter) {
                $arguments[] = $this->makeArgument($parameter->getType(), $parameter->getValue());
            }
        }
        return $arguments;
    }

    /**
     * @param Dependency[] $dependencies
     * @param Parameter[] $parameters
     * @return array
     * @throws NotFoundException
     */
    protected function buildArgumentsFromDependencies(array $dependencies, array $parameters): array
    {
        $arguments = [];
        $usedParameters = [];

        foreach ($dependencies as $key => $dependency) {
            $type = $dependency->getType();
            $value = $dependency->getValue();

            $parameter = null;
            if (isset($parameters[$key])) {
                $parameter = $parameters[$key];
                $usedParameters[] = $key;
            } elseif (isset($parameters[$dependency->getName()])) {
                $parameter = $parameters[$dependency->getName()];
                $usedParameters[] = $dependency->getName();
            }

            if ($parameter) {
                $type = $parameter->getType();
                $value = $parameter->getValue();
            }

            $arguments[] = $this->makeArgument($type, $value);
        }

        foreach ($parameters as $key => $parameter) {
            if (!in_array($key, $usedParameters, true)) {
                $arguments[] = $this->makeArgument(
                    $parameter->getType(),
                    $parameter->getValue()
                );
            }
        }

        return $arguments;
    }

    /**
     * @param $type
     * @param $value
     * @return mixed
     * @throws NotFoundException|InvalidConfigurationException
     */
    protected function makeArgument(int $type, $value)
    {
        switch ($type) {
            case self::DEPENDENCY_REFERENCE_REQUIRED:
                if (!$this->container) {
                    throw new InvalidConfigurationException('Please, provide container for usage dependencies');
                }
                if ($this->container->has($value)) {
                    return $this->container->get($value);
                }
                throw new NotFoundException("There is no service with id {$value} found");

            case self::DEPENDENCY_OBJECT_VALUE_OPTIONAL:
            case self::DEPENDENCY_REFERENCE_OPTIONAL:
                if (!$this->container) {
                    throw new InvalidConfigurationException('Please, provide container for usage dependencies');
                }
                if ($this->container->has($value)) {
                    return $this->container->get($value);
                }
                return null;

            case self::DEPENDENCY_OBJECT_VALUE_REQUIRED:
                if (!$this->container) {
                    throw new InvalidConfigurationException('Please, provide container for usage dependencies');
                }
                if ($this->container->has($value)) {
                    return $this->container->get($value);
                }
                throw new NotFoundException("There is no referenced classes of {$value} found");
            default:
                return $value;
        }
    }
}
