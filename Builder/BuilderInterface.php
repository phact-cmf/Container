<?php declare(strict_types=1);

namespace Phact\Container\Builder;

use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface;

interface BuilderInterface
{
    /**
     * Construct object by definition
     *
     * @param DefinitionInterface $definition
     * @return object
     */
    public function construct(DefinitionInterface $definition): object;

    /**
     * Configure object by definition
     *
     * @param object $object
     * @param DefinitionInterface $definition
     * @return object
     */
    public function configure(object $object, DefinitionInterface $definition): object;

    /**
     * Invoke callable with arguments
     *
     * @param callable $callable
     * @param array $arguments
     * @return mixed
     */
    public function invoke(callable $callable, array $arguments = []);

    /**
     * Apply inflection to object
     *
     * @param object $object
     * @param InflectionInterface $inflection
     * @return object
     */
    public function inflect(object $object, InflectionInterface $inflection): object;

    /**
     * Set container for retrieve services
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void;
}