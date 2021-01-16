<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Entity\ContentType;
use EMS\CoreBundle\Entity\Environment;
use EMS\CoreBundle\Service\ContentTypeService;
use EMS\CoreBundle\Service\EnvironmentService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentationCommand extends AbstractCommand
{
    const DOCUMENTATION_CT_NAME = 'documentation';
    const ENVIRONMENT = 'environment';
    protected static $defaultName = 'ems:maker:documentation';
    private EnvironmentService $environmentService;
    private ContentTypeService $contentTypeService;

    public function __construct(EnvironmentService $environmentService, ContentTypeService $contentTypeService)
    {
        parent::__construct();
        $this->environmentService = $environmentService;
        $this->contentTypeService = $contentTypeService;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->makeDocumentations($this->config['documentation']);

        return 0;
    }

    public function makeDocumentations(?array $config): void
    {
        if (null === $config) {
            $this->io->note('Documentations config is not defined');

            return;
        }

        $this->io->title('Make documentation');

        $resolved = $this->resolveDocumentation($config);

        $this->updateContentType($resolved[self::ENVIRONMENT]);
    }

    private function updateContentType(string $environmentName): void
    {
        $contentType = $this->contentTypeService->getByName(self::DOCUMENTATION_CT_NAME);
        if (false === $contentType) {
            $contentType = new ContentType();
        } elseif (!$this->forceUpdate('content type', self::DOCUMENTATION_CT_NAME)) {
            $this->io->note(\sprintf('Content Type %s ignored', self::DOCUMENTATION_CT_NAME));

            return;
        }

        $environment = $this->environmentService->getByName($environmentName);
        if (!$environment instanceof Environment) {
            throw new \RuntimeException(\sprintf('Environment %s not found', $environmentName));
        }

        $contentType->setName(self::DOCUMENTATION_CT_NAME);
        $contentType->setSingularName('Doc');
        $contentType->setPluralName('Documentation');
        $contentType->setEnvironment($environment);
        $this->contentTypeService->update($contentType);
    }

    /**
     * @param array<mixed> $config
     * @return array{environment: string}
     */
    private function resolveDocumentation(array $config)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            self::ENVIRONMENT => 'preview',
        ]);
        $resolver->setAllowedTypes(self::ENVIRONMENT, 'string');
        /** @var array{environment: string} $resolved */
        $resolved = $resolver->resolve($config);

        return $resolved;
    }
}
