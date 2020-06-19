<?php declare(strict_types=1);

namespace Tests\Mock;

class CallsComponentWithDependency implements CallsComponentWithDependencyInterface
{
    /**
     * @var int
     */
    private $intValue;

    /**
     * @var string
     */
    private $stringValue;
    /**
     * @var SimpleComponent
     */
    private $simpleComponent;

    public function setSomeValues(SimpleComponent $simpleComponent, int $intValue, string $stringValue = ''): CallsComponentWithDependency
    {
        $this->intValue = $intValue;
        $this->stringValue = $stringValue;
        $this->simpleComponent = $simpleComponent;
        return $this;
    }

    /**
     * @return int
     */
    public function getIntValue(): int
    {
        return $this->intValue;
    }

    /**
     * @return string
     */
    public function getStringValue(): string
    {
        return $this->stringValue;
    }

    /**
     * @return SimpleComponent
     */
    public function getSimpleComponent(): SimpleComponent
    {
        return $this->simpleComponent;
    }
}
