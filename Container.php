<?php declare(strict_types=1);

namespace Phact\Container;

use Phact\Container\Builder\Builder;
use Phact\Container\Builder\BuilderInterface;
use Phact\Container\Definition\Definition;
use Phact\Container\Definition\DefinitionAggregate;
use Phact\Container\Definition\DefinitionAggregateInterface;
use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Delegate\Delegate;
use Phact\Container\Exceptions\CircularException;
use Phact\Container\Exceptions\DuplicateNameException;
use Phact\Container\Exceptions\NotFoundException;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var DefinitionAggregate
     */
    protected $definitionAggregate;

    /**
     * @var array
     */
    protected $scalars = [];

    /**
     * Resolved shared instances
     *
     * @var array
     */
    protected $shared = [];

    /**
     * @var InflectionInterface[]
     */
    protected $inflections = [];

    /**
     * @var Delegate[]
     */
    protected $delegates = [];

    /**
     * Now loading services
     * @var array
     */
    protected $loading = [];

    /**
     * @var bool
     */
    protected $autoWire = true;

    /**
     * @var BuilderInterface
     */
    protected $builder;

    public function __construct(
        ?BuilderInterface $builder = null,
        ?DefinitionAggregateInterface $definitionAggregate = null
    ) {
        $this->builder = $builder ?: new Builder();
        $this->builder->setContainer($this);

        $this->definitionAggregate = $definitionAggregate ?: new DefinitionAggregate();
    }

    /**
     * {@inheritDoc}
     */
    public function addDefinitionClass(string $name, string $class): DefinitionInterface
    {
        return $this->definitionAggregate->addDefinition($name, new Definition($class));
    }

    /**
     * {@inheritDoc}
     */
    public function addDefinition(string $name, DefinitionInterface $definition): DefinitionInterface
    {
        return $this->definitionAggregate->addDefinition($name, $definition);
    }

    /**
     * {@inheritDoc}
     */
    public function addScalar(string $name, $value): void
    {
        $this->scalars[$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function addReference(string $name, string $class): void
    {
        $this->definitionAggregate->addReference($name, $class);
    }

    /**
     * {@inheritDoc}
     */
    public function addAliases(string $name, array $aliases = []): void
    {
        $this->definitionAggregate->addAliases($name, $aliases);
    }

    /**
     * {@inheritDoc}
     */
    public function addAlias(string $name, string $alias): void
    {
        $this->definitionAggregate->addAlias($name, $alias);
    }

    /**
     * {@inheritDoc}
     */
    public function addInflection(InflectionInterface $inflection): InflectionInterface
    {
        $this->inflections[] = $inflection;
        return $inflection;
    }


    /**
     * @inheritDoc
     */
    public function addDelegate(PsrContainerInterface $container, bool $applyInflection = true): void
    {
        $this->delegates[] = new Delegate($container, $applyInflection);
    }

    /**
     * Create object by DefinitionInterface object or retrieve it from shared
     *
     * @param DefinitionInterface $definition
     * @param string|null $id
     * @return object
     * @throws CircularException
     */
    protected function resolveDefinition(DefinitionInterface $definition, ?string $id = null): object
    {
        $this->setLoading($id);
        $object = $this->builder->construct($definition);
        if ($definition->isShared()) {
            $this->shared[$id] = $object;
        }
        $object = $this->builder->configure($object, $definition);
        $object = $this->inflect($object);
        $this->unsetLoading($id);
        return $object;
    }

    /**
     * Set that certain service are loading and checks circular exception
     *
     * @param string|null $id
     * @throws CircularException
     */
    protected function setLoading(?string $id): void
    {
        if ($id === null) {
            return;
        }
        if (isset($this->loading[$id])) {
            $loadingServices = implode(', ', array_keys($this->loading));
            throw new CircularException(sprintf("Circular dependency detected with services - %s", $loadingServices));
        }
        $this->loading[$id] = true;
    }

    /**
     * Unset that certain service are loading
     *
     * @param string|null $id
     */
    protected function unsetLoading(?string $id): void
    {
        if ($id === null) {
            return;
        }
        unset($this->loading[$id]);
    }

    /**
     * Apply all necessary inflections to object
     *
     * @param object $object
     * @return object
     */
    protected function inflect(object $object): object
    {
        foreach ($this->inflections as $inflection) {
            if ($inflection->canBeAppliedTo($object)) {
                $object = $this->builder->inflect($object, $inflection);
            }
        }
        return $object;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (isset($this->scalars[$id])) {
            return $this->scalars[$id];
        }

        if ($this->definitionAggregate->has($id)) {
            return $this->getFromDefinitions($id);
        }

        if ($this->isAutoWireAvailiableForClass($id)) {
            return $this->resolveDefinition(
                (new Definition($id))->setShared(false)
            );
        }

        if ($resolved = $this->getFromDelegates($id)) {
            return $resolved;
        }

        throw new NotFoundException("Could not resolve element by id - {$id}");
    }

    /**
     * Check that autowire is enabled and class can be loaded
     *
     * @param string $class
     * @return bool
     */
    protected function isAutoWireAvailiableForClass(string $class): bool
    {
        return $this->autoWire && class_exists($class);
    }

    /**
     * Try to find entry in definitions or shared
     *
     * @param string $id
     * @return object
     * @throws CircularException
     * @throws NotFoundException
     */
    protected function getFromDefinitions(string $id): object
    {
        $id = $this->definitionAggregate->resolveDefinitionName($id);
        return $this->shared[$id] ?? $this->resolveDefinition($this->definitionAggregate->get($id), $id);
    }

    /**
     * Try to find entry in delegates
     *
     * @param $id
     * @return object|null
     */
    protected function getFromDelegates($id)
    {
        foreach ($this->delegates as $delegate) {
            if ($delegate->getContainer()->has($id)) {
                $resolved = $delegate->getContainer()->get($id);
                if ($delegate->isApplyInflection()) {
                    $resolved = $this->inflect($resolved);
                }
                return $resolved;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        if (isset($this->scalars[$id])) {
            return true;
        }

        if ($this->definitionAggregate->has($id)) {
            return true;
        }

        if ($this->isAutoWireAvailiableForClass($id)) {
            return true;
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->getContainer()->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function invoke(callable $callable, array $arguments = [])
    {
        return $this->builder->invoke($callable, $arguments);
    }

    /**
     * Set that all dependencies will be analyzed for constructors, callable objects and methods
     *
     * @param bool $autoWire
     * @return $this
     */
    public function setAutoWire(bool $autoWire): self
    {
        $this->autoWire = $autoWire;
        return $this;
    }
}
