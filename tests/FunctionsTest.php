<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Serafim\Vulkan\Struct\VkBufferCopy;

use function Serafim\Vulkan\vk_api_version_major;
use function Serafim\Vulkan\vk_api_version_minor;
use function Serafim\Vulkan\vk_api_version_patch;
use function Serafim\Vulkan\vk_api_version_variant;
use function Serafim\Vulkan\vk_make_api_version;
use function Serafim\Vulkan\vk_uint64_compare;
use function Serafim\Vulkan\vk_uint64_from_string;
use function Serafim\Vulkan\vk_uint64_to_string;

use const Serafim\Vulkan\VK_API_VERSION_1_3;
use const Serafim\Vulkan\VK_HEADER_VERSION;
use const Serafim\Vulkan\VK_HEADER_VERSION_COMPLETE;
use const Serafim\Vulkan\VK_KHR_SURFACE_EXTENSION_NAME;
use const Serafim\Vulkan\VK_WHOLE_SIZE;

#[CoversNothing]
final class FunctionsTest extends TestCase
{
    public function testVersionIsComposedAndSplit(): void
    {
        $version = vk_make_api_version(1, 2, 3, 4);

        self::assertSame(1, vk_api_version_variant($version));
        self::assertSame(2, vk_api_version_major($version));
        self::assertSame(3, vk_api_version_minor($version));
        self::assertSame(4, vk_api_version_patch($version));
    }

    public function testVersionConstantsFollowTheMacro(): void
    {
        self::assertSame(vk_make_api_version(0, 1, 3, 0), VK_API_VERSION_1_3);
        self::assertSame(vk_make_api_version(0, 1, 4, VK_HEADER_VERSION), VK_HEADER_VERSION_COMPLETE);
    }

    public function testExtensionNameIsDefined(): void
    {
        self::assertSame('VK_KHR_surface', VK_KHR_SURFACE_EXTENSION_NAME);
    }

    /**
     * @return iterable<non-empty-string, array{int, numeric-string}>
     */
    public static function uint64DataProvider(): iterable
    {
        yield 'zero' => [0, '0'];
        yield 'one' => [1, '1'];
        yield 'max signed' => [\PHP_INT_MAX, '9223372036854775807'];
        yield 'min signed' => [\PHP_INT_MIN, '9223372036854775808'];
        yield 'above max signed' => [\PHP_INT_MIN + 2, '9223372036854775810'];
        yield 'random' => [-6101065172474983726, '12345678901234567890'];
        yield 'max unsigned' => [-1, '18446744073709551615'];
    }

    /**
     * @param numeric-string $string
     */
    #[DataProvider('uint64DataProvider')]
    public function testUint64IsConvertedToAndFromDecimalString(int $value, string $string): void
    {
        self::assertSame($string, vk_uint64_to_string($value));
        self::assertSame($value, vk_uint64_from_string($string));
    }

    #[DataProvider('uint64DataProvider')]
    public function testUint64SurvivesNativeMemory(int $value, string $string): void
    {
        $memory = self::loadVulkan()->memory;

        $data = new VkBufferCopy(size: $value)->toCData($memory);

        self::assertSame($value, $data->size);
    }

    public function testWholeSizeIsMaxUnsigned(): void
    {
        self::assertSame('18446744073709551615', vk_uint64_to_string(VK_WHOLE_SIZE));
    }

    /**
     * @return iterable<non-empty-string, array{string}>
     */
    public static function invalidUint64DataProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'negative' => ['-1'];
        yield 'fraction' => ['1.5'];
        yield 'letters' => ['abc'];
        yield 'too long' => ['100000000000000000000'];
        yield 'above max unsigned' => ['18446744073709551616'];
    }

    #[DataProvider('invalidUint64DataProvider')]
    public function testInvalidUint64StringIsRejected(string $value): void
    {
        $this->expectException(\ValueError::class);

        vk_uint64_from_string($value);
    }

    public function testUint64IsComparedAsUnsigned(): void
    {
        self::assertSame(1, vk_uint64_compare(-1, 1));
        self::assertSame(-1, vk_uint64_compare(1, -1));
        self::assertSame(-1, vk_uint64_compare(\PHP_INT_MAX, \PHP_INT_MIN));
        self::assertSame(0, vk_uint64_compare(-1, -1));
    }
}
