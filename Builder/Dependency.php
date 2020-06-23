<?php declare(strict_types=1);

namespace Phact\Container\Builder;

class Dependency implements DependencyInterface
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
     * @var mixed
     */
    private $value;

    public function __construct(
        int $type,
        string $name,
        $value = null
    ) {
        $this->type = $type;
        $this->name = $name;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }
}
