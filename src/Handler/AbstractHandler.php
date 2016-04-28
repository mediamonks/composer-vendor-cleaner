<?php

namespace MediaMonks\ComposerVendorCleaner\Handler;

use MediaMonks\ComposerVendorCleaner\Model\Package;
use Symfony\Component\Finder\Finder;

abstract class AbstractHandler
{
    /**
     * @var Package
     */
    protected $package;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * AbstractHandler constructor.
     * @param Package $package
     * @param array $options
     */
    public function __construct(Package $package, array $options)
    {
        $this->package = $package;
        $this->options = $options;
    }

    /**
     * @return array
     */
    protected function getExcludedDirs()
    {
        if(!isset($this->options['excludes']['dirs'])) {
            return [];
        }
        return $this->options['excludes']['dirs'];
    }

    /**
     * @return array
     */
    protected function getExcludedFiles()
    {
        if(!isset($this->options['excludes']['files'])) {
            return [];
        }
        return $this->options['excludes']['files'];
    }

    /**
     * @param array $excludeDirs
     * @return array
     */
    public function getFilesWithExcludes($excludeDirs)
    {
        $files = [];
        $finder = new Finder();
        $finder->in($this->package->getDir())->exclude($excludeDirs)->ignoreVCS(false)->ignoreDotFiles(false);
        foreach (iterator_to_array($finder, false) as $file) {
            if (in_array($file->getRelativePathname(), $this->getExcludedFiles())) {
                continue;
            }
            $files[] = $file->getRealPath();
        }
        return $files;
    }
}