<?php

namespace EMS\MakerBundle\Command;

use EMS\CoreBundle\Entity\Helper\JsonClass;
use EMS\CoreBundle\Repository\FilterRepository;
use EMS\MakerBundle\Service\FileService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilterCommand extends AbstractCommand
{
    protected static $defaultName = 'ems:maker:filter';

    private FilterRepository $filterRepository;
    private FileService $fileService;

    public function __construct(FilterRepository $filterRepository, FileService $fileService)
    {
        $this->filterRepository = $filterRepository;
        $this->fileService = $fileService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->makeFilters($this->config[AbstractCommand::FILTERS]);

        return 0;
    }

    /**
     * @param string[] $filters
     */
    public function makeFilters(array $filters): void
    {
        if (\count($filters) === 0) {
            return;
        }
        $this->io->title('Make filters');
        $this->io->progressStart(\count($filters));
        foreach ($filters as $filename) {
            $json = $this->fileService->getFileContentsByFileName($filename, FileService::TYPE_FILTER);
            $filter = $this->filterRepository->findByName($filename);

            if (null !== $filter && !$this->forceUpdate('filter', $filename)) {
                $this->io->note(\sprintf('Filter %s ignored', $filename));
                $this->io->progressAdvance();
                continue;
            }

            $meta = JsonClass::fromJsonString($json);
            $filter = $meta->jsonDeserialize($filter);
            $this->filterRepository->update($filter);
            $this->io->progressAdvance();
        }
    }
}
