<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec\Codecs;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec\PropertyContext;
use Serafim\Vulkan\Build\Generator\Registry\FieldDefinition;
use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * Writing of the properties to the members of a native struct.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Writer
{
    public function __construct(
        private Codecs $codecs,
    ) {}

    /**
     * @param string $structure the name of the structure
     * @param bool $isLengthShared whether an array that is written before
     *        has the same length member, or the length is a property
     * @param bool $isLength whether the property is the length of arrays
     *
     * @return list<string>
     */
    public function createLines(
        PhpNamespace $namespace,
        PropertyPlan $property,
        string $structure,
        bool $isLengthShared,
        bool $isLength,
    ): array {
        if ($property->field->bitWord !== null) {
            return [$this->createBitFieldLine($property->field, '$this->' . $property->name)];
        }

        return $this->codecs->get($property->kind)->createWriteLines(
            new PropertyContext($namespace, $property, $structure, $isLengthShared, $isLength),
        );
    }

    /**
     * Returns the check that a union has exactly one member.
     *
     * @return list<string>
     */
    public function createUnionLines(PhpNamespace $namespace, StructPlan $plan): array
    {
        $namespace->addUse(InvalidStructureException::class);

        $members = [];

        foreach ($plan->properties as $property) {
            $members[] = '$this->' . $property->name;
        }

        return [
            '$members = \count(\array_filter([' . \implode(', ', $members)
                . '], static fn(mixed $member): bool => $member !== null));',
            '',
            'if ($members !== 1) {',
            '    throw InvalidStructureException::becauseUnionDoesNotHaveOneMember('
                . \var_export($plan->struct->name, true) . ', $members);',
            '}',
        ];
    }

    /**
     * Writes the member of a union when the user has given it.
     *
     * @param list<string> $lines
     *
     * @return list<string>
     */
    public function createGuardedLines(PropertyPlan $property, array $lines): array
    {
        $guard = 'if ($this->' . $property->name . ' !== null) {';

        if (($lines[0] ?? '') === $guard) {
            return $lines;
        }

        return [
            $guard,
            ...\array_map(static fn(string $line): string => '    ' . $line, $lines),
            '}',
        ];
    }

    /**
     * Puts a bit-field to the word of the native structure, the first
     * bit-field of the word replaces the previous contents.
     */
    private function createBitFieldLine(FieldDefinition $field, string $value): string
    {
        $mask = \sprintf('0x%X', 2 ** ($field->bits ?? 1) - 1);
        $word = '$data->' . $field->bitWord;

        if ($field->bitOffset === 0) {
            return $word . ' = ' . $value . ' & ' . $mask . ';';
        }

        return $word . ' |= (' . $value . ' & ' . $mask . ') << ' . $field->bitOffset . ';';
    }
}
