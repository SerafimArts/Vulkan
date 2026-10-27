<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Command;

use Serafim\Vulkan\Build\Generator\CodeFormatter;
use Serafim\Vulkan\Build\Generator\CodeGenerator;
use Serafim\Vulkan\Build\Generator\Exception\GeneratorException;
use Serafim\Vulkan\Build\Generator\FilesWriter;
use Serafim\Vulkan\Build\Generator\Registry\RegistryParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates the PHP code of the Vulkan API from the registry of the
 * specification. Downloads the sources first if they are missing.
 */
#[AsCommand('generate', 'Generates the PHP code of the Vulkan API from vk.xml')]
final class GenerateCommand extends Command
{
    public function __construct(
        /**
         * Directory with the sources of the library the code is written to
         *
         * @var non-empty-string
         */
        private readonly string $directory = __DIR__ . '/../..',
        /**
         * Directory with the downloaded sources
         *
         * @var non-empty-string
         */
        private readonly string $sources = __DIR__ . '/../../../resources/src',
        /**
         * Root directory of the project
         *
         * @var non-empty-string
         */
        private readonly string $root = __DIR__ . '/../../..',
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $registry = $this->sources . '/registry/vk.xml';

        if (!\is_file($registry)) {
            $output->writeln('The registry is not downloaded, running the download command');

            $status = $this->download($output);

            if ($status !== self::SUCCESS) {
                return $status;
            }
        }

        try {
            $result = new CodeGenerator()->generate(new RegistryParser()->parse($registry));
            $files = $result->files;

            new FilesWriter($this->directory)->write($files);
            new CodeFormatter($this->root)->format();
        } catch (GeneratorException $e) {
            $output->writeln(\sprintf('<error>%s</error>', $e->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(\sprintf('<info>%d files generated</info>', \count($files)));
        $output->writeln(\sprintf('%d commands are left out, run with -v to list them', \count($result->skipped)));

        foreach ($result->skipped as $command => $reason) {
            $output->writeln(\sprintf('%s: %s', $command, $reason), OutputInterface::VERBOSITY_VERBOSE);
        }

        return self::SUCCESS;
    }

    private function download(OutputInterface $output): int
    {
        $command = $this->getApplication()?->find('download');

        if ($command === null) {
            $output->writeln('<error>The download command is not available</error>');

            return self::FAILURE;
        }

        return $command->run(new ArrayInput([]), $output);
    }
}
