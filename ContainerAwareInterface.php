<?php declare(strict_types=1);

namespace Phact\Container;

interface ContainerAwareInterface
{
    /**
     * Set ContainerInterface object
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void;
}
