<?php declare(strict_types=1);

namespace Phact\Container\Definition;

use Phact\Container\Details\CallInterface;
use Phact\Container\Details\PropertyInterface;

interface DefinitionInterface
{
    public function getClass(): string;

    /**
     * @return PropertyInterface[]
     */
    public function getProperties(): array;

    /**
     * @return CallInterface[]
     */
    public function getCalls(): array;

    public function getArguments(): array;

    public function getAliases(): array;

    public function getConstructMethod(): ?string;

    public function getFactory();

    public function isShared(): bool;
}
