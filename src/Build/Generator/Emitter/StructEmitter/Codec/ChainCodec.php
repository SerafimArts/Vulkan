<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

/**
 * The structures of the chain that a command has filled: they are not read from the
 * native struct.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class ChainCodec extends AbstractPropertyCodec {}
