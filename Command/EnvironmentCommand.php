<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Entity\Environment;
use EMS\CoreBundle\Service\AliasService;
use EMS\CoreBundle\Service\EnvironmentService;
use EMS\CoreBundle\Service\Mapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvironmentCommand extends AbstractCommand
{
    private const NAME = 'name';
    private const COLOR = 'color';
    private const ALIAS = 'alias';
    private const MANAGED = 'managed';
    protected static $defaultName = 'ems:maker:environment';
    private EnvironmentService $environmentService;
    private Mapping $mapping;
    private AliasService $aliasService;

    public function __construct(EnvironmentService $environmentService, Mapping $mapping, AliasService $aliasService)
    {
        parent::__construct();
        $this->environmentService = $environmentService;
        $this->mapping = $mapping;
        $this->aliasService = $aliasService;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->makeEnvironments($this->config[AbstractCommand::ENVIRONMENTS]);

        return 0;
    }

    public function makeEnvironments(array $environments): void
    {
        if (\count($environments) === 0) {
            $this->io->note('No environment to import');

            return;
        }
        $this->io->title('Make environments');
        $this->io->progressStart(\count($environments));
        $this->aliasService->build();
        foreach ($environments as $environment) {
            $resolved = $this->resolveEnvironment($environment);

            $entity = $this->environmentService->getByName($resolved[self::NAME]);
            if ($entity === false) {
                $entity = new Environment();
            } elseif (!$this->forceUpdate('environment', $resolved[self::NAME])) {
                $this->io->note(\sprintf('Environment %s ignored', $resolved[self::NAME]));
                $this->io->progressAdvance();
                continue;
            }

            $entity->setName($resolved[self::NAME]);
            $entity->setColor($resolved[self::COLOR]);
            $entity->setManaged($resolved[self::MANAGED]);
            if (\is_string($resolved[self::ALIAS])) {
                $entity->setAlias($resolved[self::ALIAS]);
            }
            $this->environmentService->updateEnvironment($entity);

            $indexName = $entity->getNewIndexName();
            $this->mapping->createIndex($indexName, $this->environmentService->getIndexAnalysisConfiguration());
            $this->aliasService->atomicSwitch($entity, $indexName);

            $this->io->progressAdvance();
        }
        $this->io->progressFinish();
    }

    /**
     * @param array<mixed> $environment
     * @return array{name: string, color: string, managed: bool, alias: null|string}
     */
    private function resolveEnvironment(array $environment): array
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            self::NAME,
        ])->setDefaults([
            self::COLOR => 'red',
            self::MANAGED => true,
            self::ALIAS => null,
        ]);
        $resolver->setAllowedTypes(self::NAME, 'string');
        $resolver->setAllowedTypes(self::MANAGED, 'bool');
        $resolver->setAllowedTypes(self::COLOR, 'string');
        $resolver->setAllowedTypes(self::ALIAS, ['string', 'null']);

        /** @var array{name: string, color: string, managed: bool, alias: null|string} $resolved */
        $resolved = $resolver->resolve($environment);

        return $resolved;
    }
}
