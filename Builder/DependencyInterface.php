<?php declare(strict_types=1);

namespace Phact\Container\Builder;

interface DependencyInterface
{
    public const TYPE_VALUE = 1;
    public const TYPE_REQUIRED = 2;
    public const TYPE_OPTIONAL = 3;

    /**
     * Type of dependency, see constants
     *
     * @return int
     */
    public function getType(): int;

    /**
     * Dependency name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Dependency value
     *
     * @return mixed
     */
    public function getValue();
}
