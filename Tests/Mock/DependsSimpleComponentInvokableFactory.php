<?php declare(strict_types=1);

namespace Tests\Mock;


class DependsSimpleComponentInvokableFactory
{
    public function __invoke(SimpleComponent $simpleComponent)
    {
        return new DependsSimpleComponent($simpleComponent);
    }
}
