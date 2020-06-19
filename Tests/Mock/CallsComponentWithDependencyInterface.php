<?php declare(strict_types=1);

namespace Tests\Mock;

interface CallsComponentWithDependencyInterface
{
    public function setSomeValues(SimpleComponent $simpleComponent, int $intValue, string $stringValue = ''): CallsComponentWithDependency;
}
