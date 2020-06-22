<?php declare(strict_types=1);

namespace Tests;

use Phact\Container\Definition\Definition;
use Phact\Container\Definition\DefinitionAggregate;
use Phact\Container\Exceptions\DuplicateNameException;
use Phact\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Tests\Mock\AbstractComponent;
use Tests\Mock\ChildComponent;
use Tests\Mock\ChildComponentWithSimpleInterface;
use Tests\Mock\SimpleComponent;
use Tests\Mock\SimpleInterface;

class DefinitionAggregateTest extends TestCase
{
    public function testSuccessAddDefinition(): void
    {
        $aggregate = new DefinitionAggregate();
        $definition = new Definition(SimpleComponent::class);
        $aggregate->addDefinition('simple', $definition);

        $this->assertTrue($aggregate->has('simple'));
    }

    public function testExceptionOnDuplicateDefinitonName(): void
    {
        $this->expectException(DuplicateNameException::class);

        $aggregate = new DefinitionAggregate();
        $definition = new Definition(SimpleComponent::class);
        $anotherDefinition = new Definition(ChildComponent::class);

        $aggregate->addDefinition('simple', $definition);
        $aggregate->addDefinition('simple', $anotherDefinition);
    }

    public function testCorrectlyHandlesReferencesOfClass(): void
    {
        $aggregate = new DefinitionAggregate();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $aggregate->addDefinition('referenced', $definition);

        $this->assertTrue($aggregate->has(SimpleInterface::class));
        $this->assertTrue($aggregate->has(AbstractComponent::class));
    }

    public function testHasByReferencesWithDisabledAnalyzeReferencesReturnFalse(): void
    {
        $definitionAggregate = new DefinitionAggregate();
        $definitionAggregate->setAnalyzeReferences(false);

        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $definitionAggregate->addDefinition('referenced', $definition);

        $this->assertFalse($definitionAggregate->has(SimpleInterface::class));
    }

    public function testCorrectlyHandlesAliases(): void
    {
        $definitionAggregate = new DefinitionAggregate();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $definition->addAlias('some_alias');
        $definition->addAliases(['another_alias']);
        $definitionAggregate->addDefinition('referenced', $definition);

        $this->assertTrue($definitionAggregate->has('some_alias'));
        $this->assertTrue($definitionAggregate->has('another_alias'));
    }

    public function testCorrectlyResolvesDefinitionNameByAlias(): void
    {
        $definitionAggregate = new DefinitionAggregate();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $definition->addAlias('some_alias');
        $definition->addAliases(['another_alias']);
        $definitionAggregate->addDefinition('referenced', $definition);

        $this->assertEquals('referenced', $definitionAggregate->resolveDefinitionName('some_alias'));
        $this->assertEquals('referenced', $definitionAggregate->resolveDefinitionName('another_alias'));
    }

    public function testCorrectlyResolvesDefinitionNameByReferences(): void
    {
        $definitionAggregate = new DefinitionAggregate();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $definitionAggregate->addDefinition('referenced', $definition);

        $this->assertEquals('referenced', $definitionAggregate->resolveDefinitionName(ChildComponentWithSimpleInterface::class));
        $this->assertEquals('referenced', $definitionAggregate->resolveDefinitionName(AbstractComponent::class));
    }

    public function testCorrectlyGetDefinitionNameByAlias(): void
    {
        $definitionAggregate = new DefinitionAggregate();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $definition->addAlias('some_alias');
        $definition->addAliases(['another_alias']);
        $definitionAggregate->addDefinition('referenced', $definition);

        $this->assertSame($definition, $definitionAggregate->get('some_alias'));
        $this->assertSame($definition, $definitionAggregate->get('another_alias'));
    }

    public function testCorrectlyGetDefinitionNameByReferences(): void
    {
        $definitionAggregate = new DefinitionAggregate();
        $definition = new Definition(ChildComponentWithSimpleInterface::class);
        $definitionAggregate->addDefinition('referenced', $definition);

        $this->assertSame($definition, $definitionAggregate->get(ChildComponentWithSimpleInterface::class));
        $this->assertSame($definition, $definitionAggregate->get(AbstractComponent::class));
    }

    public function testExceptionOnResolveDefinitionNameUnknownDefinitionName(): void
    {
        $this->expectException(NotFoundException::class);

        $definitionAggregate = new DefinitionAggregate();

        $definitionAggregate->resolveDefinitionName(ChildComponentWithSimpleInterface::class);
    }

    public function testExceptionOnGetUnknownDefinitionName(): void
    {
        $this->expectException(NotFoundException::class);

        $definitionAggregate = new DefinitionAggregate();

        $definitionAggregate->get(ChildComponentWithSimpleInterface::class);
    }
}
