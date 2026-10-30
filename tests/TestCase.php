<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Struct\VkInstanceCreateInfo;
use Serafim\Vulkan\Vulkan;

abstract class TestCase extends BaseTestCase
{
    /**
     * Loads the library, a test is skipped if it is not installed.
     */
    protected static function loadVulkan(): Vulkan
    {
        try {
            $vulkan = Vulkan::load();
        } catch (InvalidLibraryException) {
            self::markTestSkipped('The Vulkan library is not installed');
        }

        try {
            $vulkan->vkCreateInstance(new VkInstanceCreateInfo());
        } catch (CommandFailedException $e) {
            if ($e->getCode() === VkResult::VK_ERROR_INCOMPATIBLE_DRIVER->value) {
                self::markTestSkipped('There is no Vulkan driver');
            }

            throw $e;
        }

        return $vulkan;
    }
}
