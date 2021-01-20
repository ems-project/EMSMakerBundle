<?php

namespace EMS\MakerBundle\Command;

use Symfony\Component\Console\Command\Command;

class AnalyzerCommand extends Command
{
    protected static $defaultName = 'ems:make:analyzer';

    /**
     * create an analyzer based on a selection (input) in the Resource/make/analyzer folder.
     * --help should include the list of files in that folder
     *
     * Pass the desired languages to the command (default EN only) (--all ?)
     */
}
