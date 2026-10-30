<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Exception\StructureNotInChainException;
use Serafim\Vulkan\Struct\ChainSet;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(ChainSet::class)]
final class ChainSetTest extends TestCase
{
    public function testStructureIsFoundByItsClass(): void
    {
        $object = new \ArrayObject();
        $chain = new ChainSet([new \stdClass(), $object]);

        self::assertSame($object, $chain->find(\ArrayObject::class));
        self::assertTrue($chain->has(\ArrayObject::class));
        self::assertNull($chain->find(\SplQueue::class));
        self::assertFalse($chain->has(\SplQueue::class));
    }

    public function testStructureIsFoundByTheClassItExtends(): void
    {
        $queue = new \SplQueue();
        $chain = new ChainSet([$queue]);

        self::assertSame($queue, $chain->find(\SplDoublyLinkedList::class));
        self::assertSame($queue, $chain[\SplDoublyLinkedList::class]);
    }

    public function testMissingStructureIsAnErrorForGet(): void
    {
        $chain = new ChainSet([new \stdClass()]);

        $this->expectException(StructureNotInChainException::class);

        $chain->get(\ArrayObject::class);
    }

    public function testMissingStructureIsAnErrorForArrayAccess(): void
    {
        $chain = new ChainSet();

        $this->expectException(StructureNotInChainException::class);

        $chain[\ArrayObject::class];
    }

    public function testChainIsCountableAndTraversableInTheOrderOfTheChain(): void
    {
        $first = new \stdClass();
        $second = new \ArrayObject();
        $chain = new ChainSet([$first, $second]);

        self::assertCount(2, $chain);
        self::assertSame([$first, $second], [...$chain]);
        self::assertTrue(isset($chain[\ArrayObject::class]));
        self::assertFalse(isset($chain[\SplQueue::class]));
    }

    public function testChainCannotBeChanged(): void
    {
        $chain = new ChainSet();

        try {
            $chain[\stdClass::class] = new \stdClass();
            self::fail('The chain accepted a structure');
        } catch (\BadMethodCallException) {
            self::assertCount(0, $chain);
        }

        $this->expectException(\BadMethodCallException::class);

        unset($chain[\stdClass::class]);
    }
}
