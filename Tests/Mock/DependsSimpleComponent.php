<?php declare(strict_types=1);

namespace Tests\Mock;

class DependsSimpleComponent
{
    /**
     * @var SimpleComponent
     */
    private $simpleComponent;

    public function __construct(SimpleComponent $simpleComponent)
    {
        $this->simpleComponent = $simpleComponent;
    }

    /**
     * @return SimpleComponent
     */
    public function getSimpleComponent(): SimpleComponent
    {
        return $this->simpleComponent;
    }
}
