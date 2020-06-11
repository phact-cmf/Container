<?php declare(strict_types=1);

namespace Phact\Container\Builder;

class Parameter
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
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
