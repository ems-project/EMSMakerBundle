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

    public function __construct(
        UserCommand $userCommand,
        EnvironmentCommand $environmentCommand,
        DocumentationCommand $documentationCommand,
        ContentTypeCommand $contentTypeCommand
    ) {
        parent::__construct();
        $this->userCommand = $userCommand;
        $this->environmentCommand = $environmentCommand;
        $this->documentationCommand = $documentationCommand;
        $this->contentTypeCommand = $contentTypeCommand;
    }


    protected function configure(string $defaultConfig = null)
    {
        if (null === $defaultConfig) {
            $defaultConfig = FileService::JSON_FILES . 'demo.json';
        }
        parent::configure($defaultConfig);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->userCommand->initialize($input, $output);
        $this->userCommand->makeUsers($this->config[AbstractCommand::USERS]);
        $this->environmentCommand->initialize($input, $output);
        $this->environmentCommand->makeEnvironments($this->config[AbstractCommand::ENVIRONMENTS]);
        $this->documentationCommand->initialize($input, $output);
        $this->documentationCommand->makeDocumentations($this->config[AbstractCommand::DOCUMENTATIONS]);
        $this->contentTypeCommand->initialize($input, $output);
        $this->contentTypeCommand->makeContentTypes($this->config[AbstractCommand::CONTENTTYPES]);

        return 0;
    }
}
