<?php declare(strict_types=1);

namespace Phact\Container\Builder;

class Dependency
{
    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $optional;

    /**
     * @var mixed
     */
    private $value;

    public function __construct(
        int $type,
        string $name,
        bool $optional,
        $value = null
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->optional = $optional;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
