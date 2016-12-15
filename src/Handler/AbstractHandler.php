<?php

namespace MediaMonks\ComposerVendorCleaner\Handler;

use MediaMonks\ComposerVendorCleaner\Model\Package;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
     *
     * @param Package $package
     * @param array   $options
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
        if (!isset($this->options['excludes']['dirs'])) {
            return [];
        }

        return $this->options['excludes']['dirs'];
    }

    /**
     * @return array
     */
    protected function getExcludedFiles()
    {
        if (!isset($this->options['excludes']['files'])) {
            return [];
        }

        return $this->options['excludes']['files'];
    }

    /**
     * @param array $excludeDirs
     * @param array $excludeFiles
     *
     * @return array
     */
    protected function getFilesWithExcludes($excludeDirs, $excludeFiles = [], $onlyFiles = false)
    {
        $excludeFiles = array_merge($this->getExcludedFiles(), $excludeFiles);
        $files        = [];
        $finder       = new Finder();

        $finder->in($this->package->getDir())->ignoreVCS(false)->ignoreDotFiles(false);
        if ($onlyFiles) {
            $finder->files();
        } else {
            $finder->exclude($excludeDirs);
        }

        foreach (iterator_to_array($finder, false) as $file) {
            if (in_array($file->getRelativePathname(), $excludeFiles)) {
                continue;
            }
            if ($this->shouldIgnore($file)) {
                continue;
            }
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return bool
     */
    protected function shouldIgnore(SplFileInfo $file)
    {
        if ($file->getExtension() === 'php') {
            return true;
        }

        return false;
    }
}