<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Registry;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CommandDefinition
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        /**
         * @var non-empty-string
         */
        public string $returnType,
        /**
         * @var list<FieldDefinition>
         */
        public array $parameters,
        /**
         * Names of the `VkResult` cases the command returns on success
         *
         * @var list<non-empty-string>
         */
        public array $successCodes = [],
        /**
         * Names of the `VkResult` cases the command may fail with
         *
         * @var list<non-empty-string>
         */
        public array $errorCodes = [],
        /**
         * Queues the command can be submitted to, the names of `VkQueueFlagBits`
         *
         * @var list<non-empty-string>
         */
        public array $queues = [],
        /**
         * Whether the command is recorded inside a render pass, outside of it or both
         *
         * @var non-empty-string|null
         */
        public ?string $renderPass = null,
        /**
         * Levels of the command buffer the command is recorded to
         *
         * @var list<non-empty-string>
         */
        public array $levels = [],
    ) {}

    /**
     * Whether the command gets an object that Vulkan owns: it is not
     * created and is not destroyed.
     */
    public function isGetter(): bool
    {
        return \str_starts_with($this->name, 'vkGet');
    }

    /**
     * Whether the command releases an object.
     */
    public function isReleasing(): bool
    {
        return \preg_match('/^vk(Destroy|Free|Release)/', $this->name) === 1;
    }
}
