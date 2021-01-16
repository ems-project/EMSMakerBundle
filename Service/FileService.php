<?php
namespace EMS\MakerBundle\Service;

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
}
