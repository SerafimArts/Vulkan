<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Tests\Struct;

use PHPUnit\Framework\Attributes\CoversNothing;
use Serafim\Vulkan\Build\Generator\Quirks;
use Serafim\Vulkan\Exception\InvalidStructureException;
use Serafim\Vulkan\Exception\UnknownEnumValueException;
use Serafim\Vulkan\Struct\CountedStructureInterface;
use Serafim\Vulkan\Struct\InputStructureInterface;
use Serafim\Vulkan\Struct\OutputStructureInterface;
use Serafim\Vulkan\Tests\TestCase;

#[CoversNothing]
final class GeneratedStructuresTest extends TestCase
{
    public function testStructuresWithoutRequiredArgumentsAreWrittenToNativeMemory(): void
    {
        $vk = self::loadVulkan();
        $failed = [];

        foreach ($this->findClasses(InputStructureInterface::class) as $class => $type) {
            $constructor = new \ReflectionClass($class)->getConstructor();

            if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            try {
                $structure = new $class();

                \assert($structure instanceof InputStructureInterface);

                $structure->toCData($vk->memory);
            } catch (InvalidStructureException) {
                // A union has exactly one member, there is none in a new one.
            } catch (\Throwable $e) {
                if (!$this->isDeclared($vk->ffi, $type)) {
                    continue;
                }

                $failed[$class] = $e::class . ': ' . $e->getMessage();
            }
        }

        self::assertSame([], $failed);
    }

    public function testReturnedStructuresAreReadFromZeroedNativeMemory(): void
    {
        $vk = self::loadVulkan();
        $failed = [];

        foreach ($this->findClasses(OutputStructureInterface::class) as $class => $type) {
            if (!$this->isDeclared($vk->ffi, $type)) {
                continue;
            }

            $arguments = [$vk->memory->new($type)];

            if (new \ReflectionMethod($class, 'createFromCData')->getNumberOfParameters() > 2) {
                $arguments['vk'] = $vk;
            }

            try {
                $class::createFromCData(...$arguments);
            } catch (UnknownEnumValueException) {
                // The memory has no valid value of an enum, the zero is not
                // one of them.
            } catch (\Throwable $e) {
                $failed[$class] = $e::class . ': ' . $e->getMessage();
            }
        }

        self::assertSame([], $failed);
    }

    public function testCountedStructuresAreReadFromZeroedNativeMemory(): void
    {
        $vk = self::loadVulkan();
        $failed = [];

        foreach ($this->findClasses(CountedStructureInterface::class) as $class => $type) {
            if (!$this->isDeclared($vk->ffi, $type)) {
                continue;
            }

            $counts = Quirks::findCounts($type);

            self::assertNotNull($counts);

            try {
                $class::createFromCData($vk->memory->new($type), $vk->memory->new($counts[0]));
            } catch (\Throwable $e) {
                $failed[$class] = $e::class . ': ' . $e->getMessage();
            }
        }

        self::assertSame([], $failed);
    }

    /**
     * @param class-string $base
     *
     * @return array<class-string, string> the names of the C types by the classes
     */
    private function findClasses(string $base): array
    {
        $classes = [];

        $files = \glob(__DIR__ . '/../../src/Struct/Vk*.php');

        if ($files === false) {
            return $classes;
        }

        foreach ($files as $file) {
            $type = \basename($file, '.php');
            $class = 'Serafim\\Vulkan\\Struct\\' . $type;

            if (\is_subclass_of($class, $base)) {
                $classes[$class] = $type;
            }
        }

        return $classes;
    }

    private function isDeclared(\FFI $ffi, string $type): bool
    {
        try {
            $ffi->type($type);

            return true;
        } catch (\FFI\ParserException) {
            return false;
        }
    }
}
