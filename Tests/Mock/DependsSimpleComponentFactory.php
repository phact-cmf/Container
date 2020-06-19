<?php declare(strict_types=1);

namespace Tests\Mock;

class DependsSimpleComponentFactory
{
    public function construction(SimpleComponent $simpleComponent): DependsSimpleComponent
    {
        return new DependsSimpleComponent($simpleComponent);
    }
}
