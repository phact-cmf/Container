<?php declare(strict_types=1);

namespace Phact\Container\Definition;

use Phact\Container\Details\HasCallsTrait;
use Phact\Container\Details\HasPropertiesTrait;

class Definition implements DefinitionInterface
{
    use HasPropertiesTrait;
    use HasCallsTrait;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var string[]
     */
    protected $aliases = [];

    /**
     * @var string|null
     */
    protected $constructMethod;

    /**
     * @var string|callable|null|array
     */
    protected $factory;

    /**
     * @var bool
     */
    protected $shared = true;

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

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     * @return Definition
     */
    public function addArguments(array $arguments): Definition
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param string[] $aliases
     * @return self
     */
    public function addAliases(array $aliases): self
    {
        foreach ($aliases as $alias) {
            $this->addAlias($alias);
        }
        return $this;
    }
    /**
     * @param string $alias
     * @return self
     */
    public function addAlias(string $alias): self
    {
        $this->aliases[] = $alias;
        return $this;
    }

    /**
     * @return self
     */
    public function removeAliases(): self
    {
        $this->aliases = [];
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConstructMethod(): ?string
    {
        return $this->constructMethod;
    }

    /**
     * @param string|null $constructMethod
     */
    public function setConstructMethod(?string $constructMethod): void
    {
        $this->constructMethod = $constructMethod;
    }

    /**
     * @return callable|string|null|array
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param callable|string|null|array $factory
     */
    public function setFactory($factory): void
    {
        $this->factory = $factory;
    }

    /**
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * @param bool $shared
     * @return Definition
     */
    public function setShared(bool $shared = true): self
    {
        $this->shared = $shared;
        return $this;
    }
}
