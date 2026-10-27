<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\Names;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Exception\InvalidStructureException;

/**
 * Writing of the properties to the members of a native struct.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Writer
{
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
        $field = $property->field->name;
        $value = '$this->' . $property->name;
        $type = $property->field->type;

        return match ($property->kind) {
            PropertyKind::INTEGER => $this->createIntegerLines($field, $value, $isLength),
            PropertyKind::FLOAT => ['$data->' . $field . ' = ' . $value . ';'],
            PropertyKind::BOOLEAN => ['$data->' . $field . ' = (int) ' . $value . ';'],
            PropertyKind::ENUMERATION => ['$data->' . $field . ' = ' . $value . '->value;'],
            PropertyKind::STRING => [
                'if (' . $value . ' !== null) {',
                '    $data->' . $field . ' = $memory->toCString(' . $value . ');',
                '}',
            ],
            PropertyKind::STRING_LIST => $this->createArrayLines(
                $namespace,
                $property,
                $structure,
                $isLengthShared,
                '$memory->toCStringArray(' . $value . ')',
            ),
            PropertyKind::BINARY => [
                'if (' . $value . ' !== \'\') {',
                '    $data->' . ($property->field->length ?? '') . ' = \strlen(' . $value . ');',
                '    $data->' . $field . ' = $memory->ffi->cast(' . \var_export($type . '*', true)
                    . ', $memory->toCString(' . $value . '));',
                '}',
            ],
            PropertyKind::SAMPLE_MASK => $this->createSampleMaskLines($namespace, $property, $structure),
            PropertyKind::TEXT => $this->createTextLines($namespace, $property, $structure),
            PropertyKind::BYTES => $this->createBytesLines($namespace, $property, $structure),
            PropertyKind::NUMBER_LIST => $this->createListLines(
                $namespace,
                $property,
                $structure,
                ['$data->' . $field . '[$i] = $item;'],
            ),
            PropertyKind::STRUCTURE_LIST => $this->createListLines(
                $namespace,
                $property,
                $structure,
                [
                    '// @phpstan-ignore-next-line the element of the native array is a structure',
                    '$item->write($memory, $data->' . $field . '[$i]);',
                ],
            ),
            PropertyKind::STRUCTURE_POINTER => [
                'if (' . $value . ' !== null) {',
                '    $data->' . $field . ' = \FFI::addr($memory->toStructure(' . $value . '));',
                '}',
            ],
            PropertyKind::STRUCTURE_VALUE => [$value . '->write($memory, $data->' . $field . ');'],
            PropertyKind::HANDLE => $this->createHandleLines($property),
            PropertyKind::STRUCTURE_ARRAY => $this->createArrayLines(
                $namespace,
                $property,
                $structure,
                $isLengthShared,
                '$memory->toStructureArray(' . $value . ')',
            ),
            PropertyKind::HANDLE_ARRAY => $this->createArrayLines(
                $namespace,
                $property,
                $structure,
                $isLengthShared,
                '$memory->toHandleArray(' . \var_export($type, true) . ', ' . $value . ')',
            ),
            PropertyKind::NUMBER_ARRAY => $this->createArrayLines(
                $namespace,
                $property,
                $structure,
                $isLengthShared,
                '$memory->toNumberArray(' . \var_export($type, true) . ', ' . $value . ')',
            ),
            PropertyKind::ENUMERATION_ARRAY => $this->createArrayLines(
                $namespace,
                $property,
                $structure,
                $isLengthShared,
                '$memory->toNumberArray(' . \var_export($type, true) . ', \array_map(static fn('
                    . Imports::import($namespace, Locations::ENUM_NAMESPACE, $type)
                    . ' $item): int => $item->value, ' . $value . '))',
            ),
            default => [
                'if (' . $value . ' !== null) {',
                '    \assert(' . $value . ' instanceof InputStructure);',
                '',
                '    $data->pNext = $memory->ffi->cast(\'void*\', \FFI::addr($memory->toStructure(' . $value . ')));',
                '}',
            ],
        };
    }

    /**
     * Writes the elements of an array of the fixed size.
     *
     * @param list<string> $body the lines that write the element `$item` with the index `$i`
     *
     * @return list<string>
     */
    private function createListLines(
        PhpNamespace $namespace,
        PropertyPlan $property,
        string $structure,
        array $body,
    ): array {
        $namespace->addUse(InvalidStructureException::class);

        $value = '$this->' . $property->name;
        $size = (string) $property->arrayLength;

        return [
            'if (\count(' . $value . ') > ' . $size . ') {',
            '    throw InvalidStructureException::becauseArrayIsTooLong('
                . \var_export($structure, true) . ', ' . \var_export($property->name, true) . ', ' . $size . ');',
            '}',
            '',
            'foreach (' . $value . ' as $i => $item) {',
            ...\array_map(static fn(string $line): string => '    ' . $line, $body),
            '}',
        ];
    }

    /**
     * @return list<string>
     */
    private function createTextLines(PhpNamespace $namespace, PropertyPlan $property, string $structure): array
    {
        $namespace->addUse(InvalidStructureException::class);

        $value = '$this->' . $property->name;
        $size = (string) $property->arrayLength;

        return [
            'if (\strlen(' . $value . ') >= ' . $size . ') {',
            '    throw InvalidStructureException::becauseArrayIsTooLong('
                . \var_export($structure, true) . ', ' . \var_export($property->name, true) . ', ' . $size . ' - 1);',
            '}',
            '',
            '\FFI::memcpy($data->' . $property->field->name . ', ' . $value . ' . "\0", \strlen(' . $value . ') + 1);',
        ];
    }

    /**
     * @return list<string>
     */
    private function createBytesLines(PhpNamespace $namespace, PropertyPlan $property, string $structure): array
    {
        $namespace->addUse(InvalidStructureException::class);

        $value = '$this->' . $property->name;
        $size = (string) $property->arrayLength;

        return [
            'if (\strlen(' . $value . ') > ' . $size . ') {',
            '    throw InvalidStructureException::becauseArrayIsTooLong('
                . \var_export($structure, true) . ', ' . \var_export($property->name, true) . ', ' . $size . ');',
            '}',
            '',
            'if (' . $value . ' !== \'\') {',
            '    \FFI::memcpy($data->' . $property->field->name . ', ' . $value . ', \strlen(' . $value . '));',
            '}',
        ];
    }

    /**
     * Writes the words of the mask: `(samples + 31) / 32` of them.
     *
     * @return list<string>
     */
    private function createSampleMaskLines(PhpNamespace $namespace, PropertyPlan $property, string $structure): array
    {
        $namespace->addUse(InvalidStructureException::class);

        $value = '$this->' . $property->name;
        $samples = '$this->' . Names::createVariableName($property->field->length ?? '');

        return [
            'if (' . $value . ' !== []) {',
            '    $words = (' . $samples . ' + 31) >> 5;',
            '',
            '    if (\count(' . $value . ') !== $words) {',
            '        throw InvalidStructureException::becauseArrayHasWrongLength('
                . \var_export($structure, true) . ', ' . \var_export($property->name, true) . ', $words);',
            '    }',
            '',
            '    $data->' . $property->field->name . ' = $memory->ffi->cast(\'uint32_t*\', '
                . '$memory->toNumberArray(\'uint32_t\', ' . $value . '));',
            '}',
        ];
    }

    /**
     * The length of the arrays is written by the arrays when they are not
     * empty, the value of the property is the length otherwise.
     *
     * @return list<string>
     */
    private function createIntegerLines(string $field, string $value, bool $isLength): array
    {
        if (!$isLength) {
            return ['$data->' . $field . ' = ' . $value . ';'];
        }

        return [
            'if (' . $value . ' !== 0) {',
            '    $data->' . $field . ' = ' . $value . ';',
            '}',
        ];
    }

    /**
     * @return list<string>
     */
    private function createHandleLines(PropertyPlan $property): array
    {
        $field = '$data->' . $property->field->name;
        $value = '$this->' . $property->name;
        $cast = $field . ' = $memory->ffi->cast(' . \var_export($property->field->type, true) . ', ' . $value
            . '->pointer);';

        if ($property->isNullable) {
            return ['if (' . $value . ' !== null) {', '    ' . $cast, '}'];
        }

        return [$cast];
    }

    /**
     * Writes the length and the pointer of an array that is not empty. The
     * arrays that share the length member must have the same length.
     *
     * @param string $array the expression that allocates the native array
     *
     * @return list<string>
     */
    private function createArrayLines(
        PhpNamespace $namespace,
        PropertyPlan $property,
        string $structure,
        bool $isLengthShared,
        string $array,
    ): array {
        $value = '$this->' . $property->name;
        $length = '$data->' . ($property->field->length ?? '');
        $lines = ['if (' . $value . ' !== []) {'];

        if ($isLengthShared) {
            $namespace->addUse(InvalidStructureException::class);

            \array_push(
                $lines,
                '    if (' . $length . ' !== 0 && ' . $length . ' !== \count(' . $value . ')) {',
                '        throw InvalidStructureException::becauseArraysHaveDifferentLengths('
                    . \var_export($structure, true) . ', ' . \var_export($property->name, true) . ');',
                '    }',
                '',
            );
        }

        \array_push(
            $lines,
            '    ' . $length . ' = \count(' . $value . ');',
            '    $data->' . $property->field->name . ' = $memory->ffi->cast('
                . \var_export($this->createPointerType($property), true) . ', ' . $array . ');',
            '}',
        );

        return $lines;
    }

    private function createPointerType(PropertyPlan $property): string
    {
        if ($property->kind === PropertyKind::STRING_LIST) {
            return 'char**';
        }

        return $property->field->type . '*';
    }
}
