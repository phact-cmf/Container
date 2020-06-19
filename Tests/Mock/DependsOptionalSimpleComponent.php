<?php declare(strict_types=1);

namespace Tests\Mock;

class DependsOptionalSimpleComponent
{
    /**
     * @var SimpleComponent|null
     */
    private $simpleComponent;

    public function __construct(SimpleComponent $simpleComponent = null)
    {
        $this->simpleComponent = $simpleComponent;
    }

    /**
     * @return SimpleComponent
     */
    public function getSimpleComponent(): ?SimpleComponent
    {
        return $this->simpleComponent;
    }
}
