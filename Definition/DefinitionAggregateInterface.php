<?php declare(strict_types=1);

namespace Phact\Container\Definition;

use Phact\Container\Exceptions\DuplicateNameException;
use Phact\Container\Exceptions\NotFoundException;

interface DefinitionAggregateInterface
{
    /**
     * Add definition by DefinitionInterface object
     *
     * @param string $name
     * @param DefinitionInterface $definition
     * @return DefinitionInterface
     * @throws DuplicateNameException If service with provided name already defined
     */
    public function addDefinition(string $name, DefinitionInterface $definition): DefinitionInterface;

    /**
     * When requesting an object of a certain class, the object will be returned by the corresponding name
     *
     * @param string $name
     * @param string $class
     */
    public function addReference(string $name, string $class): void;

    /**
     * Add aliases of the service name
     *
     * @param string $name
     * @param array $aliases
     */
    public function addAliases(string $name, array $aliases = []): void;

    /**
     * Add alias of the service name
     *
     * @param string $name
     * @param string $alias
     */
    public function addAlias(string $name, string $alias): void;

    /**
     * Find definition name by aliases, references, etc
     *
     * @param string $id
     * @return string
     * @throws NotFoundException
     */
    public function resolveDefinitionName(string $id): string;

    /**
     * Get definition by id
     *
     * @param string $id
     * @return DefinitionInterface
     */
    public function get(string $id): DefinitionInterface;

    /**
     * Check that definition by id exists
     *
     * @param string $id
     * @return DefinitionInterface
     */
    public function has(string $id): bool;
}
