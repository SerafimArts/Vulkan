<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter;

use FFI\CData;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\TraitType;
use Serafim\Vulkan\Build\Generator\Analysis\CommandKind;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Analysis\TypeResolver;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\CountedBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\CreateArrayBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\CreateBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\CreateHandlesBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\EnumerateBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\ExecuteBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\MultipleBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\PointerQueryBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\QueryArrayBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\QueryBinaryBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\QueryBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Body\ScalarQueryBody;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Calls;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\HandleFactory;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Parameters;
use Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter\Structures;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;
use Serafim\Vulkan\Build\Generator\Registry\Registry;
use Serafim\Vulkan\Enum\VkResult;
use Serafim\Vulkan\Exception\CommandFailedException;
use Serafim\Vulkan\Exception\VulkanException;
use Serafim\Vulkan\VulkanBase;

/**
 * Writes the commands of the specification as the methods of
 * {@see \Serafim\Vulkan\Vulkan}.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CommandsEmitter
{
    /**
     * Errors that every command may return.
     */
    private const array COMMON_ERRORS = [
        'VK_ERROR_OUT_OF_HOST_MEMORY',
        'VK_ERROR_OUT_OF_DEVICE_MEMORY',
        'VK_ERROR_UNKNOWN',
        'VK_ERROR_VALIDATION_FAILED',
    ];

    private PhpFiles $files;

    private Parameters $parameters;

    private Calls $calls;

    private HandleFactory $handles;

    private SpecificationTags $tags;

    public function __construct(
        private Registry $registry,
        TypeResolver $types,
        private Structures $structures = new Structures(),
        private MetaRecorder $meta = new MetaRecorder(),
    ) {
        $this->files = new PhpFiles();
        $this->tags = new SpecificationTags($registry);
        $this->parameters = new Parameters($registry, $types, $this->meta);
        $this->calls = new Calls();
        $this->handles = new HandleFactory($registry, $this->calls);
    }

    /**
     * Writes the commands as the traits of the objects they are called on and
     * the class that uses them.
     *
     * @param list<CommandPlan> $commands
     *
     * @return list<GeneratedFile>
     * @throws UnsupportedDeclarationException in case of a command cannot be
     *         written
     */
    public function emit(array $commands): array
    {
        \usort($commands, static fn(CommandPlan $a, CommandPlan $b): int => $a->command->name <=> $b->command->name);

        $groups = [];

        foreach ($commands as $command) {
            $groups[$this->findGroup($command)][] = $command;
        }

        \ksort($groups);

        $files = [];

        foreach ($groups as $group => $plans) {
            $files[] = $this->emitGroup($group, $plans);
        }

        $files[] = $this->emitClass(\array_keys($groups));

        return $files;
    }

    /**
     * Returns the name of the trait of the commands that are called on the
     * object of the first handle of the command.
     *
     * @return non-empty-string
     */
    private function findGroup(CommandPlan $plan): string
    {
        $handle = $this->calls->findParent($plan);

        return match ($handle?->field->type) {
            'VkPhysicalDevice' => 'PhysicalDeviceCommands',
            'VkDevice' => 'DeviceCommands',
            'VkQueue' => 'QueueCommands',
            'VkCommandBuffer' => 'CommandBufferCommands',
            default => 'InstanceCommands',
        };
    }

    /**
     * @param non-empty-string $group
     * @param list<CommandPlan> $commands
     *
     * @throws UnsupportedDeclarationException
     */
    private function emitGroup(string $group, array $commands): GeneratedFile
    {
        $file = $this->files->create();
        $namespace = $file->addNamespace(Locations::COMMANDS_NAMESPACE);

        $namespace->addUse(VulkanBase::class);
        $namespace->addUse(CData::class);
        $namespace->addUse(VulkanException::class);

        $trait = $namespace->addTrait($group);

        $trait->addComment('The commands of the Vulkan API that are called on one kind of the objects.');
        $trait->addComment('');
        $trait->addComment('@phpstan-require-extends VulkanBase');

        foreach ($commands as $command) {
            $method = $this->emitCommand($namespace, $trait, $command);

            foreach ($this->registry->findAliases($command->command->name) as $alias) {
                $this->emitAlias($trait, $method, $alias);
            }
        }

        return new GeneratedFile(Locations::createPath('Commands', $group), $this->files->print($file));
    }

    /**
     * @param list<non-empty-string> $groups
     */
    private function emitClass(array $groups): GeneratedFile
    {
        $file = $this->files->create();
        $namespace = $file->addNamespace(Locations::ROOT_NAMESPACE);

        $namespace->addUse(VulkanBase::class);

        $class = $namespace->addClass('Vulkan');

        $class->setFinal();
        $class->setExtends(VulkanBase::class);
        $class->addComment('The commands of the Vulkan API.');

        foreach ($groups as $group) {
            $class->addTrait(Locations::createClassName(Locations::COMMANDS_NAMESPACE, $group));
        }

        return new GeneratedFile('Vulkan.php', $this->files->print($file));
    }

    /**
     * @throws UnsupportedDeclarationException
     */
    private function emitCommand(PhpNamespace $namespace, TraitType $class, CommandPlan $plan): Method
    {
        $name = $plan->command->name;
        $method = $class->addMethod($name);
        $description = [...$this->tags->createDescription($name), ...$this->createRecordingDescription($plan)];
        $deprecation = $this->tags->findDeprecation($name);

        $method->setPublic();

        foreach ($description as $line) {
            $method->addComment($line);
        }

        if ($description !== []) {
            $method->addComment('');
        }

        foreach ($this->tags->createLines($name) as $line) {
            $method->addComment($line);
        }

        if ($deprecation !== null) {
            $method->addAttribute(\Deprecated::class, ['message' => $deprecation->description]);
        }

        $method->addComment('');
        $method->addComment('@api');

        $this->parameters->emit($namespace, $method, $plan);

        if ($plan->returnsResult) {
            $namespace->addUse(CommandFailedException::class);

            $method->addComment('');

            foreach ($this->createThrows($namespace, $plan) as $line) {
                $method->addComment($line);
            }
        }

        $method->addComment('@throws VulkanException in case of internal error occurs');

        $lines = $this->createBody($plan->kind)->emit($namespace, $method, $plan);

        $method->setBody(\implode("\n", $lines));

        return $method;
    }

    /**
     * Writes the command under another name: the method calls the command.
     */
    private function emitAlias(TraitType $class, Method $command, string $name): void
    {
        $method = $command->cloneWithName($name);

        $this->meta->copyMethod(Locations::createClassName(Locations::ROOT_NAMESPACE, 'Vulkan'), $command->getName(), $name);
        $deprecation = $this->tags->findDeprecation($name);
        $comment = \explode("\n", (string) $command->getComment());
        $lines = ['Alias of {@see self::' . $command->getName() . '()}.', '', ...$this->tags->createLines($name)];

        $method->setComment(null);
        $method->setAttributes([]);

        // The alias is deprecated as the command is, unless the registry tells its own reason.
        if ($deprecation === null) {
            $method->setAttributes($command->getAttributes());
        } else {
            $method->addAttribute(\Deprecated::class, ['message' => $deprecation->description]);
        }

        if ($this->registry->findDeprecation($name) === null) {
            foreach ($comment as $line) {
                if (\str_starts_with($line, '@deprecated')) {
                    $lines[] = $line;
                }
            }
        }

        // The tags that describe the parameters and the result are the same.
        $offset = \array_search('@api', $comment, true);

        if ($offset === false) {
            $offset = 0;
        }

        \array_push($lines, '', ...\array_slice($comment, $offset));

        foreach ($lines as $line) {
            $method->addComment($line);
        }

        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = '$' . $parameter->getName();
        }

        $call = '$this->' . $command->getName() . '(' . \implode(', ', $arguments) . ');';

        if ($method->getReturnType() === 'void') {
            $method->setBody($call);
        } else {
            $method->setBody('return ' . $call);
        }

        $class->addMember($method);
    }

    /**
     * Returns the lines of the `@throws` tag: the errors that every command
     * may return are not listed.
     *
     * @return list<string>
     */
    private function createThrows(PhpNamespace $namespace, CommandPlan $plan): array
    {
        $codes = \array_diff($plan->command->errorCodes, self::COMMON_ERRORS);

        if ($codes === []) {
            return ['@throws CommandFailedException in case of the command fails'];
        }

        $namespace->addUse(VkResult::class);

        $references = [];

        foreach ($codes as $code) {
            // A reference stays in one piece when the text is wrapped: its space is a placeholder.
            $references[] = '{@see#VkResult::' . $code . '}';
        }

        $text = '@throws CommandFailedException in case of the command fails, for example with '
            . \implode(', ', $references);
        $lines = \explode("\n", \str_replace('#', ' ', \wordwrap($text, 72)));

        foreach ($lines as $i => $line) {
            if ($i > 0) {
                $lines[$i] = '        ' . $line;
            }
        }

        return $lines;
    }

    /**
     * Tells where a command of a command buffer can be recorded.
     *
     * @return list<string>
     */
    private function createRecordingDescription(CommandPlan $plan): array
    {
        $command = $plan->command;
        $sentences = [];

        if ($command->queues !== []) {
            $queues = [];

            foreach ($command->queues as $queue) {
                $queues[] = \strtolower(\str_replace(['VK_QUEUE_', '_BIT'], '', $queue));
            }

            $sentences[] = 'Queues: ' . \implode(', ', $queues) . '.';
        }

        if ($command->renderPass !== null) {
            $sentences[] = 'Render pass: ' . $command->renderPass . '.';
        }

        if ($command->levels !== []) {
            $sentences[] = 'Command buffer level: ' . \implode(', ', $command->levels) . '.';
        }

        if ($sentences === []) {
            return [];
        }

        return DocComments::createLines(\implode(' ', $sentences));
    }

    private function createBody(CommandKind $kind): Body
    {
        return match ($kind) {
            CommandKind::CREATE => new CreateBody($this->calls, $this->handles),
            CommandKind::CREATE_ARRAY => new CreateArrayBody($this->calls, $this->handles),
            CommandKind::CREATE_HANDLES => new CreateHandlesBody($this->calls, $this->handles, $this->registry),
            CommandKind::QUERY => new QueryBody($this->calls, $this->registry, $this->structures),
            CommandKind::QUERY_SCALAR => new ScalarQueryBody($this->calls),
            CommandKind::QUERY_BINARY => new QueryBinaryBody($this->calls),
            CommandKind::QUERY_ARRAY => new QueryArrayBody($this->calls, $this->registry, $this->structures),
            CommandKind::QUERY_MULTIPLE => new MultipleBody(
                $this->calls,
                $this->registry,
                $this->structures,
                new EnumerateBody($this->calls, $this->registry, $this->structures),
                new QueryArrayBody($this->calls, $this->registry, $this->structures),
            ),
            CommandKind::QUERY_POINTER => new PointerQueryBody($this->calls),
            CommandKind::ENUMERATE => new EnumerateBody($this->calls, $this->registry, $this->structures),
            CommandKind::EXECUTE => new ExecuteBody($this->calls),
            CommandKind::QUERY_COUNTED => new CountedBody($this->calls, $this->registry),
        };
    }
}
