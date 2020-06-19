<?php declare(strict_types=1);

namespace Tests\Mock;

class DependsSimpleConstructionMethodComponent
{
    /**
     * @var SimpleComponent
     */
    protected $simpleComponent;

    public static function construction(SimpleComponent $simpleComponent): self
    {
        $instance = new self;
        $instance->simpleComponent = $simpleComponent;
        return $instance;
    }

    /**
     * @return SimpleComponent
     */
    public function getSimpleComponent(): SimpleComponent
    {
        return $this->simpleComponent;
    }
}
