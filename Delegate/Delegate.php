<?php declare(strict_types=1);

namespace Phact\Container\Delegate;

use Psr\Container\ContainerInterface;

class Delegate
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var bool
     */
    private $applyInflection;

    public function __construct(ContainerInterface $container, bool $applyInflection)
    {
        $this->container = $container;
        $this->applyInflection = $applyInflection;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return bool
     */
    public function isApplyInflection(): bool
    {
        return $this->applyInflection;
    }
}
