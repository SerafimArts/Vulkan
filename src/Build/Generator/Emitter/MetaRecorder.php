<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

/**
 * What the IDE needs to know about the generated code: the arguments that are
 * the bits of flags and the structures that return a structure of their chain.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final class MetaRecorder
{
    /**
     * The types of the bits of the arguments by the class, the method and the
     * index of the argument.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private array $arguments = [];

    /**
     * @var list<string>
     */
    private array $finders = [];

    /**
     * Records that the argument is made of the bits.
     *
     * @param int<0, max> $index
     * @param non-empty-string $bits the name of the type of the bits
     */
    public function recordArgument(string $class, string $method, int $index, string $bits): void
    {
        $this->arguments[$class][$method][$index] = $bits;
    }

    /**
     * Records that the method does the same as another one of the class.
     */
    public function copyMethod(string $class, string $from, string $to): void
    {
        if (isset($this->arguments[$class][$from])) {
            $this->arguments[$class][$to] = $this->arguments[$class][$from];
        }
    }

    /**
     * Records that the class finds the structure of its chain by the class.
     */
    public function recordFinder(string $class): void
    {
        $this->finders[] = $class;
    }

    /**
     * @return array<string, array<string, array<int, string>>>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return list<string>
     */
    public function getFinders(): array
    {
        return $this->finders;
    }
}
