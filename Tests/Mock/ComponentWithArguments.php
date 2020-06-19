<?php declare(strict_types=1);

namespace Tests\Mock;

class ComponentWithArguments
{
    /**
     * @var int
     */
    private $intArgument;
    /**
     * @var string
     */
    private $stringArgument;

    public function __construct(int $intArgument, string $stringArgument = 'Defaults')
    {
        $this->intArgument = $intArgument;
        $this->stringArgument = $stringArgument;
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
