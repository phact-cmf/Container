<?php declare(strict_types=1);

namespace Tests\Mock;

class DependsSimpleComponentWithArguments
{
    /**
     * @var SimpleComponent
     */
    private $simpleComponent;
    /**
     * @var int
     */
    private $intArgument;
    /**
     * @var string
     */
    private $stringArgument;

    public function __construct(SimpleComponent $simpleComponent, int $intArgument, string $stringArgument = 'Defaults')
    {
        $this->simpleComponent = $simpleComponent;
        $this->intArgument = $intArgument;
        $this->stringArgument = $stringArgument;
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
