<?php

namespace MediaMonks\ComposerVendorCleaner\Handler;

class DefaultHandler extends AbstractHandler implements HandlerInterface
{
    /**
     * @return array
     */
    public function getFilesToRemove()
    {
        // get excludes from autoload mapping
        $psrMapped    = [];
        $excludeDirs  = $this->getExcludedDirs();
        $excludeFiles = $this->getExcludedFiles();
        $autoloadData = $this->package->getAutoload();

        if (isset($autoloadData['psr-0'])) {
            foreach ($autoloadData['psr-0'] as $namespace => $dir) {
                if (empty($dir)) {
                    return $this->getFilesWithExcludes($excludeDirs, $excludeFiles, true);
                }
            }
            $psrMapped += $autoloadData['psr-0'];
        }
        if (isset($autoloadData['psr-4'])) {
            foreach ($autoloadData['psr-4'] as $namespace => $dir) {
                if (empty($dir)) {
                    return $this->getFilesWithExcludes($excludeDirs, $excludeFiles, true);
                }
            }
            $psrMapped += $autoloadData['psr-4'];
        }
        if (isset($autoloadData['classmap'])) {
            $psrMapped += $autoloadData['classmap']; // @todo check if we use this correctly
        }
        if (isset($autoloadData['files'])) {
            $excludeFiles = array_merge($excludeFiles, $autoloadData['files']);
            // add the entire directory of the loader to the exclude dir,
            // there are quite some packages which use the same dir for storing the source files too
            foreach ($autoloadData['files'] as $file) {
                $excludeDirs[] = dirname($file);
            }
        }

        foreach ($psrMapped as $namespace => $dirs) {
            if (!is_array($dirs)) {
                $dirs = [$dirs];
            }
            foreach ($dirs as $dir) {
                $keepDir = current(explode('/', $dir));
                if (in_array($keepDir, $excludeDirs)) {
                    continue;
                }
                $excludeDirs[] = $keepDir;
            }
        }

        return $this->getFilesWithExcludes($excludeDirs, $excludeFiles);
    }
}