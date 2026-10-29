<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\Method;

/**
 * The public API of a structure is its properties, the methods the library
 * calls are not a part of it.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class InternalMethod
{
    private function __construct() {}

    /**
     * Marks the method as not being a part of the public API.
     */
    public static function mark(Method $method): void
    {
        $method->addComment('');
        $method->addComment('@internal this is an internal library method, please do not use it in your code');
        $method->addComment('@psalm-internal Serafim\Vulkan');
    }
}
