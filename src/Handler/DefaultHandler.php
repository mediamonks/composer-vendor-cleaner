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
        $autoloadData = $this->package->getAutoload();
        $psrMapped    = [];
        if (isset($autoloadData['psr-0'])) {
            $psrMapped += $autoloadData['psr-0'];
        }
        if (isset($autoloadData['psr-4'])) {
            $psrMapped += $autoloadData['psr-4'];
        }
        if (isset($autoloadData['classmap'])) {
            $psrMapped += $autoloadData['classmap'];
        }
        if (isset($autoloadData['files'])) {
            $psrMapped += $autoloadData['files'];
        }

        $excludeDirs = [];
        foreach ($psrMapped as $namespace => $folders) {
            if (!is_array($folders)) {
                $folders = [$folders];
            }
            foreach ($folders as $folder) {
                $folderKeep = current(explode('/', $folder));
                if (in_array($folderKeep, $excludeDirs)) {
                    continue;
                }
                $excludeDirs[] = $folderKeep;
            }
        }

        return $this->getFilesWithExcludes($excludeDirs);
    }
}