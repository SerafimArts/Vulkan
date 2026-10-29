<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

/**
 * The structures that the user fills in part and Vulkan fills in the rest.
 * The registry does not tell which members are which, so the member the user
 * gives is listed here. The command takes it as a list of values and returns
 * the structures as they are read.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class MixedStructures
{
    /**
     * The member that the user gives by the name of the structure
     */
    private const array INPUTS = [
        'VkDataGraphPipelinePropertyQueryResultARM' => 'property',
    ];

    /**
     * Returns the name of the member that the user gives.
     *
     * @return non-empty-string|null
     */
    public static function findInput(string $struct): ?string
    {
        return self::INPUTS[$struct] ?? null;
    }
}
