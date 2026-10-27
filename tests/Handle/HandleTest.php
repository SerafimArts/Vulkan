<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Handle;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(Handle::class)]
final class HandleTest extends TestCase
{
    public function testHandlesAliveAtTheEndOfTheProcessAreDestroyedWithTheChildrenFirst(): void
    {
        self::loadVulkan();

        $output = [];
        $code = 0;

        \exec(\escapeshellarg(\PHP_BINARY) . ' ' . \escapeshellarg(__DIR__ . '/shutdown.php') . ' 2>&1', $output, $code);

        if ($code === 3) {
            self::markTestSkipped('There are no physical devices');
        }

        self::assertSame([], $output);
        self::assertSame(0, $code);
    }
}
