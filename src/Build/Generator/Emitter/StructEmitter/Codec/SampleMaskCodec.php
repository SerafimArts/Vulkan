<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\StructEmitter\Codec;

use Serafim\Vulkan\Build\Generator\Analysis\Names;
use Serafim\Vulkan\Exception\InvalidStructure\WrongArrayLengthException;

/**
 * `const uint32_t*` of the words of a mask of samples: the number of the words
 * follows the number of the samples.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class SampleMaskCodec extends AbstractPropertyCodec
{
    public function __construct(
        private NumberTypes $numbers,
    ) {}

    public function createWriteLines(PropertyContext $context): array
    {
        $context->namespace->addUse(WrongArrayLengthException::class);

        $property = $context->property;
        $value = $context->createValueExpression();
        $samples = '$this->' . Names::createVariableName($property->field->length ?? '');

        return [
            'if (' . $value . ' !== []) {',
            '    $words = (' . $samples . ' + 31) >> 5;',
            '',
            '    if (\count(' . $value . ') !== $words) {',
            '        throw WrongArrayLengthException::becauseArrayHasWrongLength('
                . \var_export($context->structure, true) . ', ' . \var_export($property->name, true) . ', $words);',
            '    }',
            '',
            '    ' . $context->createMemberExpression() . ' = $memory->ffi->cast(\'uint32_t*\', '
                . '$memory->toNumberArray(\'uint32_t\', ' . $value . '));',
            '}',
        ];
    }

    public function createDocType(PropertyContext $context): string
    {
        return 'list<' . $this->numbers->create($context->property) . '>';
    }
}
