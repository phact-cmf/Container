<?php declare(strict_types=1);

namespace Tests;

use Phact\Container\Exceptions\InvalidConfigurationException;
use Phact\Container\Exceptions\InvalidFactoryException;
use Phact\Container\Exceptions\NotFoundException;
use Phact\Container\Inflection\Inflection;
use Tests\Mock\CallsComponent;
use Tests\Mock\CallsComponentWithDependency;
use Tests\Mock\CallsComponentWithDependencyInterface;
use Tests\Mock\ComponentWithArguments;
use Tests\Mock\ComponentWithVariadicArguments;
use Tests\Mock\ConstructionMethodComponent;
use Phact\Container\Builder\Builder;
use Phact\Container\ContainerInterface;
use Phact\Container\Definition\Definition;
use PHPUnit\Framework\TestCase;
use Tests\Mock\DependsOptionalSimpleComponent;
use Tests\Mock\DependsSimpleComponent;
use Tests\Mock\DependsSimpleComponentFactory;
use Tests\Mock\DependsSimpleComponentInvokableFactory;
use Tests\Mock\DependsSimpleComponentWithArguments;
use Tests\Mock\DependsSimpleConstructionMethodComponent;
use Tests\Mock\DependsSimpleConstructionMethodWithArgumentsComponent;
use Tests\Mock\PropertiesComponent;
use Tests\Mock\SimpleComponent;

class BuilderTest extends TestCase
{
    public function testCorrectlyCreatesObjectBySimpleDefinition(): void
    {
        $builder = new Builder();
        $object = $builder->construct(new Definition(SimpleComponent::class));
        $this->assertInstanceOf(SimpleComponent::class, $object);
    }

    public function testCorrectlyCreatesObjectByDefinitionWithDependenciesFromContainer(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct(new Definition(DependsSimpleComponent::class));

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testCorrectlyCreatesObjectByDefinitionWithDependenciesFromContainerAndArguments(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $definition = new Definition(DependsSimpleComponentWithArguments::class);
        $definition->addArguments([
            'intArgument' => 12,
            'stringArgument' => 'Some string'
        ]);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponentWithArguments::class, $object);
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('Some string', $object->getStringArgument());
    }

    public function testCorrectlyCreatesObjectByDefinitionWithDependenciesAndArgumentsWithDefaultValues(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $definition = new Definition(DependsSimpleComponentWithArguments::class);
        $definition->addArguments([
            'intArgument' => 12
        ]);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponentWithArguments::class, $object);
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('Defaults', $object->getStringArgument());
    }

    public function testCorrectlyCreatesObjectByDefinitionWithArguments(): void
    {
        $definition = new Definition(ComponentWithArguments::class);
        $definition->addArguments([
            'intArgument' => 12
        ]);
        $builder = new Builder();
        $object = $builder->construct($definition);

        $this->assertInstanceOf(ComponentWithArguments::class, $object);
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('Defaults', $object->getStringArgument());
    }

    public function testCorrectlyCreatesObjectByDefinitionWithArgumentsNotAutoWire(): void
    {
        $definition = new Definition(ComponentWithArguments::class);
        $definition->addArguments([
            12
        ]);
        $builder = new Builder(false);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(ComponentWithArguments::class, $object);
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('Defaults', $object->getStringArgument());
    }

    public function testCorrectlyCreatesObjectByDefinitionWithVariadicArguments(): void
    {
        $definition = new Definition(ComponentWithVariadicArguments::class);
        $definition->addArguments([
            12,
            'another',
            'second'
        ]);
        $builder = new Builder();
        $object = $builder->construct($definition);

        $this->assertInstanceOf(ComponentWithVariadicArguments::class, $object);
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals(['another', 'second'], $object->getProperties());
    }

    public function testCorrectlyCreatesByDefinitionWithConstructionMethod(): void
    {
        $definition = new Definition(ConstructionMethodComponent::class);
        $definition->setConstructMethod('construction');

        $builder = new Builder();
        $object = $builder->construct($definition);

        $this->assertInstanceOf(ConstructionMethodComponent::class, $object);
    }

    public function testCorrectlyCreatesByDefinitionWithConstructionMethodWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $definition = new Definition(DependsSimpleConstructionMethodComponent::class);
        $definition->setConstructMethod('construction');

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleConstructionMethodComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testCorrectlyCreatesByDefinitionWithConstructionMethodWithArguments(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $definition = new Definition(DependsSimpleConstructionMethodWithArgumentsComponent::class);
        $definition->setConstructMethod('construction');
        $definition->addArguments([
            'intArgument' => 12
        ]);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleConstructionMethodWithArgumentsComponent::class, $object);
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('Defaults', $object->getStringArgument());
    }

    public function testCorrectlySetConstructorArgumentsFromArrayByName(): void
    {
        $simpleObject = new SimpleComponent();

        $definition = new Definition(DependsSimpleComponentWithArguments::class);
        $definition->addArguments([
            'intArgument' => 12,
            'simpleComponent' => $simpleObject,
            'stringArgument' => 'Some string'
        ]);
        $builder = new Builder();
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponentWithArguments::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('Some string', $object->getStringArgument());
    }

    public function testCorrectlySetConstructorArgumentsFromArrayByPosition(): void
    {
        $simpleObject = new SimpleComponent();

        $definition = new Definition(DependsSimpleComponentWithArguments::class);
        $definition->addArguments([
            $simpleObject,
            12,
            'Some string'
        ]);
        $builder = new Builder();
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponentWithArguments::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('Some string', $object->getStringArgument());
    }

    public function testRetrieveOptionalComponentFromContainer(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('simple')
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with('simple')
            ->willReturn($simpleObject);

        $definition = new Definition(DependsOptionalSimpleComponent::class);
        $definition->addArguments([
            '@?simple'
        ]);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsOptionalSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testRetrieveOptionalComponentFromContainerWithAutowire(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $definition = new Definition(DependsOptionalSimpleComponent::class);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsOptionalSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testRetrieveNotExistingOptionalComponentFromContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(false);

        $definition = new Definition(DependsOptionalSimpleComponent::class);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsOptionalSimpleComponent::class, $object);
        $this->assertNull($object->getSimpleComponent());
    }

    public function testRetrieveRequiredNullableComponentFromContainer(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('simple')
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with('simple')
            ->willReturn($simpleObject);

        $definition = new Definition(DependsOptionalSimpleComponent::class);
        $definition->addArguments([
            '@simple'
        ]);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsOptionalSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testNotFoundNullableAndRequiredComponentInContainerWillThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('simple')
            ->willReturn(false);

        $definition = new Definition(DependsOptionalSimpleComponent::class);
        $definition->addArguments([
            '@simple'
        ]);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);
    }

    public function testNotFoundRequiredComponentInContainerWillThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $definition = new Definition(DependsSimpleComponent::class);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);
    }

    public function testSuccessfullyCreatesWithNotFoundNullableAndOptionalComponentInContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('simple')
            ->willReturn(false);

        $definition = new Definition(DependsOptionalSimpleComponent::class);
        $definition->addArguments([
            '@?simple'
        ]);
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);
        $this->assertInstanceOf(DependsOptionalSimpleComponent::class, $object);
        $this->assertNull($object->getSimpleComponent());
    }

    public function testConvertDoubleAtToSingleAtFromArguments(): void
    {
        $definition = new Definition(ComponentWithArguments::class);
        $definition->addArguments([
            12,
            '@@mysuperstrongpassword'
        ]);
        $builder = new Builder();
        $object = $builder->construct($definition);
        $this->assertInstanceOf(ComponentWithArguments::class, $object);
        $this->assertEquals(12, $object->getIntArgument());
        $this->assertEquals('@mysuperstrongpassword', $object->getStringArgument());
    }

    public function testSuccessfullyCreatesObjectWithCallableFactory(): void
    {
        $definition = new Definition(ComponentWithArguments::class);
        $definition->setFactory(function () {
            return new ComponentWithArguments(42, 'Wow');
        });
        $builder = new Builder();
        $object = $builder->construct($definition);
        $this->assertInstanceOf(ComponentWithArguments::class, $object);
        $this->assertEquals(42, $object->getIntArgument());
        $this->assertEquals('Wow', $object->getStringArgument());
    }

    public function testSuccessfullyCreatesObjectWithCallableFactoryWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory(function (SimpleComponent $simpleComponent) {
            return new DependsSimpleComponent($simpleComponent);
        });
        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);
        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testSuccessfullyCreatesObjectWithInvokableFactoryByNameWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();
        $factory = new DependsSimpleComponentInvokableFactory();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([
                'dependsSimpleComponentInvokableFactory'
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $container
            ->method('get')
            ->withConsecutive([
                'dependsSimpleComponentInvokableFactory'
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                $factory,
                $simpleObject
            );

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory('@dependsSimpleComponentInvokableFactory');

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testSuccessfullyCreatesObjectWithInvokableFactoryAsObjectWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $factory = new DependsSimpleComponentInvokableFactory();

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory($factory);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testSuccessfullyCreatesObjectWithInvokableFactoryByClassWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();
        $factory = new DependsSimpleComponentInvokableFactory();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([
                DependsSimpleComponentInvokableFactory::class
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $container
            ->method('get')
            ->withConsecutive([
                DependsSimpleComponentInvokableFactory::class
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                $factory,
                $simpleObject
            );

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory(DependsSimpleComponentInvokableFactory::class);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testSuccessfullyCreatesObjectWithFactoryAndConstructionMethodByNameWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();
        $factory = new DependsSimpleComponentFactory();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([
                'dependsSimpleComponentFactory'
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $container
            ->method('get')
            ->withConsecutive([
                'dependsSimpleComponentFactory'
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                $factory,
                $simpleObject
            );

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory('@dependsSimpleComponentFactory');
        $definition->setConstructMethod('construction');

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testSuccessfullyCreatesObjectWithFactoryAndConstructionMethodByClassWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();
        $factory = new DependsSimpleComponentFactory();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([
                DependsSimpleComponentFactory::class
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $container
            ->method('get')
            ->withConsecutive([
                DependsSimpleComponentFactory::class
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                $factory,
                $simpleObject
            );

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory(DependsSimpleComponentFactory::class);
        $definition->setConstructMethod('construction');

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testSuccessfullyCreatesObjectWithArrayIdMethodFactoryWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();
        $factory = new DependsSimpleComponentFactory();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([
                'dependsSimpleComponentFactory'
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $container
            ->method('get')
            ->withConsecutive([
                'dependsSimpleComponentFactory'
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                $factory,
                $simpleObject
            );

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory(['@dependsSimpleComponentFactory', 'construction']);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testSuccessfullyCreatesObjectWithArrayClassMethodFactoryWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();
        $factory = new DependsSimpleComponentFactory();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->withConsecutive([
                DependsSimpleComponentFactory::class
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $container
            ->method('get')
            ->withConsecutive([
                DependsSimpleComponentFactory::class
            ], [
                SimpleComponent::class
            ])
            ->willReturnOnConsecutiveCalls(
                $factory,
                $simpleObject
            );

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory([DependsSimpleComponentFactory::class, 'construction']);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);

        $this->assertInstanceOf(DependsSimpleComponent::class, $object);
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testExceptionOnIncorrectFactory(): void
    {
        $this->expectException(InvalidFactoryException::class);

        $container = $this->createMock(ContainerInterface::class);

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory('incorrect factory');

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);
    }

    public function testExceptionOnIncorrectTypeOfFactory(): void
    {
        $this->expectException(InvalidFactoryException::class);

        $container = $this->createMock(ContainerInterface::class);

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory(123);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->construct($definition);
    }

    public function testExceptionOnNonCallableFactoryWithoutContainer(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $definition = new Definition(DependsSimpleComponent::class);
        $definition->setFactory(['@dependsSimpleComponentFactory', 'construction']);

        $builder = new Builder();
        $object = $builder->construct($definition);
    }

    public function testConfigureCorrectlySetProperties(): void
    {
        $propertiesComponent = new PropertiesComponent();

        $definition = new Definition(PropertiesComponent::class);
        $definition->addProperty('someProperty', 'someValue');

        $builder = new Builder();
        $object = $builder->configure($propertiesComponent, $definition);

        $this->assertEquals('someValue', $object->someProperty);
    }

    public function testConfigureCorrectlyCallsSimpleMethods(): void
    {
        $callsComponent = new CallsComponent();

        $definition = new Definition(PropertiesComponent::class);
        $definition->addCall('setSomeValues', [
            'intValue' => 13
        ]);

        $builder = new Builder();
        $object = $builder->configure($callsComponent, $definition);

        $this->assertEquals(13, $object->getIntValue());
        $this->assertEquals('', $object->getStringValue());
    }

    public function testConfigureCorrectlyCallsMethodsWithDependencies(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $callsComponent = new CallsComponentWithDependency();

        $definition = new Definition(CallsComponentWithDependency::class);
        $definition->addCall('setSomeValues', [
            'intValue' => 13
        ]);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->configure($callsComponent, $definition);

        $this->assertEquals(13, $object->getIntValue());
        $this->assertEquals('', $object->getStringValue());
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testConfigureCorrectlyCallsMethodsWithDependenciesFromArguments(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('simple')
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with('simple')
            ->willReturn($simpleObject);

        $callsComponent = new CallsComponentWithDependency();

        $definition = new Definition(CallsComponentWithDependency::class);
        $definition->addCall('setSomeValues', [
            'simpleComponent' => '@simple',
            'intValue' => 13
        ]);

        $builder = new Builder();
        $builder->setContainer($container);
        $object = $builder->configure($callsComponent, $definition);

        $this->assertEquals(13, $object->getIntValue());
        $this->assertEquals('', $object->getStringValue());
        $this->assertSame($simpleObject, $object->getSimpleComponent());
    }

    public function testCorrectlyInflectsProperties(): void
    {
        $inflection = new Inflection(PropertiesComponent::class);
        $inflection->addProperty('someProperty', 'someValue');

        $propertyComponent = new PropertiesComponent();

        $builder = new Builder();
        $propertyComponent = $builder->inflect($propertyComponent, $inflection);

        $this->assertEquals('someValue', $propertyComponent->someProperty);
    }

    public function testCorrectlyInflectsCalls(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $callsComponent = new CallsComponentWithDependency();

        $definition = new Definition(CallsComponentWithDependency::class);

        $inflection = new Inflection(CallsComponentWithDependencyInterface::class);
        $inflection->addCall('setSomeValues', [
            'intValue' => 13
        ]);

        $builder = new Builder();
        $builder->setContainer($container);
        $callsComponent = $builder->inflect($callsComponent, $inflection);

        $this->assertEquals(13, $callsComponent->getIntValue());
        $this->assertEquals('', $callsComponent->getStringValue());
        $this->assertSame($simpleObject, $callsComponent->getSimpleComponent());
    }

    public function testCorrectInvokeWithDependenciesAndArguments(): void
    {
        $simpleObject = new SimpleComponent();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with(SimpleComponent::class)
            ->willReturn(true);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(SimpleComponent::class)
            ->willReturn($simpleObject);

        $callsComponent = new CallsComponentWithDependency();

        $builder = new Builder();
        $builder->setContainer($container);
        $callsComponent = $builder->invoke([$callsComponent, 'setSomeValues'], [
            'intValue' => 13
        ]);

        $this->assertInstanceOf(CallsComponentWithDependency::class, $callsComponent);
        $this->assertEquals(13, $callsComponent->getIntValue());
        $this->assertEquals('', $callsComponent->getStringValue());
        $this->assertSame($simpleObject, $callsComponent->getSimpleComponent());
    }
}
