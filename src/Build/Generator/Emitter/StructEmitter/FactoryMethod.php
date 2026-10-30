<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter;

use FFI\CData;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\PropertyPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructPlan;
use Serafim\Vulkan\Build\Generator\Analysis\StructRole;
use Serafim\Vulkan\Build\Generator\Emitter\ExtensionEmitter;
use Serafim\Vulkan\Build\Generator\Emitter\Imports;
use Serafim\Vulkan\Build\Generator\Emitter\Locations;
use Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec\NumberTypes;
use Serafim\Vulkan\Exception\InvalidStructure\MissingContextException;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\VulkanBase;

/**
 * The method of a structure that copies the members of a native struct to a
 * new object.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class FactoryMethod
{
    /**
     * @param list<non-empty-string> $contextual the returned structures that
     *        hold handles
     */
    public function __construct(
        private NumberTypes $numbers,
        private Reader $reader,
        private Shape $shape,
        private ChainMethods $chains,
        private array $contextual = [],
    ) {}

    public function emit(PhpNamespace $namespace, ClassType $class, StructPlan $plan): void
    {
        $namespace->addUse(CData::class);

        $isChained = $this->chains->hasChain($plan) && $plan->role === StructRole::OUTPUT;
        $read = $this->findReadProperties($plan);
        $arguments = [];

        foreach ($plan->inherited as $property) {
            $arguments[] = $property->name . ': $parent->' . $property->name . ',';
        }

        foreach ($read as $property) {
            \array_push($arguments, ...$this->createArgument($namespace, $property));
        }

        if ($isChained) {
            $arguments[] = 'chain: $chain,';
        }

        $body = "return new self(\n    " . \implode("\n    ", $arguments) . "\n);";

        if ($arguments === []) {
            $body = 'return new self();';
        }

        if ($plan->ancestors !== []) {
            $body = '$parent = ' . $plan->ancestors[0] . '::createFromCData(...$ancestors);' . "\n\n" . $body;
        }

        if ($plan->needsContext) {
            $namespace->addUse(VulkanBase::class);
            $namespace->addUse(Handle::class);
            $namespace->addUse(MissingContextException::class);

            $body = "if (\$vk === null) {\n"
                . '    throw MissingContextException::becauseContextIsMissing(self::class);'
                . "\n}\n\n" . $body;
        }

        $method = $class->addMethod('createFromCData')
            ->setPublic()
            ->setStatic()
            ->setReturnType('self')
            ->setBody($body);

        $method->addParameter('data')->setType(CData::class);

        if ($plan->version !== null) {
            $method->addParameter('ancestors')->setType(CData::class);
            $method->setVariadic();
        }

        if ($plan->counts !== null) {
            $method->addParameter('counts')->setType(CData::class);
        }

        if ($isChained) {
            $method->addParameter('chain', new Literal('new ChainSet()'))
                ->setType(Locations::createClassName(Locations::STRUCT_NAMESPACE, 'ChainSetInterface'));
        }

        if ($plan->needsContext) {
            $method->addParameter('vk', null)->setType(VulkanBase::class)->setNullable();
            $method->addParameter('parent', null)->setType(Handle::class)->setNullable();
        }

        $comments = $this->shape->createRead($namespace, $read);

        if ($isChained) {
            $comments[] = '@param ChainSetInterface<' . Imports::import(
                $namespace,
                Locations::EXTENSION_NAMESPACE,
                ExtensionEmitter::createName($plan->chains[0] ?? ''),
            ) . '> $chain';
        }

        \array_push($comments, ...$this->shape->createCounts($plan));

        foreach ($comments as $comment) {
            $method->addComment($comment);
        }

        InternalMethod::mark($method);
    }

    /**
     * Returns the lines of the argument that is read from the native struct,
     * with the comments that silence what the analysis cannot see.
     *
     * @return list<string>
     */
    private function createArgument(PhpNamespace $namespace, PropertyPlan $property): array
    {
        $lines = [];

        $isWide = $property->kind === PropertyKind::NUMBER_LIST
            && $this->numbers->create($property) !== 'float';

        if ($isWide) {
            $lines[] = '// @phpstan-ignore-next-line the elements of the array are of any width';
        }

        $isNamedDifferently = $property->kind === PropertyKind::NATIVE
            || $property->kind === PropertyKind::QUERIED_ARRAY
            || $property->kind === PropertyKind::OWNED_ARRAY
            || $property->kind === PropertyKind::QUERIED_BYTES;

        if ($isNamedDifferently && $property->name !== $property->field->name) {
            $lines[] = '// @phpstan-ignore-next-line the member is named differently from the property';
        }

        $isContextual = \in_array($property->field->type, $this->contextual, true);

        $lines[] = $property->name . ': ' . $this->reader->createExpression($namespace, $property, $isContextual) . ',';

        return $lines;
    }

    /**
     * Returns the properties that are read from the native struct: the chain
     * of the structures is not read.
     *
     * @return list<PropertyPlan>
     */
    private function findReadProperties(StructPlan $plan): array
    {
        return \array_values(\array_filter(
            $plan->properties,
            static fn(PropertyPlan $property): bool => $property->kind !== PropertyKind::NEXT
                && $property->kind !== PropertyKind::CHAIN,
        ));
    }
}
