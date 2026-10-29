<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Internal\Memory\Allocation;

/**
 * The method of a structure of the user that writes the properties to the
 * members of a native struct.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class WriteMethod
{
    public function __construct(
        private Writer $writer,
        private Shape $shape,
    ) {}

    public function emit(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $lines = [];

        if ($plan->structureType !== null) {
            $lines[] = '$data->sType = self::STRUCTURE_TYPE;';
        }

        if ($plan->struct->isUnion) {
            $lines = $this->writer->createUnionLines($namespace, $plan);
        }

        $names = $this->findPropertyFields($plan);
        $arrayLengths = $this->findLengths($plan);
        $lengths = [];

        foreach ($plan->properties as $property) {
            if ($lines !== []) {
                $lines[] = '';
            }

            $length = $this->findArrayLength($property);
            $isShared = $length !== null && (isset($lengths[$length]) || isset($names[$length]));
            $isLength = isset($arrayLengths[$property->field->name]);

            if ($length !== null) {
                $lengths[$length] = true;
            }

            $propertyLines = $this->writer->createLines($namespace, $property, $plan->struct->name, $isShared, $isLength);

            if ($plan->struct->isUnion) {
                $propertyLines = $this->writer->createGuardedLines($property, $propertyLines);
            }

            \array_push($lines, ...$propertyLines);
        }

        $write = $class->addMethod('write')
            ->setPublic()
            ->setReturnType('void')
            ->setBody(\implode("\n", $lines));

        $write->addParameter('memory')->setType(Allocation::class);
        $write->addParameter('data')->setType(CData::class);

        foreach ($this->shape->createWritten($namespace, $plan) as $line) {
            $write->addComment($line);
        }

        $write->addComment('');
        $write->addComment('@internal this is an internal library method, please do not use it in your code');
        $write->addComment('@psalm-internal Serafim\Vulkan');
    }

    /**
     * Returns the members of the structure that are properties.
     *
     * @return array<string, true>
     */
    private function findPropertyFields(StructPlan $plan): array
    {
        $names = [];

        foreach ($plan->properties as $property) {
            $names[$property->field->name] = true;
        }

        return $names;
    }

    /**
     * Returns the length members of the arrays of the structure.
     *
     * @return array<string, true>
     */
    private function findLengths(StructPlan $plan): array
    {
        $lengths = [];

        foreach ($plan->properties as $property) {
            $length = $this->findArrayLength($property);

            if ($length !== null) {
                $lengths[$length] = true;
            }
        }

        return $lengths;
    }

    /**
     * Returns the name of the length member of the array property.
     */
    private function findArrayLength(PropertyPlan $property): ?string
    {
        $isArray = \in_array($property->kind, [
            PropertyKind::STRING_LIST,
            PropertyKind::BINARY,
            PropertyKind::STRUCTURE_ARRAY,
            PropertyKind::HANDLE_ARRAY,
            PropertyKind::NUMBER_ARRAY,
            PropertyKind::ENUMERATION_ARRAY,
        ], true);

        if ($isArray) {
            return $property->field->length;
        }

        return null;
    }
}
