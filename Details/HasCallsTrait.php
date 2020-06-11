<?php declare(strict_types=1);

namespace Phact\Container\Details;

trait HasCallsTrait
{
    /**
     * @var Call[]
     */
    protected $calls = [];

    /**
     * @return Call[]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return self
     */
    public function addCall(string $name, array $arguments): self
    {
        $this->calls[] = new Call($name, $arguments);
        return $this;
    }

    /**
     * @return self
     */
    public function removeCalls(): self
    {
        $this->calls = [];
        return $this;
    }
}
