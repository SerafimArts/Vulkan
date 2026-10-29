<?php

declare(strict_types=1);

namespace Serafim\Vulkan;

use FFI\CData;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Exception\CommandNotAvailableException;
use Serafim\Vulkan\Handle\Handle;
use Serafim\Vulkan\Internal\Memory\Allocator;
use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Exception\InvalidLibraryException;
use Serafim\Vulkan\Loader\Exception\InvalidPlatformException;
use Serafim\Vulkan\Loader\Exception\InvalidRuntimeException;
use Serafim\Vulkan\Loader\Exception\LoadingException;
use Serafim\Vulkan\Loader\VulkanLoader;

/**
 * Base of the commands of the Vulkan API: the loading of the library and the
 * helpers the generated commands are made of.
 *
 * @phpstan-consistent-constructor
 */
abstract class VulkanBase
{
    /**
     * Native memory of the commands.
     *
     * @internal this is an internal library property, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    public readonly Allocator $memory;

    /**
     * Pointers to the commands by their names. A pointer belongs to the library
     * of the object, it is not shared between the objects.
     *
     * @var array<non-empty-string, callable>
     */
    private array $procedures = [];

    final public function __construct(
        public readonly \FFI $ffi,
    ) {
        $this->memory = new Allocator($ffi);
    }

    /**
     * Loads the Vulkan library of the environment, a new object every time.
     *
     * @api
     *
     * @throws InvalidRuntimeException in case of `ext-ffi` is not available
     * @throws InvalidLibraryException in case of the library cannot be found
     * @throws InvalidPlatformException in case of the platform cannot be
     *         detected
     * @throws InvalidHeadersException in case of the headers cannot be read
     * @throws LoadingException in case of the library cannot be bound to the
     *         headers
     */
    public static function create(?VulkanLoader $loader = null): static
    {
        $loader ??= VulkanLoader::createDefault();

        // @phpstan-ignore-next-line the constructor is final
        return new static($loader->load());
    }

    /**
     * Returns the success code of the `VkResult` returned by the command.
     *
     * @param non-empty-string $command
     *
     * @throws CommandFailedException in case of the result is an error
     *
     * @internal this is an internal library method, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    public function getResult(string $command, int $result): VkResult
    {
        $code = VkResult::tryFrom($result);

        if ($code === null || $result < 0) {
            throw CommandFailedException::becauseCommandFailed($command, $result);
        }

        return $code;
    }

    /**
     * Checks the `VkResult` returned by the command.
     *
     * @param non-empty-string $command
     *
     * @throws CommandFailedException in case of the result is an error
     *
     * @internal this is an internal library method, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    public function assertSuccess(string $command, mixed $result): void
    {
        if ($result === VkResult::VK_SUCCESS->value) {
            return;
        }

        \assert(\is_int($result));

        throw CommandFailedException::becauseCommandFailed($command, $result);
    }

    /**
     * Returns the pointer to the native value, or {@see null} if there is
     * no value.
     */
    protected function addr(?CData $data): ?CData
    {
        if ($data === null) {
            return null;
        }

        return \FFI::addr($data);
    }

    /**
     * Returns the pointer to the command that can be called.
     *
     * @param non-empty-string $command
     * @param Handle|null $handle the handle the command is called on,
     *        {@see null} for a command that needs none
     *
     * @throws CommandNotAvailableException in case of the command is not
     *         supported
     */
    public function vkGetInstanceProc(string $command, ?Handle $handle = null): callable
    {
        return $this->procedures[$command] ??= $this->findInstanceProc($command, $handle);
    }

    /**
     * Returns the copy whose command calls the procedure instead of the
     * function of the library.
     *
     * @param non-empty-string $command
     *
     * @internal this is an internal library method, please do not use it in your code
     * @psalm-internal Serafim\Vulkan
     */
    public function withProcedure(string $command, callable $procedure): static
    {
        $self = clone $this;
        $self->procedures[$command] = $procedure;

        return $self;
    }

    /**
     * @param non-empty-string $command
     *
     * @throws CommandNotAvailableException in case of the command is not
     *         supported
     */
    private function findInstanceProc(string $command, ?Handle $handle): callable
    {
        $root = $handle;

        while ($root?->parent !== null) {
            $root = $root->parent;
        }

        // @phpstan-ignore-next-line the function is declared by the headers of the library
        $pointer = $this->ffi->vkGetInstanceProcAddr($root?->pointer, $command);

        \assert($pointer === null || $pointer instanceof CData);

        if ($pointer === null || \FFI::isNull($pointer)) {
            throw CommandNotAvailableException::becauseCommandNotAvailable($command);
        }

        // @phpstan-ignore-next-line a pointer to a function is callable
        return $this->ffi->cast('PFN_' . $command, $pointer);
    }
}
