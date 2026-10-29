<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal\Driver;

use FFI\CData;
use Serafim\Vulkan\Exception\CommandNotAvailableException;
use Serafim\Vulkan\Handle\Handle;

/**
 * The procedures of the library, they are found by `vkGetInstanceProcAddr()`
 * of the instance the handle belongs to.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class NativeDriver implements DriverInterface
{
    public function __construct(
        /**
         * @var object{
         *     vkGetInstanceProcAddr: CData&(callable(CData, string): CData),
         *     ...
         * }|\FFI
         */
        private \FFI $ffi,
    ) {}

    public function getProcedure(string $command, ?Handle $handle = null): callable
    {
        $root = $handle;

        while ($root?->parent !== null) {
            $root = $root->parent;
        }

        // @phpstan-ignore-next-line the function is declared by the headers of the library
        $pointer = $this->ffi->vkGetInstanceProcAddr($root?->pointer, $command);

        \assert($pointer === null || $pointer instanceof CData);

        if ($pointer === null || \FFI::isNull($pointer)) {
            throw CommandNotAvailableException::becauseCommandNotAvailable($command);
        }

        // @phpstan-ignore-next-line a pointer to a function is callable
        return $this->ffi->cast('PFN_' . $command, $pointer);
    }
}
