<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Analysis;

use Serafim\Vulkan\Build\Generator\Registry\FunctionPointer;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * Tells what a C type of the registry is.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class TypeResolver
{
    private const array INTEGERS = [
        'int',
        'uint8_t',
        'uint16_t',
        'uint32_t',
        'uint64_t',
        'int8_t',
        'int16_t',
        'int32_t',
        'int64_t',
        'size_t',
        'VkFlags',
        'VkFlags64',
    ];

    public function __construct(
        private Registry $registry,
    ) {}

    public function resolve(string $type): TypeKind
    {
        if ($type === 'char') {
            return TypeKind::CHARACTER;
        }

        if ($type === 'float' || $type === 'double') {
            return TypeKind::FLOAT;
        }

        if ($type === 'VkBool32') {
            return TypeKind::BOOLEAN;
        }

        if (\in_array($type, self::INTEGERS, true) || $this->registry->findBitmaskType($type) !== null) {
            return TypeKind::INTEGER;
        }

        $base = $this->registry->findBaseType($type);

        if ($base !== null) {
            return $this->resolve($base);
        }

        if ($this->registry->findEnum($type) !== null) {
            return TypeKind::ENUMERATION;
        }

        if ($this->registry->findHandle($type) !== null) {
            return TypeKind::HANDLE;
        }

        if ($this->registry->findStruct($type) !== null) {
            return TypeKind::STRUCTURE;
        }

        if ($this->registry->findFunctionPointer($type) !== null) {
            return TypeKind::CALLBACK;
        }

        if ($type === 'void' || $this->registry->isNativeType($type)) {
            return TypeKind::NATIVE;
        }

        return TypeKind::UNSUPPORTED;
    }

    public function findFunctionPointer(string $type): ?FunctionPointer
    {
        return $this->registry->findFunctionPointer($type);
    }

    /**
     * Returns the name of the type of the bits of a flag type.
     *
     * @return non-empty-string|null
     */
    public function findFlagBits(string $type): ?string
    {
        return $this->registry->findFlagBits($type);
    }

    /**
     * Returns how many values a type of the bits has.
     *
     * @return int<0, max>
     */
    public function countFlagBits(string $bits): int
    {
        return $this->registry->countFlagBits($bits);
    }

    /**
     * Returns the PHPStan type of an integer of the C type, the range follows
     * its width. A 64-bit unsigned value is a plain `int` holding the bit
     * pattern, the values from 2^63 are negative (`~0ULL` is `-1`). The range
     * of a bit-field follows the number of its bits.
     *
     * @param int<1, 64>|null $bits
     */
    public function getIntegerRange(string $type, ?int $bits = null): string
    {
        if ($bits !== null && $bits < 63) {
            return 'int<0, ' . (2 ** $bits - 1) . '>';
        }

        $type = $this->registry->findBitmaskType($type) ?? $type;
        $base = $this->registry->findBaseType($type);

        while ($base !== null) {
            $type = $base;
            $base = $this->registry->findBaseType($type);
        }

        return match (true) {
            $type === 'size_t' => 'int<0, max>',
            $type === 'uint8_t' => 'int<0, 255>',
            $type === 'uint16_t' => 'int<0, 65535>',
            $type === 'uint32_t', $type === 'VkFlags' => 'int<0, 4294967295>',
            $type === 'int8_t' => 'int<-128, 127>',
            $type === 'int16_t' => 'int<-32768, 32767>',
            $type === 'int32_t' => 'int<-2147483648, 2147483647>',
            default => 'int',
        };
    }
}
