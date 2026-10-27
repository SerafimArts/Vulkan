<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use Nette\PhpGenerator\Literal;
use Serafim\Vulkan\Build\Generator\Registry\EnumDefinition;
use Serafim\Vulkan\Build\Generator\Registry\Registry;

/**
 * Writes an enum of the specification as a backed enum. The cases that
 * repeat the value of another case and the aliases become constants. The bits
 * of flags are not an enumeration, they are the constants of a class.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class EnumEmitter
{
    private PhpFiles $files;

    private SpecificationTags $tags;

    public function __construct(Registry $registry)
    {
        $this->files = new PhpFiles();
        $this->tags = new SpecificationTags($registry);
    }

    public function emit(EnumDefinition $definition): GeneratedFile
    {
        if ($definition->isFlags) {
            return $this->emitFlags($definition);
        }

        $file = $this->files->create();
        $namespace = $file->addNamespace(Locations::ENUM_NAMESPACE);

        $enum = $namespace->addEnum($definition->name);

        $enum->setType('int');

        foreach ($this->tags->createLines($definition->name) as $line) {
            $enum->addComment($line);
        }

        /** @var array<int, non-empty-string> $cases names of the cases by values */
        $cases = [];
        /** @var array<non-empty-string, non-empty-string> $constants */
        $constants = [];

        foreach ($definition->cases as $name => $value) {
            if (isset($cases[$value])) {
                $constants[$name] = $cases[$value];

                continue;
            }

            $cases[$value] = $name;

            $case = $enum->addCase($name, $value);

            if (isset($definition->deprecated[$name])) {
                $case->addAttribute(\Deprecated::class);
            }

            foreach (DocComments::createLines($definition->comments[$name] ?? '') as $line) {
                $case->addComment($line);
            }
        }

        foreach ($definition->aliases as $alias => $target) {
            $case = $this->findCase($definition, $cases, $target);

            if ($case !== null) {
                $constants[$alias] = $case;
            }
        }

        foreach ($constants as $name => $case) {
            $constant = $enum->addConstant($name, new Literal('self::' . $case));

            $constant->setType('self');
            $constant->addComment('Alias of {@see self::' . $case . '}');

            if (isset($definition->deprecated[$name])) {
                $constant->addAttribute(\Deprecated::class);
            }
        }

        return new GeneratedFile(
            Locations::createPath('Enum', $definition->name),
            $this->files->print($file),
        );
    }

    private function emitFlags(EnumDefinition $definition): GeneratedFile
    {
        $file = $this->files->create();
        $namespace = $file->addNamespace(Locations::ENUM_NAMESPACE);

        $class = $namespace->addClass($definition->name);

        $class->setFinal();

        foreach ($this->tags->createLines($definition->name) as $line) {
            $class->addComment($line);
        }

        $class->addMethod('__construct')->setPrivate();

        foreach ($definition->cases as $name => $value) {
            $constant = $class->addConstant($name, $value);

            $constant->setType('int');

            if (isset($definition->deprecated[$name])) {
                $constant->addAttribute(\Deprecated::class);
            }

            foreach (DocComments::createLines($definition->comments[$name] ?? '') as $line) {
                $constant->addComment($line);
            }
        }

        foreach ($definition->aliases as $alias => $target) {
            if (!isset($definition->cases[$target]) && !isset($definition->aliases[$target])) {
                continue;
            }

            $constant = $class->addConstant($alias, new Literal('self::' . $target));

            $constant->setType('int');
            $constant->addComment('Alias of {@see self::' . $target . '}');

            if (isset($definition->deprecated[$alias])) {
                $constant->addAttribute(\Deprecated::class);
            }
        }

        return new GeneratedFile(
            Locations::createPath('Enum', $definition->name),
            $this->files->print($file),
        );
    }

    /**
     * @param array<int, non-empty-string> $cases names of the cases by values
     *
     * @return non-empty-string|null
     */
    private function findCase(EnumDefinition $definition, array $cases, string $name): ?string
    {
        for ($depth = 0; $depth < 8; ++$depth) {
            if (isset($definition->cases[$name])) {
                return $cases[$definition->cases[$name]];
            }

            if (!isset($definition->aliases[$name])) {
                return null;
            }

            $name = $definition->aliases[$name];
        }

        return null;
    }
}
