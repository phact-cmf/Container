<?php declare(strict_types=1);

namespace Phact\Container\Definition;

use Phact\Container\Exceptions\DuplicateNameException;
use Phact\Container\Exceptions\NotFoundException;

class DefinitionAggregate implements DefinitionAggregateInterface
{
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
     * @var bool
     */
    protected $analyzeReferences = true;

    /**
     * Set analyze all added classes with class_parents and class_implements and create references.
     * When you add Child class to container and try get object by Parent class you will get Child class object
     * that described in container.
     *
     * @param bool $analyzeReferences
     */
    public function setAnalyzeReferences(bool $analyzeReferences): void
    {
        $this->analyzeReferences = $analyzeReferences;
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
     * Add references by class parents and interfaces
     *
     * @param string $name
     * @param string $class
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

    /**
     * {@inheritDoc}
     */
    public function get(string $id): DefinitionInterface
    {
        $name = $this->findDefinitionName($id);
        if ($name === null) {
            throw new NotFoundException("Could not resolve definition by id - {$id}");
        }
        return $this->definitions[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        return (
            isset($this->definitions[$id]) ||
            isset($this->aliases[$id]) ||
            ($this->analyzeReferences && isset($this->references[$id]))
        );
    }

    /**
     * Find definition name by aliases, references, etc
     *
     * @param string $id
     * @return string
     * @throws NotFoundException
     */
    public function resolveDefinitionName(string $id): string
    {
        $name = $this->findDefinitionName($id);
        if ($name === null) {
            throw new NotFoundException("Could not resolve definition by id - {$id}");
        }
        return $name;
    }

    /**
     * @param $id
     * @return string|null
     */
    protected function findDefinitionName($id): ?string
    {
        if (!isset($this->definitions[$id])) {
            if (isset($this->aliases[$id])) {
                $id = reset($this->aliases[$id]);
            }
            if ($this->analyzeReferences && isset($this->references[$id])) {
                $id = reset($this->references[$id]);
            }
        }
        if (!isset($this->definitions[$id])) {
            return null;
        }
        return $id;
    }
}
