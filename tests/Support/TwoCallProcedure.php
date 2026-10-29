<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Support;

use Serafim\Vulkan\Enum\VkResult;

/**
 * The procedure of a command that is called twice: the first call writes the
 * sizes, the next one writes the data.
 */
final class TwoCallProcedure
{
    /**
     * The number of the calls
     */
    public private(set) int $calls = 0;

    public function __construct(
        /**
         * Tells whether the call is the first one by the arguments of the command
         *
         * @var \Closure(mixed...): bool
         */
        private readonly \Closure $isProbe,
        /**
         * Writes the sizes
         *
         * @var \Closure(mixed...): void
         */
        private readonly \Closure $probe,
        /**
         * Writes the data
         *
         * @var \Closure(mixed...): void
         */
        private readonly \Closure $fill,
        private readonly VkResult $result = VkResult::VK_SUCCESS,
    ) {}

    public function __invoke(mixed ...$arguments): int
    {
        ++$this->calls;

        if (($this->isProbe)(...$arguments)) {
            ($this->probe)(...$arguments);
        } else {
            ($this->fill)(...$arguments);
        }

        return $this->result->value;
    }
}
