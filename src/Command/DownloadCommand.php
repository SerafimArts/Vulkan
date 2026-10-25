<?php

declare(strict_types=1);

namespace Serafim\Vulkan\Command;

use Serafim\Vulkan\Command\Download\HeadersDownloader;
use Serafim\Vulkan\Loader\Exception\InvalidHeadersException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Downloads the Vulkan headers and the registry from the Khronos repository
 * into the sources of the library.
 */
#[AsCommand('download', 'Downloads the Vulkan headers and the registry into resources/src')]
final class DownloadCommand extends Command
{
    public function __construct(
        /**
         * Place the downloaded sources are written to
         */
        private readonly HeadersDownloader $downloader = new HeadersDownloader(
            __DIR__ . '/../../resources/src',
        ),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'version',
            mode: InputArgument::OPTIONAL,
            description: 'Version of the headers (like 1.4.366), the main branch by default',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var non-empty-string|null $version */
        $version = $input->getArgument('version');

        $output->writeln(\sprintf('Downloading %s', $this->downloader->getArchiveUrl($version)));

        try {
            $this->downloader->download($version);
        } catch (InvalidHeadersException $e) {
            $output->writeln(\sprintf('<error>%s</error>', $e->getMessage()));

            return self::FAILURE;
        }

        $output->writeln('<info>Done</info>');

        return self::SUCCESS;
    }
}
