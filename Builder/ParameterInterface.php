<?php declare(strict_types=1);

namespace Phact\Container\Builder;

/**
 * Interface ParameterInterface
 * @package Phact\Container\Builder
 */
interface ParameterInterface
{
    public const TYPE_VALUE = 1;
    public const TYPE_REFERENCE_REQUIRED = 2;
    public const TYPE_REFERENCE_OPTIONAL = 3;

    /**
     * Type of provided parameter
     *
     * @return int
     */
    public function getType(): int;

    /**
     * Value of provided parameter
     *
     * @return mixed
     */
    public function getValue();
}
