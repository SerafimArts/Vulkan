<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Vulkan;
use Serafim\Vulkan\VulkanBase;

#[CoversClass(VulkanBase::class)]
final class VulkanBaseTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{int, VkResult}>
     */
    public static function successCodesDataProvider(): iterable
    {
        yield 'success' => [0, VkResult::VK_SUCCESS];
        yield 'not ready' => [1, VkResult::VK_NOT_READY];
        yield 'timeout' => [2, VkResult::VK_TIMEOUT];
    }

    #[DataProvider('successCodesDataProvider')]
    public function testSuccessCodeIsReturnedAsTheCaseOfTheResult(int $code, VkResult $expected): void
    {
        self::assertSame($expected, self::loadVulkan()->getResult('vkTest', $code));
    }

    public function testErrorResultFailsTheCommandWithItsCode(): void
    {
        $vk = self::loadVulkan();

        try {
            $vk->getResult('vkTest', VkResult::VK_ERROR_DEVICE_LOST->value);

            self::fail('The command has not failed');
        } catch (CommandFailedException $e) {
            self::assertSame(VkResult::VK_ERROR_DEVICE_LOST->value, $e->getCode());
            self::assertStringContainsString('VK_ERROR_DEVICE_LOST', $e->getMessage());
        }
    }

    public function testUnknownResultFailsTheCommand(): void
    {
        $this->expectException(CommandFailedException::class);

        self::loadVulkan()->getResult('vkTest', 123456789);
    }

    public function testAnythingButSuccessFailsTheCheck(): void
    {
        $this->expectException(CommandFailedException::class);

        self::loadVulkan()->assertSuccess('vkTest', VkResult::VK_TIMEOUT->value);
    }
}
