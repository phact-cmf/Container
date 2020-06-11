<?php declare(strict_types=1);

namespace Phact\Container\Inflection;

interface InflectionInterface
{
    public function getClass(): string;

    public function getProperties(): array;

    public function getCalls(): array;
}
