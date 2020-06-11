<?php declare(strict_types=1);

namespace Phact\Container\Details;

trait HasPropertiesTrait
{
    /**
     * @var Property[]
     */
    protected $properties = [];


    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function addProperty(string $name, $value): self
    {
        $this->properties[] = new Property($name, $value);
        return $this;
    }

    /**
     * @return self
     */
    public function removeProperties(): self
    {
        $this->properties = [];
        return $this;
    }
}
