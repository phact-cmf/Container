<?php declare(strict_types=1);

namespace Phact\Container\Builder;

class Parameter implements ParameterInterface
{
    /**
     * @var int
     */
    private $type;

    private $value;

    public function __construct(int $type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }
}
