<?php declare(strict_types=1);

namespace Tests\Mock;

class CircularComponentA
{
    public function __construct(CircularComponentB $componentB)
    {
    }
}
