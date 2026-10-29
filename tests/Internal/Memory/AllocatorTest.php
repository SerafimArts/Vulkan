<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Internal\Memory;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Internal\Memory\Allocator;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(Allocator::class)]
final class AllocatorTest extends TestCase
{
    public function testValuePointerArrayPointsToTheGivenValues(): void
    {
        $memory = self::loadVulkan()->memory;

        $float = $memory->new('float');
        $int = $memory->new('int');

        $float->cdata = 1.5;
        $int->cdata = 7;

        $pointers = $memory->toValuePointerArray([$float, $int]);

        self::assertSame(1.5, $memory->ffi->cast('float*', $pointers[0])[0]);
        self::assertSame(7, $memory->ffi->cast('int*', $pointers[1])[0]);
    }
}
