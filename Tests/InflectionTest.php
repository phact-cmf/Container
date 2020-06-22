<?php declare(strict_types=1);

namespace Tests;

use Phact\Container\Inflection\Inflection;
use PHPUnit\Framework\TestCase;
use Tests\Mock\CallsComponentWithDependency;
use Tests\Mock\CallsComponentWithDependencyInterface;
use Tests\Mock\SimpleComponent;

class InflectionTest extends TestCase
{
    public function testReturnCorrectClassAfterConstruct(): void
    {
        $inflection = new Inflection(CallsComponentWithDependencyInterface::class);
        $this->assertEquals(CallsComponentWithDependencyInterface::class, $inflection->getClass());
    }

    public function testCanBeAppliedToCorrectObject(): void
    {
        $inflection = new Inflection(CallsComponentWithDependencyInterface::class);
        $component = new CallsComponentWithDependency();

        $this->assertTrue($inflection->canBeAppliedTo($component));
    }

    public function testCannotBeAppliedToIncorrectObject(): void
    {
        $inflection = new Inflection(CallsComponentWithDependencyInterface::class);
        $component = new SimpleComponent();

        $this->assertFalse($inflection->canBeAppliedTo($component));
    }
}
