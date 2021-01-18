<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Entity\Helper\JsonClass;
use EMS\CoreBundle\Repository\AnalyzerRepository;
use EMS\MakerBundle\Service\FileService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzerCommand extends AbstractCommand
{
    protected static $defaultName = 'ems:maker:analyzer';

    private AnalyzerRepository $analyzerRepository;
    private FileService $fileService;

    public function __construct(AnalyzerRepository $analyzerRepository, FileService $fileService)
    {
        $this->analyzerRepository = $analyzerRepository;
        $this->fileService = $fileService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->makeAnalyzers($this->config[AbstractCommand::ANALYZERS]);

        return 0;
    }

    /**
     * @param string[] $analyzers
     */
    private function makeAnalyzers(array $analyzers): void
    {
        if (\count($analyzers) === 0) {
            return;
        }
        $this->io->title('Make analyzers');
        $this->io->progressStart(\count($analyzers));
        foreach ($analyzers as $filename) {
            $json = $this->fileService->getFileContentsByFileName($filename, FileService::TYPE_ANALYZER);
            $analyzer = $this->analyzerRepository->findByName($filename);

            if (null !== $analyzer && !$this->forceUpdate('analyzer', $filename)) {
                $this->io->note(\sprintf('Analyzer %s ignored', $filename));
                $this->io->progressAdvance();
                continue;
            }

            $meta = JsonClass::fromJsonString($json);
            $analyzer = $meta->jsonDeserialize($analyzer);
            $this->analyzerRepository->update($analyzer);
            $this->io->progressAdvance();
        }
    }

}
