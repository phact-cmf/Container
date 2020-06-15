<?php declare(strict_types=1);

namespace Phact\Container;

use Phact\Container\Builder\Builder;
use Phact\Container\Builder\BuilderInterface;
use Phact\Container\Definition\Definition;
use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Exceptions\CircularException;
use Phact\Container\Exceptions\DuplicateNameException;
use Phact\Container\Exceptions\NotFoundException;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface;

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

    public function addDefinitionClass(string $name, string $class): DefinitionInterface
    {
        return $this->addDefinition($name, new Definition($class));
    }

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

    public function addScalar(string $name, $value): void
    {
        $this->scalars[$name] = $value;
    }

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

    public function addReference(string $name, string $class): void
    {
        if (!isset($this->references[$class])) {
            $this->references[$class] = [];
        }
        $this->references[$class][] = $name;
    }

    public function addAliases(string $name, array $aliases = []): void
    {
        foreach ($aliases as $alias) {
            $this->addAlias($name, $alias);
        }
    }

    public function addAlias(string $name, string $alias): void
    {
        if (!isset($this->aliases[$alias])) {
            $this->aliases[$alias] = [];
        }
        $this->aliases[$alias][] = $name;
    }

    public function addInflection(InflectionInterface $inflection): InflectionInterface
    {
        $this->inflections[] = $inflection;
        return $inflection;
    }

    protected function resolveDefinitionById(string $id): object
    {
        if (!isset($this->definitions[$id])) {
            if ($this->aliases[$id]) {
                $id = reset($this->aliases[$id]);
            }
            if ($this->analyzeReferences && isset($this->references[$id])) {
                $id = reset($this->references[$id]);
            }
        }
        return $this->resolveDefinition($this->definitions[$id], $id);
    }

    protected function resolveDefinition(DefinitionInterface $definition, ?string $id = null): object
    {
        $this->setLoading($id);
        $object = $this->builder->construct($definition);
        if ($id && $definition->isShared()) {
            $this->shared[$id] = $object;
        }
        $object = $this->builder->configure($object, $definition);
        $this->inflect($object);
        $this->unsetLoading($id);
    }

    protected function setLoading(string $id): void
    {
        if (isset($this->loading[$id])) {
            $loadingServices = implode(', ', array_keys($this->loading));
            throw new CircularException(sprintf("Circular dependency detected with services - %s", $loadingServices));
        }
        $this->loading[$id] = true;
    }

    protected function unsetLoading(string $id): void
    {
        unset($this->loading[$id]);
    }

    protected function hasDefinition(string $id): bool
    {
        return (
            isset($this->definitions[$id]) ||
            isset($this->aliases[$id]) ||
            ($this->analyzeReferences && isset($this->references[$id]))
        );
    }

    protected function inflect(object $object): void
    {
        foreach ($this->inflections as $inflection) {
            if ($inflection->canBeAppliedTo($object)) {
                $this->builder->inflect($object, $inflection);
            }
        }
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
            $this->resolveDefinition(
                (new Definition($id))->setShared(true)
            );
        }

        throw new NotFoundException("Could not resolve element by id - {$id}");
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

        return false;
    }

    public function invoke(callable $callable, array $arguments = [])
    {
        return $this->builder->invoke($callable, $arguments);
    }

    public function setAutoWire(bool $autoWire): self
    {
        $this->autoWire = $autoWire;
        return $this;
    }

    public function setAnalyzeReferences(bool $analyzeReferences): self
    {
        $this->analyzeReferences = $analyzeReferences;
        return $this;
    }
}