<?php

namespace EMS\MakerBundle\Command;

use EMS\MakerBundle\Service\FileService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DemoCommand extends AbstractCommand
{
    protected static $defaultName = 'ems:maker:demo';

    private UserCommand $userCommand;
    private EnvironmentCommand $environmentCommand;
    private DocumentationCommand $documentationCommand;
    private ContentTypeCommand $contentTypeCommand;
    private AnalyzerCommand $analyzerCommand;
    private FilterCommand $filterCommand;
    private RevisionCommand $revisionCommand;

    public function __construct(
        UserCommand $userCommand,
        FilterCommand $filterCommand,
        AnalyzerCommand $analyzerCommand,
        EnvironmentCommand $environmentCommand,
        DocumentationCommand $documentationCommand,
        ContentTypeCommand $contentTypeCommand,
        RevisionCommand $revisionCommand
    ) {
        parent::__construct();
        $this->userCommand = $userCommand;
        $this->environmentCommand = $environmentCommand;
        $this->analyzerCommand = $analyzerCommand;
        $this->filterCommand = $filterCommand;
        $this->documentationCommand = $documentationCommand;
        $this->contentTypeCommand = $contentTypeCommand;
        $this->revisionCommand = $revisionCommand;
    }

    protected function configure(string $defaultConfig = null): void
    {
        if (null === $defaultConfig) {
            $defaultConfig = FileService::JSON_FILES.'demo.json';
        }
        parent::configure($defaultConfig);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->userCommand->initialize($input, $output);
        $this->userCommand->makeUsers($this->config[AbstractCommand::USERS]);
        $this->filterCommand->initialize($input, $output);
        $this->filterCommand->makeFilters($this->config[AbstractCommand::FILTERS]);
        $this->analyzerCommand->initialize($input, $output);
        $this->analyzerCommand->makeAnalyzers($this->config[AbstractCommand::ANALYZERS]);
        $this->environmentCommand->initialize($input, $output);
        $this->environmentCommand->makeEnvironments($this->config[AbstractCommand::ENVIRONMENTS]);
        $this->contentTypeCommand->initialize($input, $output);
        $this->contentTypeCommand->makeContentTypes($this->config[AbstractCommand::CONTENTTYPES]);
        $this->documentationCommand->initialize($input, $output);
        $this->documentationCommand->indexDocumentations($this->config[AbstractCommand::DOCUMENTATIONS]);
        $this->revisionCommand->initialize($input, $output);
        $this->revisionCommand->makeRevisions($this->config[AbstractCommand::REVISIONS]);

        return 0;
    }
}
