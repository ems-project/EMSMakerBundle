<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Entity\ContentType;
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
    const MAPPING = 'mapping';
    const TYPE = 'type';
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
        if (0 === \count($environments)) {
            $this->io->note('No environment to import');

            return;
        }
        $this->io->title('Make environments');
        $this->io->progressStart(\count($environments));
        $this->aliasService->build();
        foreach ($environments as $environment) {
            $resolved = $this->resolveEnvironment($environment);

            $entity = $this->environmentService->getByName($resolved[self::NAME]);
            if (false === $entity) {
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
            $contentType = new ContentType();
            $contentType->setName($resolved[self::TYPE]);
            $mapping = $this->mapping->generateMapping($contentType);
            if (null !== $resolved[self::MAPPING]) {
                $mapping['properties'] = array_merge($resolved[self::MAPPING], $mapping['properties']);
            }
            $this->mapping->updateMapping($indexName, $mapping, $resolved[self::TYPE]);
            $this->aliasService->atomicSwitch($entity, $indexName);

            $this->io->progressAdvance();
        }
        $this->io->progressFinish();
    }

    /**
     * @param array<mixed> $environment
     *
     * @return array{name: string, type: string, color: string, managed: bool, alias: null|string, mapping: array|null}
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
            self::MAPPING => null,
            self::TYPE => 'doc',
        ]);
        $resolver->setAllowedTypes(self::NAME, 'string');
        $resolver->setAllowedTypes(self::MANAGED, 'bool');
        $resolver->setAllowedTypes(self::COLOR, 'string');
        $resolver->setAllowedTypes(self::ALIAS, ['string', 'null']);
        $resolver->setAllowedTypes(self::TYPE, ['string']);
        $resolver->setAllowedTypes(self::MAPPING, ['array', 'null']);

        /** @var array{name: string, type: string, color: string, managed: bool, alias: null|string, mapping: array|null} $resolved */
        $resolved = $resolver->resolve($environment);

        return $resolved;
    }
}
