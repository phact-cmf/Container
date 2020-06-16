<?php declare(strict_types=1);

namespace Phact\Container\Builder;

use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface;

interface BuilderInterface
{
    public function construct(DefinitionInterface $definition): object;

    public function configure(object $object, DefinitionInterface $definition): object;

    public function invoke(callable $callable, array $arguments = []);

    public function inflect(object $object, InflectionInterface $inflection): object;

    public function setContainer(ContainerInterface $container): void;
}