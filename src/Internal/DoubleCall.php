<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Internal;

use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Internal\DoubleCall\ClosureQuery;
use Serafim\Vulkan\Internal\DoubleCall\QueryInterface;
use Serafim\Vulkan\VulkanBase;

/**
 * A command that is called more than once: the first call tells the sizes of
 * what it will write, the memory is allocated for them and the next call
 * writes the data.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan
 */
final readonly class DoubleCall
{
    private function __construct() {}

    /**
     * Calls the command made of closures, see {@see self::execute()}.
     *
     * @param non-empty-string $command
     * @param \Closure(): mixed $call
     * @param \Closure(): bool $allocate
     * @param (\Closure(): void)|null $reset
     *
     * @throws CommandFailedException in case of the command fails
     */
    public static function run(
        VulkanBase $vk,
        string $command,
        \Closure $call,
        \Closure $allocate,
        ?\Closure $reset = null,
    ): void {
        self::execute($vk, $command, new ClosureQuery($call, $allocate, $reset));
    }

    /**
     * Calls the command until nothing is left to allocate. The command is
     * called from the beginning when it reports `VK_INCOMPLETE`: the data has
     * changed between the calls.
     *
     * @param non-empty-string $command
     *
     * @throws CommandFailedException in case of the command fails
     */
    public static function execute(VulkanBase $vk, string $command, QueryInterface $query): void
    {
        $isRepeated = false;

        do {
            if ($isRepeated) {
                $query->reset();
            }

            $isRepeated = true;
            $result = self::getCode($query->call());

            while ($result === VkResult::VK_SUCCESS->value && $query->allocate()) {
                $result = self::getCode($query->call());
            }
        } while ($result === VkResult::VK_INCOMPLETE->value);

        $vk->assertSuccess($command, $result);
    }

    /**
     * Returns the code the command has returned, a command that returns
     * nothing is successful.
     */
    private static function getCode(mixed $result): int
    {
        if (\is_int($result)) {
            return $result;
        }

        return VkResult::VK_SUCCESS->value;
    }
}
