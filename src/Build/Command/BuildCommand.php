<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Build\Command;

use Serafim\Vulkan\Build\Command\BuildCommand\BitFieldsPacker;
use Serafim\Vulkan\Build\Command\BuildCommand\HeadersSplitter;
use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Serafim\Vulkan\Loader\Headers\HeadersLoader;
use Serafim\Vulkan\Loader\Headers\HeadersLoaderInterface;
use Serafim\Vulkan\Loader\Headers\PreprocessorHeadersLoader;
use Serafim\Vulkan\Loader\Platform\Platform;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds the Vulkan headers of all the platforms into the C sources that are
 * loaded by the library at runtime: the declarations that all the platforms
 * share are written once, a platform has only its own part. Downloads the
 * sources first if they are missing.
 */
#[AsCommand('build', 'Builds the Vulkan headers of all the platforms')]
final class BuildCommand extends Command
{
    public function __construct(
        /**
         * Source of the headers being built
         */
        private readonly HeadersLoaderInterface $headers = new PreprocessorHeadersLoader(),
        /**
         * Directory the built headers are written to
         *
         * @var non-empty-string
         */
        private readonly string $output = __DIR__ . '/../../../resources/headers',
        /**
         * Directory the sources are downloaded to
         *
         * @var non-empty-string
         */
        private readonly string $sources = __DIR__ . '/../../../resources/src',
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->hasSources()) {
            $output->writeln('The sources are not downloaded, running the download command');

            $status = $this->download($output);

            if ($status !== self::SUCCESS) {
                return $status;
            }
        }

        try {
            $this->createOutputDirectory();
        } catch (InvalidHeadersException $e) {
            $output->writeln(\sprintf('<error>%s</error>', $e->getMessage()));

            return self::FAILURE;
        }

        $status = self::SUCCESS;
        $headers = [];

        foreach (Platform::cases() as $platform) {
            try {
                $headers[$platform->name] = new BitFieldsPacker()->pack($this->headers->getHeaders($platform));
            } catch (InvalidHeadersException $e) {
                $output->writeln(\sprintf('<error>%s: %s</error>', $platform->name, $e->getMessage()));
                $status = self::FAILURE;
            }
        }

        if ($headers === [] || $status !== self::SUCCESS) {
            return self::FAILURE;
        }

        [$shared, $tails] = new HeadersSplitter()->split($headers);

        try {
            $size = $this->write(HeadersLoader::getCoreFilename(), $shared);

            $output->writeln(\sprintf('<info>shared</info>: %s (%d bytes)', HeadersLoader::getCoreFilename(), $size));

            foreach (Platform::cases() as $platform) {
                $filename = HeadersLoader::getFilename($platform);
                $size = $this->write($filename, $tails[$platform->name]);

                $output->writeln(\sprintf('<info>%s</info>: %s (%d bytes)', $platform->name, $filename, $size));
            }
        } catch (InvalidHeadersException $e) {
            $output->writeln(\sprintf('<error>%s</error>', $e->getMessage()));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @throws InvalidHeadersException in case of the directory cannot be
     *         created
     */
    private function createOutputDirectory(): void
    {
        if (\is_dir($this->output) || \mkdir($this->output, recursive: true)) {
            return;
        }

        throw InvalidHeadersException::becauseHeadersNotWritable($this->output);
    }

    /**
     * Writes the file and returns its size in bytes.
     *
     * @return int<0, max>
     * @throws InvalidHeadersException in case of the file cannot be written
     */
    private function write(string $filename, string $contents): int
    {
        $pathname = $this->output . '/' . $filename;

        $size = \file_put_contents($pathname, $contents);

        if ($size === false) {
            throw InvalidHeadersException::becauseHeadersNotWritable($pathname);
        }

        return $size;
    }

    private function hasSources(): bool
    {
        return \is_file($this->sources . '/include/vulkan/vulkan.h');
    }

    private function download(OutputInterface $output): int
    {
        $command = $this->getApplication()
            ?->find('download');

        if ($command === null) {
            $output->writeln('<error>The download command is not available</error>');

            return self::FAILURE;
        }

        return $command->run(new ArrayInput([]), $output);
    }
}
