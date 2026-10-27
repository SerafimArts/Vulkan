<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Generator;

use Serafim\Vulkan\Build\Generator\Exception\InvalidOutputException;
use Symfony\Component\Process\Process;

/**
 * Brings the generated files to the code style of the project.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Serafim\Vulkan\Build
 */
final readonly class CodeFormatter
{
    public function __construct(
        /**
         * Root directory of the project with `vendor` and the configuration
         *
         * @var non-empty-string
         */
        private string $root,
    ) {}

    /**
     * @throws InvalidOutputException in case of the files cannot be formatted
     */
    public function format(): void
    {
        $process = new Process([
            \PHP_BINARY,
            $this->root . '/vendor/bin/php-cs-fixer',
            'fix',
            '--config=' . $this->root . '/.php-cs-fixer.php',
            '--allow-risky=yes',
            '--quiet',
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw InvalidOutputException::becauseFilesNotFormatted($process->getErrorOutput() . $process->getOutput());
        }
    }
}
