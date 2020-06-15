<?php declare(strict_types=1);

namespace Phact\Container;

interface ContainerAwareInterface
{
    public function setContainer(ContainerInterface $container): void;
}
