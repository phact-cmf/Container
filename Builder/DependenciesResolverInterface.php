<?php declare(strict_types=1);

namespace Phact\Container\Builder;

interface DependenciesResolverInterface
{
    /**
     * Read constructor dependencies as array
     *
     * @param string $className
     * @param string|null $constructMethod
     * @return DependencyInterface[]
     */
    public function resolveConstructorDependencies(string $className, ?string $constructMethod = null): array;

    /**
     * Read callable dependencies
     *
     * @param $callable
     * @return DependencyInterface[]
     */
    public function resolveCallableDependencies(callable $callable): array;
}
