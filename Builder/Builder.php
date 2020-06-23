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

    protected $autoWire = true;

    public function __construct(bool $autoWire = true, ?DependenciesResolverInterface $dependenciesResolver = null)
    {
        $this->autoWire = $autoWire;
        $this->dependenciesResolver = $dependenciesResolver ?: new DependenciesResolver();
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
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
        if ($this->autoWire) {
            $dependencies = $this->dependenciesResolver->resolveCallableDependencies($callable);
        }
        $parameters = $this->buildParameters($arguments);
        $args = $this->buildArguments($dependencies, $parameters);
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
        if ($this->autoWire) {
            $dependencies = $this->dependenciesResolver->resolveConstructorDependencies(
                $className,
                $definition->getConstructMethod()
            );
        }
        $parameters = $this->buildParameters($definition->getArguments());
        $arguments = $this->buildArguments($dependencies, $parameters);

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

        if (!is_callable($factory) || is_array($factory)) {
            if (!$this->container) {
                throw new InvalidConfigurationException('Please, provide container for usage non-callable factories');
            }
            $factory = $this->buildFactoryFromNonCallable($definition);
        }

        $dependencies = [];
        if ($this->autoWire) {
            $dependencies = $this->dependenciesResolver->resolveCallableDependencies($factory);
        }
        $parameters = $this->buildParameters($definition->getArguments());
        $arguments = $this->buildArguments($dependencies, $parameters);
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
            $factoryId = $this->fetchDependencyId($factory);
            $factoryMethod = $definition->getConstructMethod() ?: '__invoke';
        } elseif (is_array($factory)) {
            $factoryId = $this->fetchDependencyId($factory[0]);
            $factoryMethod = $factory[1];
        } else {
            throw new InvalidFactoryException('Incorrect factory provided, available string and array factories');
        }
        if ($this->container->has($factoryId)) {
            $factoryResolved = $this->container->get($factoryId);
            return [$factoryResolved, $factoryMethod];
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

    /**
     * Build arguments by dependencies and parameters
     *
     * @param DependencyInterface[] $dependencies
     * @param ParameterInterface[] $parameters
     * @return array
     * @throws NotFoundException
     */
    protected function buildArguments(array $dependencies, array $parameters): array
    {
        $arguments = [];
        if (count($dependencies) > 0) {
            $arguments = $this->buildArgumentsFromDependencies($dependencies, $parameters);
        } else {
            foreach ($parameters as $parameter) {
                $arguments[] = $this->makeArgumentByParameter($parameter);
            }
        }
        return $arguments;
    }

    /**
     * @param DependencyInterface[] $dependencies
     * @param ParameterInterface[] $parameters
     * @return array
     * @throws NotFoundException
     */
    protected function buildArgumentsFromDependencies(array $dependencies, array $parameters): array
    {
        $arguments = [];
        $usedParameters = [];

        foreach ($dependencies as $key => $dependency) {
            /** @var ParameterInterface $parameter */
            $parameter = null;
            if (isset($parameters[$key])) {
                $parameter = $parameters[$key];
                $usedParameters[] = $key;
                $arguments[] = $this->makeArgumentByParameter($parameter);
            } elseif (isset($parameters[$dependency->getName()])) {
                $parameter = $parameters[$dependency->getName()];
                $usedParameters[] = $dependency->getName();
                $arguments[] = $this->makeArgumentByParameter($parameter);
            } else {
                $arguments[] = $this->makeArgumentByDependency($dependency);
            }
        }

        $arguments = $this->appendUnusedParamsToArguments($parameters, $arguments, $usedParameters);

        return $arguments;
    }

    /**
     * @param array $parameters
     * @param array $usedParameters
     * @param array $arguments
     * @return array
     * @throws NotFoundException
     */
    protected function appendUnusedParamsToArguments(
        array $parameters,
        array $arguments,
        array $usedParameters = []
    ): array {
        foreach ($parameters as $key => $parameter) {
            if (!in_array($key, $usedParameters, true)) {
                $arguments[] = $this->makeArgumentByParameter($parameter);
            }
        }
        return $arguments;
    }

    /**
     * @param ParameterInterface $parameter
     * @return mixed
     * @throws NotFoundException
     */
    protected function makeArgumentByParameter(ParameterInterface $parameter)
    {
        switch ($parameter->getType()) {
            case ParameterInterface::TYPE_REFERENCE_REQUIRED:
                return $this->retrieveRequiredDependencyFromContainer($parameter->getValue());

            case ParameterInterface::TYPE_REFERENCE_OPTIONAL:
                return $this->retrieveOptionalDependencyFromContainer($parameter->getValue());
        }
        return $parameter->getValue();
    }

    /**
     * @param DependencyInterface $dependency
     * @return mixed
     * @throws NotFoundException
     */
    protected function makeArgumentByDependency(DependencyInterface $dependency)
    {
        switch ($dependency->getType()) {
            case DependencyInterface::TYPE_REQUIRED:
                return $this->retrieveRequiredDependencyFromContainer($dependency->getValue());

            case DependencyInterface::TYPE_OPTIONAL:
                return $this->retrieveOptionalDependencyFromContainer($dependency->getValue());
        }
        return $dependency->getValue();
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundException
     */
    protected function retrieveRequiredDependencyFromContainer($id)
    {
        if ($this->container && $this->container->has($id)) {
            return $this->container->get($id);
        }
        throw new NotFoundException("There is no referenced classes of {$id} found");
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function retrieveOptionalDependencyFromContainer($id)
    {
        if ($this->container && $this->container->has($id)) {
            return $this->container->get($id);
        }
        return null;
    }
}
