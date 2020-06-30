<?php declare(strict_types=1);

namespace Phact\Container\Builder;

use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Details\CallInterface;
use Phact\Container\Details\PropertyInterface;
use Phact\Container\Exceptions\InvalidConfigurationException;
use Phact\Container\Exceptions\InvalidFactoryException;
use Phact\Container\Exceptions\NotFoundException;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface;

class Builder implements BuilderInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DependenciesResolver
     */
    protected $dependenciesResolver;

    /**
     * @var ArgumentsBuilder
     */
    protected $argumentsBuilder;

    protected $analyzeArguments = true;

    public function __construct(
        bool $analyzeArguments = true,
        ?DependenciesResolverInterface $dependenciesResolver = null
    ) {
        $this->analyzeArguments = $analyzeArguments;
        $this->dependenciesResolver = $dependenciesResolver ?: new DependenciesResolver();
        $this->argumentsBuilder = new ArgumentsBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
        $this->argumentsBuilder->setContainer($container);
    }

    /**
     * {@inheritDoc}
     */
    public function construct(DefinitionInterface $definition): object
    {
        if ($definition->getFactory()) {
            $object = $this->makeObjectWithFactory($definition);
        } else {
            $object = $this->makeObjectSelf($definition);
        }
        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function configure(object $object, DefinitionInterface $definition): object
    {
        $this->applyProperties($object, $definition->getProperties());
        $this->applyCalls($object, $definition->getCalls());
        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function invoke(callable $callable, array $arguments = [])
    {
        return $this->call($callable, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function inflect(object $object, InflectionInterface $inflection): object
    {
        $this->applyProperties($object, $inflection->getProperties());
        $this->applyCalls($object, $inflection->getCalls());
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
     */
    protected function call(callable $callable, array $arguments = [])
    {
        $dependencies = [];
        if ($this->analyzeArguments) {
            $dependencies = $this->dependenciesResolver->resolveCallableDependencies($callable);
        }
        $parameters = $this->buildParameters($arguments);
        $args = $this->argumentsBuilder->buildArguments($dependencies, $parameters);
        return call_user_func_array($callable, $args);
    }

    /**
     * Make object without factory
     *
     * @param DefinitionInterface $definition
     * @return mixed
     * @throws NotFoundException
     */
    protected function makeObjectSelf(DefinitionInterface $definition)
    {
        $dependencies = [];

        $className = $definition->getClass();
        if ($this->analyzeArguments) {
            $dependencies = $this->dependenciesResolver->resolveConstructorDependencies(
                $className,
                $definition->getConstructMethod()
            );
        }
        $parameters = $this->buildParameters($definition->getArguments());
        $arguments = $this->argumentsBuilder->buildArguments($dependencies, $parameters);

        return $this->constructObject($definition->getClass(), $arguments, $definition->getConstructMethod());
    }

    /**
     * Make object with factory
     *
     * @param DefinitionInterface $definition
     * @return mixed
     * @throws InvalidConfigurationException
     * @throws InvalidFactoryException
     * @throws NotFoundException
     */
    protected function makeObjectWithFactory(DefinitionInterface $definition)
    {
        $factory = $definition->getFactory();

        if (!is_callable($factory) || (is_array($factory))) {
            if (!$this->container) {
                throw new InvalidConfigurationException('Please, provide container for usage non-callable factories');
            }
            $factory = $this->buildFactoryFromNonCallable($definition);
        }

        $dependencies = [];
        if ($this->analyzeArguments) {
            $dependencies = $this->dependenciesResolver->resolveCallableDependencies($factory);
        }
        $parameters = $this->buildParameters($definition->getArguments());
        $arguments = $this->argumentsBuilder->buildArguments($dependencies, $parameters);
        return call_user_func_array($factory, $arguments);
    }

    /**
     * Build callable factory from non-callable
     *
     * @param DefinitionInterface $definition
     * @return callable
     * @throws InvalidFactoryException
     */
    protected function buildFactoryFromNonCallable(DefinitionInterface $definition): callable
    {
        $factory = $definition->getFactory();
        if (is_string($factory)) {
            return $this->resolveStringFactory($definition, $factory);
        }
        if (is_array($factory)) {
            return $this->resolveArrayFactory($definition, $factory);
        }
        throw new InvalidFactoryException('Incorrect factory provided, available string and array factories');
    }

    /**
     * @param DefinitionInterface $definition
     * @param string $factory
     * @return callable
     * @throws InvalidFactoryException
     */
    protected function resolveStringFactory(DefinitionInterface $definition, string $factory): callable
    {
        $id = $this->fetchDependencyId($factory);
        $method = $definition->getConstructMethod() ?: '__invoke';
        return $this->resolveFactoryFromContainer($id, $method);
    }

    /**
     * @param DefinitionInterface $definition
     * @param array $factory
     * @return callable
     * @throws InvalidFactoryException
     */
    protected function resolveArrayFactory(DefinitionInterface $definition, array $factory): callable
    {
        if (!isset($factory[0], $factory[1])) {
            throw new InvalidFactoryException('Array factory must contain 2 elements');
        }
        list($id, $method) = $factory;
        if (is_object($id)) {
            return $factory;
        }
        $id = $this->fetchDependencyId($id);
        return $this->resolveFactoryFromContainer($id, $method);
    }

    /**
     * Fetch factory from container
     *
     * @param $id
     * @param $method
     * @return callable
     * @throws InvalidFactoryException
     */
    protected function resolveFactoryFromContainer($id, $method): callable
    {
        if ($this->container->has($id)) {
            $factoryResolved = $this->container->get($id);
            return [$factoryResolved, $method];
        }
        throw new InvalidFactoryException('Incorrect factory provided');
    }

    /**
     * Create object with provided arguments and optional construct method
     *
     * @param string $className
     * @param array $arguments
     * @param string|null $constructMethod
     * @return mixed
     */
    protected function constructObject(string $className, array $arguments, ?string $constructMethod = null)
    {
        if ($constructMethod !== null) {
            $obj = $className::$constructMethod(...$arguments);
        } else {
            $obj = new $className(...$arguments);
        }
        return $obj;
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
     * Create parameter from provided argument
     *
     * @param $value
     * @return ParameterInterface
     */
    protected function buildParameter($value): ParameterInterface
    {
        $type = ParameterInterface::TYPE_VALUE;
        if (\is_string($value) && 0 === strpos($value, '@')) {
            $type = ParameterInterface::TYPE_REFERENCE_REQUIRED;
            if (0 === strpos($value, '@?')) {
                $value = substr($value, 2);
                $type = ParameterInterface::TYPE_REFERENCE_OPTIONAL;
            } elseif (0 === strpos($value, '@@')) {
                $value = substr($value, 1);
                $type = DependencyInterface::TYPE_VALUE;
            } else {
                $value = substr($value, 1);
            }
        }
        return new Parameter($type, $value);
    }

    /**
     * Try fetch dependency name from string value
     *
     * @param string $value
     * @return string|null
     */
    protected function fetchDependencyId(string $value): ?string
    {
        if (0 === strpos($value, '@')) {
            return substr($value, 1);
        }
        return $value;
    }
}
