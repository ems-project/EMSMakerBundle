<?php

declare(strict_types=1);

namespace EMS\MakerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractCommand extends Command
{
    private const OPTION_FORCE = 'force';
    private const ARGUMENT_CONFIG_FILE = 'config_file';
    public const ADMIN = 'admin';
    public const SITES = 'sites';
    public const USERS = 'users';
    public const ENVIRONMENTS = 'environments';
    public const DOCUMENTATIONS = 'documentations';
    public const CONTENTTYPES = 'contenttypes';
    public const ANALYZERS = 'analyzers';
    public const REVISIONS = 'revisions';
    public const FILTERS = 'filters';
    protected array $config;
    protected SymfonyStyle $io;
    protected bool $force;

    protected function configure(string $defaultConfig = null)
    {
        parent::configure();
        if (null === $defaultConfig) {
            $this->addArgument(
                self::ARGUMENT_CONFIG_FILE,
                InputArgument::REQUIRED,
                'Path to the config file (in JSON format)'
            );
        } else {
            $this->addArgument(
                self::ARGUMENT_CONFIG_FILE,
                InputArgument::OPTIONAL,
                'Path to the config file (in JSON format)',
                $defaultConfig
            );
        }
        $this->addOption(
            self::OPTION_FORCE,
            null,
            InputOption::VALUE_NONE,
            'If set, items all ready defined will be overridden without question'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->force = $input->getOption(self::OPTION_FORCE) === true;

        $configFile = $input->getArgument(self::ARGUMENT_CONFIG_FILE);
        if (!\is_string($configFile)) {
            throw new \RuntimeException('Unexpected config file argument');
        }

        $configContent = \file_get_contents($configFile);
        if ($configContent === false) {
            throw new \RuntimeException('Config file not found');
        }

        $config = \json_decode($configContent, true);
        if (!\is_array($config)) {
            throw new \RuntimeException('Config file not found');
        }

        $this->config = $this->resolveConfig($config);
    }

    /**
     * @param array<mixed> $config
     * @return array{admin: array, sites: array, users: array, environments: array, documentations: array|null}
     */
    private function resolveConfig(array $config): array
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            self::ADMIN,
            self::SITES
        ])->setDefaults([
            self::USERS => [],
            self::ENVIRONMENTS => [],
            self::CONTENTTYPES => [],
            self::ANALYZERS => [],
            self::FILTERS => [],
            self::REVISIONS => [],
            self::DOCUMENTATIONS => null,
        ])->setAllowedTypes(self::ADMIN, 'array');
        $resolver->setAllowedTypes(self::SITES, ['array']);
        $resolver->setAllowedTypes(self::USERS, ['array']);
        $resolver->setAllowedTypes(self::ANALYZERS, ['array']);
        $resolver->setAllowedTypes(self::REVISIONS, ['array']);
        $resolver->setAllowedTypes(self::FILTERS, ['array']);
        $resolver->setAllowedTypes(self::ENVIRONMENTS, ['array']);
        $resolver->setAllowedTypes(self::CONTENTTYPES, ['array']);
        $resolver->setAllowedTypes(self::DOCUMENTATIONS, ['array','null']);

        /** @var array{admin: array, sites: array, users: array, environments: array, documentations: array|null} $resolved */
        $resolved = $resolver->resolve($config);

        return $resolved;
    }

    protected function forceUpdate(string $objectType, $identifier): bool
    {
        if ($this->force) {
            return true;
        }
        $question = new ConfirmationQuestion(
            \sprintf('Continue with updating the existing %s %s?', $objectType, $identifier),
            false
        );

        return $this->io->askQuestion($question) === true;
    }
}
