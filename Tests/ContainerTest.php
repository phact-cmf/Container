<?php declare(strict_types=1);

namespace Tests;

use Phact\Container\Exceptions\NotFoundException;
use Tests\Mock\DependsSimpleComponent;
use Phact\Container\Builder\BuilderInterface;
use Phact\Container\Exceptions\CircularException;
use Phact\Container\Inflection\InflectionInterface;
use Tests\Mock\CircularComponentA;
use Tests\Mock\CircularComponentB;
use Tests\Mock\SimpleInterface;
use Phact\Container\Exceptions\DuplicateNameException;
use Tests\Mock\AbstractComponent;
use Tests\Mock\ChildComponent;
use Tests\Mock\ChildComponentWithSimpleInterface;
use Tests\Mock\SimpleComponent;
use Phact\Container\Container;
use Phact\Container\Definition\Definition;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testSuccessAddDefinitionByClass(): void
    {
        $container = new Container();
        $container->addDefinitionClass('simple', SimpleComponent::class);

        $this->assertTrue($container->has('simple'));
    }

    public function testSuccessAddDefinition(): void
    {
        $container = new Container();
        $definition = new Definition(SimpleComponent::class);
        $container->addDefinition('simple', $definition);

        $this->assertTrue($container->has('simple'));
    }

    public function testExceptionOnDuplicateDefinitonName(): void
    {
        $this->expectException(DuplicateNameException::class);

        $container = new Container();
        $definition = new Definition(SimpleComponent::class);
        $anotherDefinition = new Definition(ChildComponent::class);

        $container->addDefinition('simple', $definition);
        $container->addDefinition('simple', $anotherDefinition);
    }

    public function testCorrectlyHandlesReferencesOfClass(): void
    {
        $container = new Container();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $container->addDefinition('referenced', $definition);

        $this->assertTrue($container->has(SimpleInterface::class));
        $this->assertTrue($container->has(AbstractComponent::class));
    }

    public function testCorrectlyHandlesAliases(): void
    {
        $container = new Container();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $definition->addAlias('some_alias');
        $container->addDefinition('referenced', $definition);

        $this->assertTrue($container->has('some_alias'));
    }

    public function testExceptionOnCircularDependency(): void
    {
        $this->expectException(CircularException::class);

        $container = new Container();
        $container->addDefinition('circularA', new Definition(CircularComponentA::class));
        $container->addDefinition('circularB', new Definition(CircularComponentB::class));

        $container->get('circularA');
    }

    public function testSharedDefinitionSameObject(): void
    {
        $container = new Container();
        $container->addDefinition('simple', (new Definition(SimpleComponent::class))->setShared());

        $simpleObjectFirst = $container->get('simple');

        $this->assertTrue($container->has('simple'));

        $simpleObjectSecond = $container->get('simple');

        $this->assertSame($simpleObjectFirst, $simpleObjectSecond);
    }

    public function testInflectsIfCanBeInflects(): void
    {
        $builder = $this->createMock(BuilderInterface::class);
        $builder
            ->expects($this->once())
            ->method('construct')
            ->willReturn(new SimpleComponent());

        $builder
            ->expects($this->once())
            ->method('configure')
            ->willReturnArgument(0);

        $builder
            ->expects($this->once())
            ->method('inflect')
            ->willReturnArgument(0);

        $container = new Container($builder);

        $container->addDefinition('simple', new Definition(SimpleComponent::class));

        $inflection = $this->createMock(InflectionInterface::class);
        $inflection
            ->expects($this->once())
            ->method('canBeAppliedTo')
            ->with($this->isInstanceOf(SimpleComponent::class))
            ->willReturn(true);

        $inflection
            ->method('getCalls')
            ->willReturn([]);

        $inflection
            ->method('getProperties')
            ->willReturn([]);

        $container->addInflection($inflection);

        $container->get('simple');
    }

    public function testNoInflectsIfCannotBeInflects(): void
    {
        $builder = $this->createMock(BuilderInterface::class);
        $builder
            ->expects($this->once())
            ->method('construct')
            ->willReturn(new SimpleComponent());

        $builder
            ->expects($this->once())
            ->method('configure')
            ->willReturnArgument(0);

        $builder
            ->expects($this->never())
            ->method('inflect')
            ->willReturnArgument(0);

        $container = new Container($builder);

        $container->addDefinition('simple', new Definition(SimpleComponent::class));

        $inflection = $this->createMock(InflectionInterface::class);
        $inflection
            ->expects($this->once())
            ->method('canBeAppliedTo')
            ->with($this->isInstanceOf(SimpleComponent::class))
            ->willReturn(false);

        $container->addInflection($inflection);

        $container->get('simple');
    }

    public function testGetByName(): void
    {
        $container = new Container();
        $container->addDefinition('simple', new Definition(SimpleComponent::class));

        $this->assertInstanceOf(SimpleComponent::class, $container->get('simple'));
    }

    public function testGetByAlias(): void
    {
        $container = new Container();
        $container->addDefinition('simple', (new Definition(SimpleComponent::class))->addAlias('some_alias'));

        $this->assertInstanceOf(SimpleComponent::class, $container->get('some_alias'));
    }

    public function testGetByReference(): void
    {
        $container = new Container();
        $container->addDefinition('simple', new Definition(ChildComponentWithSimpleInterface::class));

        $this->assertInstanceOf(ChildComponentWithSimpleInterface::class, $container->get(SimpleInterface::class));
    }

    public function testHasScalarIfScalarExists()
    {
        $container = new Container();
        $container->addScalar('scalar_value', new SimpleComponent());

        $this->assertTrue($container->has('scalar_value'));
    }

    public function testGetScalarIfScalarExists(): void
    {
        $container = new Container();
        $container->addScalar('scalar_value', new SimpleComponent());

        $this->assertTrue($container->has('scalar_value'));

        $simpleObjectFirst = $container->get('scalar_value');
        $simpleObjectSecond = $container->get('scalar_value');

        $this->assertSame($simpleObjectFirst, $simpleObjectSecond);
    }

    public function testAutowireCreateObjectWithDependenciesInContainer(): void
    {
        $container = new Container();
        $container->addDefinition('simple', (new Definition(SimpleComponent::class))->setShared());

        /** @var DependsSimpleComponent $dependsComponent */
        $dependsComponent = $container->get(DependsSimpleComponent::class);

        $this->assertInstanceOf(DependsSimpleComponent::class, $dependsComponent);
        $this->assertSame($dependsComponent->getSimpleComponent(), $container->get('simple'));
    }

    public function testAutowireCreateObjectWithDependenciesNotInContainer(): void
    {
        $container = new Container();

        /** @var DependsSimpleComponent $dependsComponent */
        $dependsComponent = $container->get(DependsSimpleComponent::class);

        $this->assertInstanceOf(DependsSimpleComponent::class, $dependsComponent);
        $this->assertInstanceOf(SimpleComponent::class, $dependsComponent->getSimpleComponent());
    }

    public function testProxyInvokeToBuilder(): void
    {
        $simpleComponent = new SimpleComponent();
        $object = new DependsSimpleComponent($simpleComponent);

        $callable = [$object, 'getSimpleComponent'];
        $arguments = ['testArgument'];


        $builder = $this->createMock(BuilderInterface::class);
        $builder
            ->expects($this->once())
            ->method('invoke')
            ->with($callable, $arguments);

        $container = new Container($builder);

        $container->invoke($callable, $arguments);
    }

    public function testHasByClassNotDefinedWithDisabledAutowireReturnFalse(): void
    {
        $container = new Container();
        $container->setAutoWire(false);

        $this->assertFalse($container->has(SimpleComponent::class));
    }

    public function testHasByReferencesWithDisabledAnalyzeReferencesReturnFalse(): void
    {
        $container = new Container();
        $container->setAnalyzeReferences(false);

        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $container->addDefinition('referenced', $definition);

        $this->assertFalse($container->has(SimpleInterface::class));
    }

    public function testExceptionOnGettingUnknownObject(): void
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->get('unknown');
    }

    public function testFalseOnHasUnknownObject(): void
    {
        $container = new Container();
        $this->assertFalse($container->has('unknown'));
    }
}
