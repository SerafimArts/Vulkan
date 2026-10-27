<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use Serafim\Vulkan\Build\Generator\Registry\HandleDefinition;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Handle\Handle;

/**
 * Writes a handle of the specification as a class.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class HandleEmitter
{
    private PhpFiles $files;

    private SpecificationTags $tags;

    public function __construct(Registry $registry)
    {
        $this->files = new PhpFiles();
        $this->tags = new SpecificationTags($registry);
    }

    public function emit(HandleDefinition $definition): GeneratedFile
    {
        $file = $this->files->create();
        $namespace = $file->addNamespace(Locations::HANDLE_NAMESPACE);

        $namespace->addUse(Handle::class);

        $class = $namespace->addClass($definition->name);

        $class->setFinal();
        $class->setReadOnly();
        $class->setExtends(Handle::class);

        foreach ($this->tags->createLines($definition->name) as $line) {
            $class->addComment($line);
        }

        return new GeneratedFile(
            Locations::createPath('Handle', $definition->name),
            $this->files->print($file),
        );
    }
}
