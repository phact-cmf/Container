<?php declare(strict_types=1);

namespace Phact\Container;

interface InvokableContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * Invoke callable with provided arguments
     *
     * @param callable $callable
     * @param array $arguments
     * @return mixed
     */
    public function invoke(callable $callable, array $arguments = []);
}
