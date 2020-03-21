<?php

namespace EMS\MakerBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use EMS\CoreBundle\Entity\Filter;
use EMS\CoreBundle\Repository\FilterRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

final class FilterCommand extends Command
{
    protected static $defaultName = 'ems:make:filter';

    /** @var Registry */
    protected $doctrine;

    /** @var FilterRepository */
    private $filterRepository;

    /** @var SymfonyStyle */
    private $io;

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $filters;

    const OPTION_ALL = 'all';
    const ARGUMENT_FILTERS = 'filter';

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;

        $this->em = $this->doctrine->getManager();
        $repository = $this->em->getRepository('EMSCoreBundle:Filter');
        if (!$repository instanceof FilterRepository) {
            throw new \Exception('Wrong FilterRepository repository instance');
        }

        $this->filterRepository = $repository;
        $this->loadFilters();
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $fileNames = implode(', ', array_keys($this->filters));
        $this
            ->addArgument(
                self::ARGUMENT_FILTERS,
                InputArgument::IS_ARRAY,
                sprintf('Optional array of filter to create. Allowed values: [%s]', $fileNames)
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                sprintf('Make all filter: [%s]', $fileNames)
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Make filters');
        $this->io->section('Checking input');

        /** @var array $types */
        $filters = $input->getArgument(self::ARGUMENT_FILTERS);

        if (!$input->getOption(self::OPTION_ALL) && count($filters) == 0) {
            $this->chooseFilters($input, $output);
        }

        if ($input->getOption(self::OPTION_ALL)) {
            $this->optionAll($input);
        }

    }

    private function chooseFilters(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select the filter you want to import',
            array_merge([self::OPTION_ALL], array_keys($this->filters))
        );
        $question->setMultiselect(true);

        $filters = $helper->ask($input, $output, $question);
        if (in_array(self::OPTION_ALL, $filters)) {
            $input->setOption(self::OPTION_ALL, true);
            $this->io->note(sprintf('Continuing with option --%s', self::OPTION_ALL));
        } else {
            $input->setArgument(self::ARGUMENT_FILTERS, $filters);
            $this->io->note(['Continuing with filters:', implode(', ', $filters)]);
        }
    }

    private function optionAll(InputInterface $input): void
    {
        $filters = array_keys($this->filters);
        $input->setArgument(self::ARGUMENT_FILTERS, $filters);
        $this->io->note(['Continuing with filters:', implode(', ', $filters)]);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var array $types */
        $types = $input->getArgument(self::ARGUMENT_FILTERS);

        $orderKey = 1;
        foreach ($types as $type) {
            try {
                $data = $this->filters[$type];

                $filter = $this->filterRepository->findByName($data['name']);

                if ($filter === null) {
                    $filter = new Filter();
                }

                $filter->setLabel($data['label']);
                $filter->setName($data['name']);
                $filter->setOptions($data['options']);
                $filter->setOrderKey($orderKey);
                $this->em->persist($filter);
                ++$orderKey;
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        }
        $this->em->flush();
    }

    private function loadFilters()
    {
        $this->filters = [
            'dutch_stemmer' => [
                'name' => 'dutch_stemmer',
                'label' => 'dutch_stemmer',
                'options' => \json_decode('{"type":"stemmer","name":"dutch"}', true),
            ],
            'dutch_stop' => [
                'name' => 'dutch_stop',
                'label' => 'dutch_stop',
                'options' => \json_decode('{"remove_trailing":true,"stopwords":"_dutch_","type":"stop","ignore_case":false}', true),
            ],
            'empty_elision' => [
                'name' => 'empty_elision',
                'label' => 'empty_elision',
                'options' => \json_decode('{"articles":[""],"type":"elision","articles_case":false}', true),
            ],
            'english_stemmer' => [
                'name' => 'english_stemmer',
                'label' => 'english_stemmer',
                'options' => \json_decode('{"type":"stemmer","name":"english"}', true),
            ],
            'english_stop' => [
                'name' => 'english_stop',
                'label' => 'english_stop',
                'options' => \json_decode('{"remove_trailing":true,"stopwords":"_english_","type":"stop","ignore_case":false}', true),
            ],
            'french_elision' => [
                'name' => 'french_elision',
                'label' => 'french_elision',
                'options' => \json_decode('{"articles":["l","m","t","qu","n","s","j","d","c","jusqu","quoiqu","lorsqu","puisq"],"type":"elision","articles_case":false}', true),
            ],
            'french_stemmer' => [
                'name' => 'french_stemmer',
                'label' => 'french_stemmer',
                'options' => \json_decode('{"type":"stemmer","name":"light_french"}', true),
            ],
            'french_stop' => [
                'name' => 'french_stop',
                'label' => 'french_stop',
                'options' => \json_decode('{"remove_trailing":true,"stopwords":"_french_","type":"stop","ignore_case":false}', true),
            ],
            'german_stemmer' => [
                'name' => 'german_stemmer',
                'label' => 'german_stemmer',
                'options' => \json_decode('{"type":"stemmer","name":"light_german"}', true),
            ],
            'german_stop' => [
                'name' => 'german_stop',
                'label' => 'german_stop',
                'options' => \json_decode('{"remove_trailing":true,"stopwords":"_german_","type":"stop","ignore_case":false}', true),
            ],
        ];
    }
}
