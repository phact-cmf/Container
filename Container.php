<?php declare(strict_types=1);

namespace Phact\Container;

use Closure;
use Phact\Container\Definition\Definition;
use Phact\Container\Definition\DefinitionInterface;
use Phact\Container\Exceptions\DuplicateNameException;
use Phact\Container\Exceptions\NotFoundException;
use Phact\Container\Inflection\InflectionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $scalars = [];

    /**
     * @var Definition[]
     */
    protected $definitions = [];

    /**
     * @var array
     */
    protected $references = [];

    /**
     * Resolved shared instances
     *
     * @var array
     */
    protected $shared = [];

    /**
     * @var InflectionInterface[]
     */
    protected $inflections = [];

    /**
     * @var bool
     */
    protected $autoWire = true;

    /**
     * @var bool
     */
    protected $analyzeReferences = true;


    public function addDefinitionClass(string $name, string $class): DefinitionInterface
    {
        return $this->addDefinition($name, new Definition($class));
    }

    public function addDefinition(string $name, DefinitionInterface $definition): DefinitionInterface
    {
        if (isset($this->definitions[$name])) {
            throw new DuplicateNameException("Definition with name {$name} already exists.");
        }
        $this->definitions[$name] = $definition;
        if ($this->analyzeReferences) {
            $this->analyzeReferences($name, $definition->getClass());
        }
        return $definition;
    }

    public function addReference(string $name, string $class): void
    {
        if (!isset($this->references[$class])) {
            $this->references[$class] = [];
        }
        $this->references[$class][] = $name;
    }

    public function addInflection(InflectionInterface $inflection): InflectionInterface
    {
        $this->inflections[] = $inflection;
        return $inflection;
    }

    protected function analyzeReferences(string $name, string $class): void
    {
        $this->addReference($name, $class);
        $interfaces = class_implements($class);
        foreach ($interfaces as $interface) {
            $this->addReference($name, $interface);
        }
        $parents = class_parents($class);
        foreach ($parents as $parent) {
            $this->addReference($name, $parent);
        }
    }

    public function resolveDefinition(DefinitionInterface $definition, ?string $id = null): object
    {
        $this->inflectObject($object);
    }

    protected function inflectObject(object $object)
    {

    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (isset($this->shared[$id])) {
            return $this->shared[$id];
        }

        if (isset($this->scalars[$id])) {
            return $this->scalars[$id];
        }

        if (isset($this->definitions[$id])) {
            return $this->resolveDefinition($this->definitions[$id], $id);
        }

        if ($this->analyzeReferences && isset($this->references[$id])) {
            $id = reset($this->references[$id]);
            return $this->get($id);
        }

        if ($this->autoWire && class_exists($id)) {
            $this->resolveDefinition(
                (new Definition($id))->setShared(true)
            );
        }

        throw new NotFoundException("Could not resolve element by id - {$id}");
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {

    }

    public function invoke(callable $callable, array $arguments = [])
    {

    }
}
