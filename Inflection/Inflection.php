<?php declare(strict_types=1);

namespace Phact\Container\Inflection;

use Phact\Container\Details\HasCallsTrait;
use Phact\Container\Details\HasPropertiesTrait;

class Inflection implements InflectionInterface
{
    use HasPropertiesTrait;
    use HasCallsTrait;

    /**
     * @var string
     */
    protected $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}
