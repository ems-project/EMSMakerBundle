<?php

declare(strict_types=1);

namespace EMS\MakerBundle\Command;

use FOS\UserBundle\Util\UserManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MakeCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'ems:maker:make';
    private UserManipulator $userManipulator;

    public function __construct(UserManipulator $userManipulator)
    {
        parent::__construct();
        $this->userManipulator = $userManipulator;

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}