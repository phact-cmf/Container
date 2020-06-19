<?php declare(strict_types=1);

namespace Tests\Mock;

class ConstructionMethodComponent
{
    public static function construction(): self
    {
        return new self;
    }
}
