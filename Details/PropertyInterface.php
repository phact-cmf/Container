<?php declare(strict_types=1);

namespace Phact\Container\Details;

interface PropertyInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return array
     */
    public function getValue();
}
