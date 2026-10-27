<?php

declare(strict_types=1);

namespace Serafim\Vulkan;

use Serafim\Vulkan\Enum\VkResult;

/**
 * The value that a command returns together with its success code: the code
 * tells how the command has finished (`VK_SUBOPTIMAL_KHR`, `VK_NOT_READY`).
 *
 * @template TValue
 */
final readonly class CommandResult
{
    public function __construct(
        /**
         * The success code of the command
         */
        public VkResult $status,
        /**
         * The value the command has written
         *
         * @var TValue
         */
        public mixed $value,
    ) {}
}
