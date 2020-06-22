<?php declare(strict_types=1);

namespace Phact\Container;

use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Exceptions\DuplicateNameException;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends InvokableContainerInterface
{
    /**
     * Add definition by class name
     *
     * @param string $name
     * @param string $class
     * @return DefinitionInterface
     * @throws DuplicateNameException If service with provided name already defined
     */
    public function addDefinitionClass(string $name, string $class): DefinitionInterface;

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
     * Add scalar value to container
     *
     * @param string $name
     * @param $value
     */
    public function addScalar(string $name, $value): void;

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
     * Add inflection to container
     *
     * @param InflectionInterface $inflection
     * @return InflectionInterface
     */
    public function addInflection(InflectionInterface $inflection): InflectionInterface;

    /**
     * Append sub-container to retrieve services if it cannot be resolved with current container
     *
     * @param PsrContainerInterface $container
     * @param bool $applyInflection Apply inflection of current container to all services from delegate container
     * @return mixed
     */
    public function addDelegate(PsrContainerInterface $container, bool $applyInflection = true): void;
}