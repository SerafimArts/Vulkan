<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;

/**
 * `pNext`, the structures chained to the structure.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class NextCodec extends AbstractPropertyCodec
{
    public function createWriteLines(PropertyContext $context): array
    {
        $marker = Imports::import(
            $context->namespace,
            Locations::EXTENSION_NAMESPACE,
            ExtensionEmitter::createName($context->structure),
        );

        return ['$memory->linkChain($data, ' . $context->createValueExpression() . ', ' . $marker . '::class);'];
    }
}
