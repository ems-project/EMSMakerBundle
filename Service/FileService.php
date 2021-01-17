<?php
namespace EMS\MakerBundle\Service;

use EMS\CoreBundle\Form\Field\SelectPickerType;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileService
{
    
    const JSON_FILES = __DIR__ . '/../Resources/make/';
    const TYPE_ANALYZER = 'analyzer';
    const TYPE_CONTENTTYPE = 'contenttype';
    const TYPE_ENVIRONMENT = 'environment';
    const TYPE_REVISION = 'revision';
    const TYPE_USER = 'user';
    const TYPES = [self::TYPE_ANALYZER, self::TYPE_CONTENTTYPE, self::TYPE_ENVIRONMENT, self::TYPE_REVISION, self::TYPE_USER];
    private string $projectDir;
    private string $bundlesDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->bundlesDir = \implode(DIRECTORY_SEPARATOR, [$projectDir, 'vendor', 'elasticms']);
    }

    public function getFileNames(string $type): array
    {
        if (!in_array($type, self::TYPES)) {
            return [];
        }

        $finder = new Finder();
        $finder = $finder->files()->name('*.json')->in(self::JSON_FILES . $type);

        $names = [];
        /** @var SplFileInfo $file **/
        foreach ($finder as $file) {
            $names[] = $file->getBasename('.json');
        }
        return $names;
    }
    
    public function getFileContentsByFileName(string $name, string $type): string
    {
        $path = self::JSON_FILES . $type;
        $finder = new Finder();
        $finder = $finder->files()->name($name . '.json')->in($path);

        foreach ($finder as $file) {
            /** @var SplFileInfo $file **/
            return $file->getContents();
        }

        throw new FileNotFoundException(null, 0, null, $path . '/' . $name . '.json');
    }

    public function getDocumentationsCount(): int
    {
        $counter = 0;
        foreach ($this->getBundles() as $bundle) {
            $bundlePath = $bundle->getRealPath();
            if (!\is_string($bundlePath)) {
                continue;
            }
            $counter += $this->getMarkdowns($bundlePath)->count();
        }

        return $counter;
    }

    public function getDocumentations()
    {
        $composerLockContent = \file_get_contents($this->projectDir . DIRECTORY_SEPARATOR . 'composer.lock');
        if (false === $composerLockContent) {
            $composerLockContent = '{}';
        }
        $composerLockJson = \json_decode($composerLockContent, true);
        if (!is_array($composerLockJson)) {
            $composerLockJson = [];
        }
        $packages = [];
        foreach ($composerLockJson['packages'] ?? [] as $package) {
            $split = \explode('/', $package['name'] ?? 'do-not-care');
            if (\count($split) !== 2 || \reset($split) !== 'elasticms') {
                continue;
            }
            $packages[\end($split)] = $package['version'];
        }

        foreach ($this->getBundles() as $bundle) {
            $bundlePath = $bundle->getRealPath();
            if (!\is_string($bundlePath)) {
                continue;
            }
            $bundleName = $bundle->getFilename();
            $composer = \file_get_contents($bundlePath . DIRECTORY_SEPARATOR . 'composer.json');
            if (false === $composer) {
                continue;
            }
            $json = \json_decode($composer, true);
            if ($json === $composer) {
                continue;
            }
            $bundlesKeywords = $json['keywords'] ?? ['Bundle\'s keywords not found'];

            /** @var SplFileInfo $file **/
            foreach ($this->getMarkdowns($bundlePath) as $file) {
                $name = SelectPickerType::humanize($file->getBasename('.md'));
                yield [
                    'path_en' => $bundleName . \substr($file->getPath(), \strlen($bundlePath)) . DIRECTORY_SEPARATOR . $file->getBasename('.md'),
                    'keywords' => \array_merge($bundlesKeywords, [$file->getBasename('.md'), $bundleName]),
                    'title_en' => $name,
                    'body_en' => $file->getContents(),
                    'version' => $packages[$bundleName] ?? 'not-specified',
                ];
            }
        }
    }

    private function getBundles(): Finder
    {
        $bundles = new Finder();
        return  $bundles->in($this->bundlesDir)->directories();
    }

    private function getMarkdowns(string $bundlePath): Finder
    {
        $finder = new Finder();

        return $finder->in($bundlePath)->notPath(['vendor', 'public', 'node_modules'])->name('*.md')->files();
    }
}
