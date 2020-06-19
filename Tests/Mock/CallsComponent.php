<?php declare(strict_types=1);

namespace Tests\Mock;

class CallsComponent
{
    /**
     * @var int
     */
    private $intValue;

    /**
     * @var string
     */
    private $stringValue;

    public function setSomeValues(int $intValue, string $stringValue = ''): void
    {
        $this->intValue = $intValue;
        $this->stringValue = $stringValue;
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
}
