<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator\Emitter\CommandsEmitter;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Serafim\Vulkan\Build\Generator\Analysis\CommandPlan;
use Serafim\Vulkan\Build\Generator\Exception\UnsupportedDeclarationException;

/**
 * The body of a generated command and its return type.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
interface Body
{
    /**
     * @return list<string> the lines of the body
     * @throws UnsupportedDeclarationException in case of the command cannot
     *         be written
     */
    public function emit(PhpNamespace $namespace, Method $method, CommandPlan $plan): array;
}
