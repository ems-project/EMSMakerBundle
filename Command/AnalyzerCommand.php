<?php

namespace EMS\MakerBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use EMS\CoreBundle\Entity\Analyzer;
use EMS\CoreBundle\Repository\AnalyzerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class AnalyzerCommand extends Command
{
    protected static $defaultName = 'ems:make:analyzer';

    /** @var Registry */
    protected $doctrine;

    /** @var AnalyzerRepository */
    private $analyzerRepository;

    /** @var SymfonyStyle */
    private $io;

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $analyzers;

    const OPTION_ALL = 'all';
    const ARGUMENT_ANALYZERS = 'analyzer';

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;

        $this->em = $this->doctrine->getManager();
        $repository = $this->em->getRepository('EMSCoreBundle:Analyzer');
        if (!$repository instanceof AnalyzerRepository) {
            throw new \Exception('Wrong AnalyzerRepository repository instance');
        }

        $this->analyzerRepository = $repository;
        $this->loadAnalyzers();
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $fileNames = implode(', ', array_keys($this->analyzers));
        $this
            ->addArgument(
                self::ARGUMENT_ANALYZERS,
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
        $this->io->title('Make analyzers');
        $this->io->section('Checking input');

        /** @var array $types */
        $filters = $input->getArgument(self::ARGUMENT_ANALYZERS);

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
            'Select the analyzer you want to import',
            array_merge([self::OPTION_ALL], array_keys($this->analyzers))
        );
        $question->setMultiselect(true);

        $analyzers = $helper->ask($input, $output, $question);
        if (in_array(self::OPTION_ALL, $analyzers)) {
            $input->setOption(self::OPTION_ALL, true);
            $this->io->note(sprintf('Continuing with option --%s', self::OPTION_ALL));
        } else {
            $input->setArgument(self::ARGUMENT_ANALYZERS, $analyzers);
            $this->io->note(['Continuing with analyzers:', implode(', ', $analyzers)]);
        }
    }

    private function optionAll(InputInterface $input): void
    {
        $analyzers = array_keys($this->analyzers);
        $input->setArgument(self::ARGUMENT_ANALYZERS, $analyzers);
        $this->io->note(['Continuing with analyzers:', implode(', ', $analyzers)]);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var array $analyzers */
        $analyzers = $input->getArgument(self::ARGUMENT_ANALYZERS);

        $orderKey = 1;
        foreach ($analyzers as $analyzerKey) {
            try {
                $data = $this->analyzers[$analyzerKey];

                $analyzer = $this->analyzerRepository->findByName($data['name']);

                if ($analyzer === null) {
                    $analyzer = new Analyzer();
                }

                $analyzer->setLabel($data['label']);
                $analyzer->setName($data['name']);
                $analyzer->setOptions($data['options']);
                $analyzer->setOrderKey($orderKey);
                $this->em->persist($analyzer);
                ++$orderKey;
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        }
        $this->em->flush();
    }

    private function loadAnalyzers()
    {
        $this->analyzers = [
            'alpha_order' => [
                'name' => 'alpha_order',
                'label' => 'alpha_order',
                'options' => \json_decode('{"filter":["asciifolding","lowercase"],"char_filter":[],"type":"custom","tokenizer":"keyword"}', true),
            ],
            'dutch_for_highlighting' => [
                'name' => 'dutch_for_highlighting',
                'label' => 'dutch_for_highlighting',
                'options' => \json_decode('{"filter":["standard","lowercase","dutch_stemmer","dutch_stop","empty_elision"],"char_filter":["html_strip"],"type":"custom","tokenizer":"standard"}', true),
            ],
            'english_for_highlighting' => [
                'name' => 'english_for_highlighting',
                'label' => 'english_for_highlighting',
                'options' => \json_decode('{"filter":["standard","lowercase","empty_elision","english_stemmer","english_stop"],"char_filter":["html_strip"],"type":"custom","tokenizer":"standard"}', true),
            ],
            'french_for_highlighting' => [
                'name' => 'french_for_highlighting',
                'label' => 'french_for_highlighting',
                'options' => \json_decode('{"filter":["standard","asciifolding","lowercase","french_elision","french_stemmer","french_stop"],"char_filter":["html_strip"],"type":"custom","tokenizer":"standard"}', true),
            ],
            'german_for_highlighting' => [
                'name' => 'german_for_highlighting',
                'label' => 'german_for_highlighting',
                'options' => \json_decode('{"filter":["standard","lowercase","empty_elision","german_stemmer","german_stop"],"char_filter":["html_strip"],"type":"custom","tokenizer":"standard"}', true),
            ],
            'html_strip' => [
                'name' => 'html_strip',
                'label' => 'html_strip',
                'options' => \json_decode('{"filter":["standard"],"char_filter":["html_strip"],"type":"custom","tokenizer":"standard"}', true),
            ],
        ];
    }
}
