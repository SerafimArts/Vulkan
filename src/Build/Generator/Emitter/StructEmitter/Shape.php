<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * The docblock that describes the members of the native struct a method of a
 * generated structure works with.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Shape
{
    public function __construct(
        private Reader $reader,
        private Registry $registry,
    ) {}

    /**
     * Returns the lines that describe the members of the structure with the
     * lengths of the arrays, {@see null} lines if the structure has them
     * itself.
     *
     * @return list<string>
     */
    public function createCounts(StructPlan $plan): array
    {
        if ($plan->counts === null) {
            return [];
        }

        $used = [];

        foreach ($plan->properties as $property) {
            if ($property->field->lengthStruct !== null && $property->field->length !== null) {
                $used[$property->field->length] = true;
            }
        }

        $shape = ['@param CData&object{'];

        foreach ($this->registry->getStruct($plan->counts)->members as $member) {
            if (isset($used[$member->name])) {
                $shape[] = '    ' . $member->name . ': ' . $this->reader->createFieldType($member) . ',';
            }
        }

        $shape[] = '} $counts';
        $shape[] = '@phpstan-param CData $counts';

        return $shape;
    }

    /**
     * Returns the lines that describe every member of the native struct.
     *
     * @return list<string>
     */
    public function createWritten(PhpNamespace $namespace, StructPlan $plan): array
    {
        $properties = [];

        foreach ($plan->properties as $property) {
            $properties[$property->field->name] = $property;
        }

        $shape = ['@param CData&object{'];

        $words = [];

        foreach ($plan->struct->members as $member) {
            if ($member->bitWord !== null) {
                if (!isset($words[$member->bitWord])) {
                    $shape[] = '    ' . $member->bitWord . ': int<0, 4294967295>,';
                }

                $words[$member->bitWord] = true;

                continue;
            }

            $property = $properties[$member->name] ?? null;

            $type = match (true) {
                $property !== null => $this->reader->createType($namespace, $property),
                $member->name === 'sType' && $plan->structureType !== null => 'value-of<'
                    . Imports::import($namespace, Locations::ENUM_NAMESPACE, 'VkStructureType')
                    . '::' . $plan->structureType . '>',
                $member->name === 'sType' => 'int',
                $member->name === 'pNext' => 'CData',
                default => $this->reader->createFieldType($member),
            };

            $shape[] = '    ' . $member->name . ': ' . $type . ',';
        }

        $shape[] = '} $data';
        $shape[] = '@phpstan-param CData $data';

        return $shape;
    }

    /**
     * Returns the lines that describe the members the properties are read
     * from.
     *
     * @param list<PropertyPlan> $properties
     *
     * @return list<string>
     */
    public function createRead(PhpNamespace $namespace, array $properties): array
    {
        $shape = ['@param CData&object{'];

        $words = [];

        foreach ($properties as $property) {
            if ($property->field->bitWord !== null) {
                if (!isset($words[$property->field->bitWord])) {
                    $shape[] = '    ' . $property->field->bitWord . ': int<0, 4294967295>,';
                }

                $words[$property->field->bitWord] = true;

                continue;
            }

            $shape[] = '    ' . $property->field->name . ': ' . $this->reader->createType($namespace, $property) . ',';
        }

        $shape[] = '} $data';
        $shape[] = '@phpstan-param CData $data';

        return $shape;
    }
}
