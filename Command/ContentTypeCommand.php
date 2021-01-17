<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Entity\ContentType;
use EMS\CoreBundle\Entity\Environment;
use EMS\CoreBundle\Service\EnvironmentService;
use EMS\CoreBundle\Service\ContentTypeService;
use EMS\MakerBundle\Service\FileService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContentTypeCommand extends Command
{
    protected static $defaultName = 'ems:maker:contenttype';

    private EnvironmentService $environmentService;
    private ContentTypeService $contentTypeService;
    private FileService $fileService;
    private SymfonyStyle $io;
    private Environment $environment;

    private const ARGUMENT_CONTENTTYPES = 'contenttypes';
    private const OPTION_ALL = 'all';
    private const OPTION_FORCE = 'force';
    private const OPTION_ENV = 'environment';
    private bool $force = false;

    public function __construct(EnvironmentService $environmentService, ContentTypeService $contentTypeService, FileService $fileService)
    {
        $this->environmentService = $environmentService;
        $this->contentTypeService = $contentTypeService;
        $this->fileService = $fileService;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $fileNames = implode(', ', $this->fileService->getFileNames(FileService::TYPE_CONTENTTYPE));
        $this
            ->addArgument(
                self::ARGUMENT_CONTENTTYPES,
                InputArgument::IS_ARRAY,
                sprintf('Optional array of contenttypes to create. Allowed values: [%s]', $fileNames)
            )
            ->addOption(
                'environment',
                null,
                InputOption::VALUE_REQUIRED,
                'Default environment for the contenttypes',
                'preview'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                sprintf('Make all contenttypes: [%s]', $fileNames)
            );
        $this->addOption(
            self::OPTION_FORCE,
            null,
            InputOption::VALUE_NONE,
            'If set, items all ready defined will be overridden without question'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var array $types */
        $types = $input->getArgument(self::ARGUMENT_CONTENTTYPES);

        foreach ($types as $type) {
            $this->updateContentType($type, $this->environment, true, true);
        }
    }


    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->force = $input->getOption(self::OPTION_FORCE) === true;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Make contenttypes');
        $this->io->section('Checking input');

        /** @var array $types */
        $types = $input->getArgument(self::ARGUMENT_CONTENTTYPES);

        if (!$input->getOption(self::OPTION_ALL) && count($types) == 0) {
            $this->chooseTypes($input, $output);
        }

        if ($input->getOption(self::OPTION_ALL)) {
            $this->optionAll($input);
        }

        $this->checkEnvironment($input);
    }

    private function chooseTypes(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select the contenttypes you want to import',
            array_merge([self::OPTION_ALL], $this->fileService->getFileNames(FileService::TYPE_CONTENTTYPE))
        );
        $question->setMultiselect(true);

        $types = $helper->ask($input, $output, $question);
        if (in_array(self::OPTION_ALL, $types)) {
            $input->setOption(self::OPTION_ALL, true);
            $this->io->note(sprintf('Continuing with option --%s', self::OPTION_ALL));
        } else {
            $input->setArgument(self::ARGUMENT_CONTENTTYPES, $types);
            $this->io->note(['Continuing with contenttypes:', implode(', ', $types)]);
        }
    }

    private function optionAll(InputInterface $input): void
    {
        $types = $this->fileService->getFileNames(FileService::TYPE_CONTENTTYPE);
        $input->setArgument(self::ARGUMENT_CONTENTTYPES, $types);
        $this->io->note(['Continuing with contenttypes:', implode(', ', $types)]);
    }

    private function checkEnvironment(InputInterface $input): void
    {
        /** @var string $env */
        $env = $input->getOption(self::OPTION_ENV);
        /** @var Environment|false $environment */
        $environment = $this->environmentService->getByName($env);

        if ($environment === false) {
            $this->io->caution('Environment ' . $env . ' does not exist');
            $env = $this->io->choice('Select an existing environment as default', $this->environmentService->getEnvironmentNames());
            $input->setOption(self::OPTION_ENV, $env);
            $this->checkEnvironment($input);
            return;
        }

        $this->environment = $environment;
        $this->io->note(sprintf('Continuing with environment %s', $env));
    }

    private function updateContentType(string $type, Environment $environment, bool $deleteExistingActions, bool $deleteExistingViews): void
    {
        try {
            $json = $this->fileService->getFileContentsByFileName($type, FileService::TYPE_CONTENTTYPE);
            $contentType = $this->contentTypeService->contentTypeFromJson($json, $environment);

            $previousContentType = $this->contentTypeService->getByName($contentType->getName());
            if (false === $previousContentType) {
                $contentType = $this->contentTypeService->importContentType($contentType);
                $this->io->success(sprintf('Contenttype %s has been created', $contentType->getName()));
                return;
            } elseif (!$this->forceUpdate($contentType->getName())) {
                $this->io->note(sprintf('Contenttype %s has been ignored', $contentType->getName()));
                return;
            }
            $this->contentTypeService->updateFromJson($previousContentType, $json, $deleteExistingActions, $deleteExistingViews);
            $this->io->success(sprintf('Contenttype %s has been updated', $contentType->getName()));
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

    protected function forceUpdate(string $identifier): bool
    {
        if ($this->force) {
            return true;
        }
        $question = new ConfirmationQuestion(
            \sprintf('Continue with updating the existing the content type %s?', $identifier),
            false
        );

        return $this->io->askQuestion($question) === true;
    }
}
