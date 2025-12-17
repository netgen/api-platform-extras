<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\Command;

use Netgen\ApiPlatformExtras\Service\IriTemplatesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

use function count;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

#[AsCommand(
    name: 'api-platform-extras:generate-iri-templates',
    description: 'Generate IRI templates and write them to a JSON file',
)]
final class GenerateIriTemplatesCommand extends Command
{
    public function __construct(
        private readonly IriTemplatesService $iriTemplatesService,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'The output JSON file path',
            )
            ->setHelp(
                <<<'HELP'
                The <info>%command.name%</info> command generates IRI templates from all API Platform resources
                and writes them to the specified JSON file.

                  <info>php %command.full_name% output.json</info>
                  <info>php %command.full_name% /path/to/iri-templates.json</info>
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $outputPath = $input->getArgument('output');

        try {
            $iriTemplates = $this->iriTemplatesService->getIriTemplatesData();
        } catch (\Exception $e) {
            $io->error(sprintf('Failed to generate IRI templates: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $content = false;

        try {
            $content = json_encode($iriTemplates, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException) {
        }

        if ($content === false) {
            $io->error('Failed to encode IRI templates to JSON');

            return Command::FAILURE;
        }

        try {
            $this->filesystem->dumpFile($outputPath, $content);
            $io->success(sprintf('IRI templates written to %s', $outputPath));
            $io->info(sprintf('Generated %d IRI templates', count($iriTemplates)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Failed to write file: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
