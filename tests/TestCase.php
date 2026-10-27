<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Vulkan;

abstract class TestCase extends BaseTestCase
{
    /**
     * Loads the library, a test is skipped if it is not installed.
     */
    protected static function loadVulkan(): Vulkan
    {
        try {
            return Vulkan::create();
        } catch (InvalidLibraryException) {
            self::markTestSkipped('The Vulkan library is not installed');
        }
    }
}
