<?php declare(strict_types=1);

namespace Tests\Mock;

class DependsSimpleConstructionMethodWithArgumentsComponent
{
    /**
     * @var SimpleComponent
     */
    protected $simpleComponent;

    /**
     * @var int
     */
    protected $intArgument;

    /**
     * @var string
     */
    protected $stringArgument;

    public static function construction(SimpleComponent $simpleComponent, int $intArgument, string $stringArgument = 'Defaults'): self
    {
        $instance = new self;
        $instance->simpleComponent = $simpleComponent;
        $instance->intArgument = $intArgument;
        $instance->stringArgument = $stringArgument;
        return $instance;
    }

    /**
     * @return SimpleComponent
     */
    public function getSimpleComponent(): SimpleComponent
    {
        return $this->simpleComponent;
    }

    /**
     * @return int
     */
    public function getIntArgument(): int
    {
        return $this->intArgument;
    }

    /**
     * @return string
     */
    public function getStringArgument(): string
    {
        return $this->stringArgument;
    }
}
