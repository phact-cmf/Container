<?php declare(strict_types=1);

namespace Tests\Mock;

class CircularComponentB
{
    public function __construct(CircularComponentA $componentA)
    {
    }
}
