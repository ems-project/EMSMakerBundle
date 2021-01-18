<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Service\ContentTypeService;
use EMS\CoreBundle\Service\DocumentService;
use EMS\MakerBundle\Service\FileService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RevisionCommand extends AbstractCommand
{
    protected static $defaultName = 'ems:maker:revision';
    private const CONTENTTYPE = 'contenttype';
    private const FOLDER = 'folder';

    private FileService $fileService;
    private DocumentService $documentService;
    private ContentTypeService $contentTypeService;

    public function __construct(ContentTypeService $contentTypeService, DocumentService $documentService, FileService $fileService)
    {
        $this->fileService = $fileService;
        $this->documentService = $documentService;
        $this->contentTypeService = $contentTypeService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->makeRevisions($this->config[AbstractCommand::REVISIONS]);

        return 0;
    }

    /**
     * @param array<mixed> $revisions
     */
    public function makeRevisions(array $revisions): void
    {
        if (\count($revisions) === 0) {
            return;
        }
        $this->io->title('Make revisions');
        $this->io->progressStart(\count($revisions));
        foreach ($revisions as $revision) {
            $resolved = $this->resolveRevision($revision);
            $contentTypeName = $resolved[self::CONTENTTYPE] ?? $resolved[self::FOLDER];
            $contentType = $this->contentTypeService->getByName($contentTypeName);
            if (false === $contentType) {
                $this->io->warning(\sprintf('Content type %s not found', $contentTypeName));
                continue;
            }
            $finder = $this->fileService->getFilesInFolder($resolved[self::FOLDER], FileService::TYPE_REVISION);
            if ($finder->count() === 0) {
                continue;
            }
            $this->io->title(\sprintf('Make %s revisions', $contentTypeName));
            $this->io->progressStart($finder->count());
            $importerContext = $this->documentService->initDocumentImporterContext($contentType, 'REVISION_MAKER', false, true, true, 100, true, true);
            /** @var SplFileInfo $file **/
            foreach ($finder as $file) {
                $content = \json_decode($file->getContents(), true);
                $this->documentService->importDocument($importerContext, $file->getBasename('.json'), $content);
                $this->io->progressAdvance();
            }
            $this->io->progressFinish();
        }
    }


    /**
     * @param array<mixed> $config
     * @return array{folder: string}
     */
    private function resolveRevision(array $config)
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            self::FOLDER,
        ]);
        $resolver->setDefaults([
            self::CONTENTTYPE => null,
        ]);
        $resolver->setAllowedTypes(self::FOLDER, 'string');
        $resolver->setAllowedTypes(self::CONTENTTYPE, ['null', 'string']);
        /** @var array{folder: string} $resolved */
        $resolved = $resolver->resolve($config);

        return $resolved;
    }
}
