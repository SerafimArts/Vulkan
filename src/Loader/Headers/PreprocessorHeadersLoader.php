<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Loader\Headers;

use FFI\Preprocessor\Preprocessor;
use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Exception\InvalidRuntimeException;
use Serafim\Vulkan\Loader\Platform\Platform;

/**
 * Compiles the Vulkan headers of a platform from the raw sources. Requires
 * the `ffi/preprocessor` package.
 *
 * @see HeadersLoader
 */
final readonly class PreprocessorHeadersLoader implements HeadersLoaderInterface
{
    /**
     * Source that includes the whole Vulkan API of the version 1.0.
     */
    private const string ENTRYPOINT = <<<'C'
        #include <vulkan/vulkan.h>
        #include <prototypes-1.0.h>
        C;

    public function __construct(
        /**
         * Directory with the `vulkan`, `vk_video` and `platform` directories
         * and the `prototypes-1.0.h` file
         *
         * @var non-empty-string
         */
        private string $include = __DIR__ . '/../../../resources/include',
        /**
         * Additional replacements by the names of the macros, applied after
         * the default ones
         *
         * @var array<non-empty-string, string>
         */
        private array $defines = [],
    ) {}

    /**
     * Returns the copy of the loader with the additional `#define`.
     *
     * @api
     *
     * @param non-empty-string $name
     */
    public function withDefine(string $name, string $replacement = '1'): self
    {
        return new self($this->include, [...$this->defines, $name => $replacement]);
    }

    /**
     * @throws InvalidRuntimeException in case of the `ffi/preprocessor`
     *         package is not installed
     * @throws InvalidHeadersException in case of the headers cannot be
     *         compiled
     */
    public function getHeaders(Platform $platform): string
    {
        if (!\class_exists(Preprocessor::class)) {
            throw InvalidRuntimeException::becausePreprocessorIsNotInstalled();
        }

        try {
            $preprocessor = new Preprocessor();

            $preprocessor->macros->define('VK_USE_64_BIT_PTR_DEFINES', '1');
            $preprocessor->macros->define('VK_NO_STDDEF_H', '1');
            $preprocessor->macros->define('VK_NO_STDINT_H', '1');
            $preprocessor->macros->define('VK_NO_PROTOTYPES', '1');
            $preprocessor->macros->define('VKAPI_ATTR', '');
            $preprocessor->macros->define('VKAPI_CALL', '');
            $preprocessor->macros->define(self::getMacro($platform), '1');

            foreach ($this->defines as $name => $replacement) {
                $preprocessor->macros->define($name, $replacement);
            }

            $preprocessor->files->add('stddef.h', '');
            $preprocessor->files->add('stdint.h', '');

            $preprocessor->files->mount($this->include);
            $preprocessor->files->mount($this->include . '/vulkan');
            $preprocessor->files->mount($this->include . '/vk_video');
            $preprocessor->files->mount($this->include . '/platform');

            return (string) $preprocessor->process(self::ENTRYPOINT);
        } catch (\Throwable $e) {
            throw InvalidHeadersException::becauseHeadersNotCompiled($e);
        }
    }

    /**
     * Returns the macro that enables the surface extensions of the platform.
     *
     * @return non-empty-string
     */
    private static function getMacro(Platform $platform): string
    {
        return match ($platform) {
            Platform::ANDROID => 'VK_USE_PLATFORM_ANDROID_KHR',
            Platform::FUCHSIA => 'VK_USE_PLATFORM_FUCHSIA',
            Platform::IOS => 'VK_USE_PLATFORM_IOS_MVK',
            Platform::MACOS => 'VK_USE_PLATFORM_MACOS_MVK',
            Platform::METAL => 'VK_USE_PLATFORM_METAL_EXT',
            Platform::VI => 'VK_USE_PLATFORM_VI_NN',
            Platform::WAYLAND => 'VK_USE_PLATFORM_WAYLAND_KHR',
            Platform::WIN32 => 'VK_USE_PLATFORM_WIN32_KHR',
            Platform::XCB => 'VK_USE_PLATFORM_XCB_KHR',
            Platform::XLIB => 'VK_USE_PLATFORM_XLIB_KHR',
            Platform::DIRECTFB => 'VK_USE_PLATFORM_DIRECTFB_EXT',
            Platform::XLIB_XRANDR => 'VK_USE_PLATFORM_XLIB_XRANDR_EXT',
            Platform::GGP => 'VK_USE_PLATFORM_GGP',
            Platform::SCREEN => 'VK_USE_PLATFORM_SCREEN_QNX',
            Platform::OHOS => 'VK_USE_PLATFORM_OHOS',
            Platform::UBM => 'VK_USE_PLATFORM_UBM_SEC',
        };
    }
}
