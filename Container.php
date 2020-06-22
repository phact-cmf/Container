<?php declare(strict_types=1);

namespace Phact\Container;

use Phact\Container\Builder\Builder;
use Phact\Container\Builder\BuilderInterface;
use Phact\Container\Definition\Definition;
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
     * @var array
     */
    protected $scalars = [];

    /**
     * @var Definition[]
     */
    protected $definitions = [];

    /**
     * @var array
     */
    protected $references = [];

    /**
     * @var array
     */
    protected $aliases = [];

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
     * @var bool
     */
    protected $analyzeReferences = true;

    /**
     * @var BuilderInterface
     */
    protected $builder;

    public function __construct(?BuilderInterface $builder = null)
    {
        if (!$builder) {
            $builder = new Builder();
        }
        $this->builder = $builder;
        $this->builder->setContainer($this);
    }

    /**
     * {@inheritDoc}
     */
    public function addDefinitionClass(string $name, string $class): DefinitionInterface
    {
        return $this->addDefinition($name, new Definition($class));
    }

    /**
     * {@inheritDoc}
     */
    public function addDefinition(string $name, DefinitionInterface $definition): DefinitionInterface
    {
        if (isset($this->definitions[$name])) {
            throw new DuplicateNameException("Definition with name {$name} already exists.");
        }
        $this->definitions[$name] = $definition;
        if ($this->analyzeReferences) {
            $this->addReferences($name, $definition->getClass());
        }
        $this->addAliases($name, $definition->getAliases());
        return $definition;
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
    protected function addReferences(string $name, string $class): void
    {
        $this->addReference($name, $class);
        $interfaces = class_implements($class);
        foreach ($interfaces as $interface) {
            $this->addReference($name, $interface);
        }
        $parents = class_parents($class);
        foreach ($parents as $parent) {
            $this->addReference($name, $parent);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addReference(string $name, string $class): void
    {
        if (!isset($this->references[$class])) {
            $this->references[$class] = [];
        }
        $this->references[$class][] = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function addAliases(string $name, array $aliases = []): void
    {
        foreach ($aliases as $alias) {
            $this->addAlias($name, $alias);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addAlias(string $name, string $alias): void
    {
        if (!isset($this->aliases[$alias])) {
            $this->aliases[$alias] = [];
        }
        $this->aliases[$alias][] = $name;
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
     * Resolve definition by provided id from definitions, aliases and references
     *
     * @param string $id
     * @return object
     * @throws CircularException
     */
    protected function resolveDefinitionById(string $id): object
    {
        if (!isset($this->definitions[$id])) {
            if (isset($this->aliases[$id])) {
                $id = reset($this->aliases[$id]);
            }
            if ($this->analyzeReferences && isset($this->references[$id])) {
                $id = reset($this->references[$id]);
            }
        }
        return $this->resolveDefinition($this->definitions[$id], $id);
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
        if ($id && $definition->isShared()) {
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
     * Check that definition exists as definition or alias or reference
     *
     * @param string $id
     * @return bool
     */
    protected function hasDefinition(string $id): bool
    {
        return (
            isset($this->definitions[$id]) ||
            isset($this->aliases[$id]) ||
            ($this->analyzeReferences && isset($this->references[$id]))
        );
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
        if (isset($this->shared[$id])) {
            return $this->shared[$id];
        }

        if (isset($this->scalars[$id])) {
            return $this->scalars[$id];
        }

        if ($this->hasDefinition($id)) {
            return $this->resolveDefinitionById($id);
        }

        if ($this->autoWire && class_exists($id)) {
            return $this->resolveDefinition(
                (new Definition($id))->setShared(true)
            );
        }

        if ($resolved = $this->getFromDelegates($id)) {
            return $resolved;
        }

        throw new NotFoundException("Could not resolve element by id - {$id}");
    }

    /**
     * Try to find entry in delegates
     *
     * @param $id
     * @return mixed|object|null
     */
    public function getFromDelegates($id)
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
        if (isset($this->shared[$id])) {
            return true;
        }

        if (isset($this->scalars[$id])) {
            return true;
        }

        if ($this->hasDefinition($id)) {
            return true;
        }

        if ($this->autoWire && class_exists($id)) {
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
     * @inheritDoc
     */
    public function addDelegate(PsrContainerInterface $container, bool $applyInflection = true): void
    {
        $this->delegates[] = new Delegate($container, $applyInflection);
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

    /**
     * Set analyze all added classes with class_parents and class_implements and create references.
     * When you add Child class to container and try get object by Parent class you will get Child class object
     * that described in container.
     *
     * @param bool $analyzeReferences
     * @return $this
     */
    public function setAnalyzeReferences(bool $analyzeReferences): self
    {
        $this->analyzeReferences = $analyzeReferences;
        return $this;
    }
}