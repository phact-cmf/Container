<?php declare(strict_types=1);

namespace Tests;

use Phact\Container\Definition\Definition;
use Phact\Container\Details\Call;
use Phact\Container\Details\CallInterface;
use Phact\Container\Details\PropertyInterface;
use PHPUnit\Framework\TestCase;
use Tests\Mock\DependsSimpleComponent;
use Tests\Mock\DependsSimpleComponentFactory;
use Tests\Mock\DependsSimpleComponentInvokableFactory;
use Tests\Mock\SimpleComponent;

class DefinitionTest extends TestCase
{
    public function testReturnCorrectClassAfterConstruct(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $this->assertEquals(SimpleComponent::class, $definition->getClass());
    }

    public function testReturnCorrectArgumentsAfterSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $args = [
            'first' => 'firstArgument',
            'second' => 'secondArgument'
        ];
        $definition->addArguments($args);
        $this->assertEquals($args, $definition->getArguments());
    }

    public function testReturnCorrectAliasesAfterSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $definition->addAlias('some_alias');
        $definition->addAliases(['another_alias', 'more']);
        $this->assertEquals(['some_alias', 'another_alias', 'more'], $definition->getAliases());
    }

    public function testReturnCorrectConstructMethodAfterSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $definition->setConstructMethod('myCustomConstructMethod');
        $this->assertEquals('myCustomConstructMethod', $definition->getConstructMethod());
    }

    public function testNullConstructMethodIfNotSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $this->assertNull($definition->getConstructMethod());
    }

    public function testReturnCorrectFactoryAfterSet(): void
    {
        $factory = new DependsSimpleComponentInvokableFactory();

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory($factory);

        $this->assertSame($factory, $definition->getFactory());
    }

    public function testNullFactoryIfNotSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $this->assertNull($definition->getFactory());
    }

    public function testReturnCorrectIsSharedAfterSet(): void
    {
        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setShared(false);

        $this->assertFalse($definition->isShared());

        $definition->setShared(true);
        $this->assertTrue($definition->isShared());
    }

    public function testIsSharedInNotSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $this->assertTrue($definition->isShared());
    }

    public function testReturnCorrectCallsAfterSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $definition->addCall('someMethod', [
            'argumentOne' => 'valueOne',
            'argumentTwo' => 123
        ]);
        $definition->addCall('someAnotherMethodWithoutArguments', []);

        $calls = $definition->getCalls();

        $this->assertCount(2, $calls);

        $this->assertInstanceOf(CallInterface::class, $calls[0]);
        $this->assertInstanceOf(CallInterface::class, $calls[1]);

        $this->assertEquals('someMethod', $calls[0]->getMethod());
        $this->assertEquals([
            'argumentOne' => 'valueOne',
            'argumentTwo' => 123
        ], $calls[0]->getArguments());

        $this->assertEquals('someAnotherMethodWithoutArguments', $calls[1]->getMethod());
        $this->assertEquals([], $calls[1]->getArguments());
    }

    public function testReturnEmptyCallsAfterRemove(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $definition->addCall('someMethod', [
            'argumentOne' => 'valueOne',
            'argumentTwo' => 123
        ]);
        $definition->addCall('someAnotherMethodWithoutArguments', []);
        $definition->removeCalls();

        $this->assertEmpty($definition->getCalls());
    }

    public function testReturnCorrectPropertiesAfterSet(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $definition->addProperty('someProperty', 'value1');
        $definition->addProperty('someAnotherProperty', null);

        $properties = $definition->getProperties();

        $this->assertCount(2, $properties);

        $this->assertInstanceOf(PropertyInterface::class, $properties[0]);
        $this->assertInstanceOf(PropertyInterface::class, $properties[1]);

        $this->assertEquals('someProperty', $properties[0]->getName());
        $this->assertEquals('value1', $properties[0]->getValue());

        $this->assertEquals('someAnotherProperty', $properties[1]->getName());
        $this->assertNull($properties[1]->getValue());
    }

    public function testReturnEmptyPropertiesAfterRemove(): void
    {
        $definition = new Definition(SimpleComponent::class);
        $definition->addProperty('someProperty', 'value1');
        $definition->addProperty('someAnotherProperty', null);
        $definition->removeProperties();

        $this->assertEmpty($definition->getProperties());
    }
}
