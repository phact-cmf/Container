<?php declare(strict_types=1);

namespace Tests\Mock;

class ComponentWithVariadicArguments
{
    /**
     * @var string
     */
    private $stringArgument;
    /**
     * @var array
     */
    private $properties;

    public function __construct(int $intArgument, ...$properties)
    {
        $this->intArgument = $intArgument;
        $this->properties = $properties;
    }

    /**
     * @return int
     */
    public function getIntArgument(): int
    {
        return $this->intArgument;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
