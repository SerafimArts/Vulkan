<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\PropertyKind;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;

/**
 * The codec of every kind of a property.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class Codecs
{
    /**
     * @var array<string, PropertyCodecInterface> the codecs by the names of the kinds
     */
    private array $codecs;

    public function __construct(TypeResolver $types, NumberTypes $numbers)
    {
        $this->codecs = [
            PropertyKind::INTEGER->name => new IntegerCodec($types),
            PropertyKind::FLOAT->name => new FloatCodec(),
            PropertyKind::BOOLEAN->name => new BooleanCodec(),
            PropertyKind::ENUMERATION->name => new EnumerationCodec(),
            PropertyKind::STRING->name => new StringCodec(),
            PropertyKind::STRING_LIST->name => new StringListCodec(),
            PropertyKind::TEXT->name => new TextCodec(),
            PropertyKind::BINARY->name => new BinaryCodec(),
            PropertyKind::CALLBACK->name => new CallbackCodec($types),
            PropertyKind::NATIVE->name => new NativeCodec(),
            PropertyKind::SAMPLE_MASK->name => new SampleMaskCodec($numbers),
            PropertyKind::BYTES->name => new BytesCodec(),
            PropertyKind::HANDLE_POINTER->name => new HandlePointerCodec(),
            PropertyKind::SCALAR_POINTER->name => new ScalarPointerCodec($types),
            PropertyKind::NUMBER_LIST->name => new NumberListCodec($numbers),
            PropertyKind::STRUCTURE_POINTER->name => new StructurePointerCodec(),
            PropertyKind::STRUCTURE_VALUE->name => new StructureValueCodec(),
            PropertyKind::HANDLE->name => new HandleCodec(),
            PropertyKind::HANDLE_LIST->name => new HandleListCodec(),
            PropertyKind::ENUMERATION_LIST->name => new EnumerationListCodec(),
            PropertyKind::STRUCTURE_LIST->name => new StructureListCodec(),
            PropertyKind::FEEDBACK_STRUCTURE->name => new FeedbackStructureCodec(),
            PropertyKind::FEEDBACK_ARRAY->name => new FeedbackArrayCodec(),
            PropertyKind::QUERIED_ARRAY->name => new QueriedArrayCodec($types, $numbers),
            PropertyKind::QUERIED_BYTES->name => new QueriedBytesCodec(),
            PropertyKind::STRUCTURE_ARRAY->name => new StructureArrayCodec(),
            PropertyKind::HANDLE_ARRAY->name => new HandleArrayCodec(),
            PropertyKind::NUMBER_ARRAY->name => new NumberArrayCodec($numbers),
            PropertyKind::ENUMERATION_ARRAY->name => new EnumerationArrayCodec(),
            PropertyKind::BOOLEAN_ARRAY->name => new BooleanArrayCodec(),
            PropertyKind::VALUE_POINTER_ARRAY->name => new ValuePointerArrayCodec(),
            PropertyKind::INOUT_INTEGER->name => new InoutIntegerCodec(),
            PropertyKind::OWNED_ARRAY->name => new QueriedArrayCodec($types, $numbers),
            PropertyKind::FEEDBACK_ENUMERATION->name => new FeedbackEnumerationCodec(),
            PropertyKind::NEXT->name => new NextCodec(),
            PropertyKind::CHAIN->name => new ChainCodec(),
        ];
    }

    public function get(PropertyKind $kind): PropertyCodecInterface
    {
        return $this->codecs[$kind->name] ?? throw new \LogicException(\sprintf('There is no codec of the kind %s', $kind->name));
    }
}
