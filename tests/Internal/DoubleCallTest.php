<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Internal\DoubleCall;
use Serafim\Vulkan\Tests\TestCase;

#[CoversClass(DoubleCall::class)]
final class DoubleCallTest extends TestCase
{
    public function testCommandIsCalledAgainWhileMemoryIsAllocated(): void
    {
        $vk = self::loadVulkan();
        $calls = 0;
        $allocations = 2;

        DoubleCall::run(
            $vk,
            'vkTest',
            static function () use (&$calls): int {
                ++$calls;

                return VkResult::VK_SUCCESS->value;
            },
            static function () use (&$allocations): bool {
                if ($allocations === 0) {
                    return false;
                }

                --$allocations;

                return true;
            },
        );

        self::assertSame(3, $calls);
    }

    public function testIncompleteResultRepeatsTheCommandFromTheBeginning(): void
    {
        $vk = self::loadVulkan();
        $calls = 0;
        $resets = 0;

        DoubleCall::run(
            $vk,
            'vkTest',
            static function () use (&$calls): int {
                ++$calls;

                if ($calls === 1) {
                    return VkResult::VK_INCOMPLETE->value;
                }

                return VkResult::VK_SUCCESS->value;
            },
            static fn(): bool => false,
            static function () use (&$resets): void {
                ++$resets;
            },
        );

        self::assertSame(2, $calls);
        self::assertSame(1, $resets);
    }

    public function testFailedCallStopsBeforeAllocation(): void
    {
        $vk = self::loadVulkan();
        $isAllocated = false;

        try {
            DoubleCall::run(
                $vk,
                'vkTest',
                static fn(): int => VkResult::VK_ERROR_OUT_OF_HOST_MEMORY->value,
                static function () use (&$isAllocated): bool {
                    $isAllocated = true;

                    return false;
                },
            );

            self::fail('The failed command has to throw');
        } catch (CommandFailedException) {
            self::assertFalse($isAllocated);
        }
    }

    public function testCommandThatReturnsNothingIsSuccessful(): void
    {
        $vk = self::loadVulkan();
        $calls = 0;

        DoubleCall::run(
            $vk,
            'vkTest',
            static function () use (&$calls): null {
                ++$calls;

                return null;
            },
            static fn(): bool => false,
        );

        self::assertSame(1, $calls);
    }
}
