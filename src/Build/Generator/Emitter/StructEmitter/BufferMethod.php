<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Internal\Memory\Allocator;

/**
 * The method of a returned structure that allocates the arrays Vulkan fills,
 * for the lengths the first call of a command has written.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class BufferMethod
{
    /**
     * @param list<non-empty-string> $buffered the returned structures whose
     *        arrays Vulkan fills
     */
    public function __construct(
        private Registry $registry,
        private Shape $shape,
        private array $buffered = [],
    ) {}

    /**
     * Whether Vulkan fills the arrays of the structure.
     */
    public function hasArrays(StructPlan $plan): bool
    {
        foreach ($plan->properties as $property) {
            if ($this->isQueried($property)) {
                return true;
            }
        }

        return false;
    }

    public function emit(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(Allocator::class);
        $namespace->addUse(CData::class);

        $class->addImplement(
            Imports::importFullName($namespace, Locations::EXTENSION_NAMESPACE, 'BufferedStructureInterface'),
        );

        $lines = ['$owner ??= $data;', '$isAllocated = false;'];

        foreach ($plan->properties as $property) {
            if ($this->isQueried($property)) {
                $lines = [...$lines, '', ...$this->createLines($namespace, $property)];
            }
        }

        $lines[] = '';
        $lines[] = 'return $isAllocated;';

        $method = $class->addMethod('allocateBuffers')
            ->setPublic()
            ->setStatic()
            ->setReturnType('bool')
            ->setBody(\implode("\n", $lines));

        $method->addParameter('memory')->setType(Allocator::class);
        $method->addParameter('data')->setType(CData::class);
        $method->addParameter('owner', null)->setType(CData::class)->setNullable();

        foreach ($this->shape->createWritten($namespace, $plan) as $line) {
            $method->addComment($line);
        }
    }

    private function isQueried(PropertyPlan $property): bool
    {
        return $property->kind === PropertyKind::QUERIED_ARRAY || $property->kind === PropertyKind::QUERIED_BYTES;
    }

    /**
     * Returns the lines that allocate the array of a property for the length
     * Vulkan has written, set `sType` of the elements that have one and
     * allocate the arrays of the elements. Nothing is allocated twice.
     *
     * @return list<string>
     */
    private function createLines(PhpNamespace $namespace, PropertyPlan $property): array
    {
        $length = '$data->' . ($property->field->length ?? '');
        $field = '$data->' . $property->field->name;
        $type = $property->field->type;
        $lines = ['if (' . $length . ' !== 0) {', '    if (' . $field . ' === null) {'];

        if ($property->kind === PropertyKind::QUERIED_BYTES) {
            $lines[] = '        ' . $field . ' = $memory->ffi->cast(\'void*\', $memory->newArray(\'uint8_t\', '
                . $length . ', $owner));';
        }

        if ($property->kind === PropertyKind::QUERIED_ARRAY && !$this->registry->isTypedStructure($type)) {
            $lines[] = '        ' . $field . ' = $memory->newArray(\'' . $type . '\', ' . $length . ', $owner);';
        }

        if ($property->kind === PropertyKind::QUERIED_ARRAY && $this->registry->isTypedStructure($type)) {
            $element = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $type);

            $lines = [
                ...$lines,
                '        $items = $memory->newArray(\'' . $type . '\', ' . $length . ', $owner);',
                '',
                '        for ($i = 0; $i < ' . $length . '; ++$i) {',
                '            /** @var CData $item */',
                '            $item = $items[$i];',
                '',
                '            $item->sType = ' . $element . '::STRUCTURE_TYPE;',
                '        }',
                '',
                '        ' . $field . ' = $items;',
            ];
        }

        $lines = [...$lines, '        $isAllocated = true;', '    }'];

        if ($property->kind === PropertyKind::QUERIED_ARRAY && \in_array($type, $this->buffered, true)) {
            $element = Imports::import($namespace, Locations::STRUCT_NAMESPACE, $type);

            $lines = [
                ...$lines,
                '',
                '    /** @var CData $items */',
                '    $items = ' . $field . ';',
                '',
                '    for ($i = 0; $i < ' . $length . '; ++$i) {',
                '        /** @var CData $item */',
                '        $item = $items[$i];',
                '',
                '        if (' . $element . '::allocateBuffers($memory, $item, $owner)) {',
                '            $isAllocated = true;',
                '        }',
                '    }',
            ];
        }

        return [...$lines, '}'];
    }
}
