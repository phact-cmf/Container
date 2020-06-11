<?php declare(strict_types=1);

namespace Phact\Container\Details;

interface CallInterface
{
    public function getMethod(): string;

    public function getArguments(): array;
}
