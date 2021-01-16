<?php

namespace EMS\MakerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DemoCommand extends AbstractCommand
{
    protected static $defaultName = 'ems:maker:demo';

    private UserCommand $userCommand;

    public function __construct(UserCommand $userCommand)
    {
        parent::__construct();
        $this->userCommand = $userCommand;
    }


    protected function configure(string $defaultConfig = null)
    {
        if (null === $defaultConfig) {
            $defaultConfig = 'vendor/elasticms/maker-bundle/Resources/make/demo.json';
        }
        parent::configure($defaultConfig);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->userCommand->initialize($input, $output);
        $this->userCommand->makeUsers($this->config['users']);

        return 0;
    }
}
