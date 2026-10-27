<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;

/**
 * The PHPStan types of the integers of the specification.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class IntegerTypes
{
    /**
     * PHPStan enumerates all the combinations of the bits of a mask, so a mask
     * of many bits is written as the range with the constants.
     */
    private const int MAX_MASK_BITS = 12;

    /**
     * Returns the mask of the bits for flags of a few bits, the range of the C
     * type with the bits for a single bit and for flags of many bits, the
     * range of the C type for the rest. The range of a bit-field follows its
     * width.
     *
     * @param int<1, 64>|null $width
     */
    public static function create(
        PhpNamespace $namespace,
        TypeResolver $types,
        string $type,
        ?int $width = null,
    ): string {
        $bits = $types->findFlagBits($type);

        if ($bits === null || $types->countFlagBits($bits) === 0) {
            return $types->getIntegerRange($type, $width);
        }

        Imports::import($namespace, Locations::ENUM_NAMESPACE, $bits);

        if ($bits === $type || $types->countFlagBits($bits) > self::MAX_MASK_BITS) {
            return $types->getIntegerRange($type, $width) . '|(' . $bits . '::*)';
        }

        return 'int-mask-of<' . $bits . '::*>';
    }

    private function __construct() {}
}
