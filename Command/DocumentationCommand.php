<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Service\EnvironmentService;
use EMS\CoreBundle\Service\IndexService;
use EMS\MakerBundle\Service\FileService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentationCommand extends AbstractCommand
{
    const DOCUMENTATION_CT_NAME = 'documentation';
    const ENVIRONMENT = 'environment';
    const CONTENTTYPE = 'contenttype';
    protected static $defaultName = 'ems:maker:documentation';
    private EnvironmentService $environmentService;
    private FileService $fileService;
    private IndexService $indexService;

    public function __construct(EnvironmentService $environmentService, FileService $fileService, IndexService $indexService)
    {
        $this->environmentService = $environmentService;
        $this->fileService = $fileService;
        $this->indexService = $indexService;
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->indexDocumentations($this->config['documentation']);

        return 0;
    }

    public function indexDocumentations(?array $config): void
    {
        if (null === $config) {
            $this->io->note('Documentations config is not defined');

            return;
        }

        $this->io->title('Index documentation');

        $resolved = $this->resolveDocumentation($config);

        $environment = $this->environmentService->getByName($resolved[self::ENVIRONMENT]);

        if (false === $environment) {
            $this->io->error(\sprintf('Target environment %s not found', $resolved[self::ENVIRONMENT]));

            return;
        }

        $this->io->progressStart($this->fileService->getDocumentationsCount());
        foreach ($this->fileService->getDocumentations() as $document) {
            $this->indexService->indexDocument($environment->getAlias(), $resolved[self::ENVIRONMENT], null, $document);
            $this->io->progressAdvance();
        }
        $this->io->progressFinish();
    }

    /**
     * @param array<mixed> $config
     * @return array{environment: string}
     */
    private function resolveDocumentation(array $config): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            self::ENVIRONMENT => 'preview',
            self::CONTENTTYPE => 'documentation',
        ]);
        $resolver->setAllowedTypes(self::ENVIRONMENT, 'string');
        $resolver->setAllowedTypes(self::CONTENTTYPE, 'string');
        /** @var array{environment: string} $resolved */
        $resolved = $resolver->resolve($config);

        return $resolved;
    }
}
