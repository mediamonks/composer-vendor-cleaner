<?php

namespace MediaMonks\ComposerVendorCleaner\Handler;

use Symfony\Component\Finder\Finder;

class SymfonyBundleHandler extends AbstractHandler implements HandlerInterface
{
    /**
     * @return array
     */
    public function getFilesToRemove()
    {
        $excludeDirs = [];
        $excludeFiles = [];
        $finder = new Finder();
        foreach ($finder->in($this->package->getDir())->depth(0) as $file) {
            if($file->isDir()) {
                if(in_array($file->getRelativePathname(), ['Tests'])) {
                    continue;
                }
                if (in_array($file->getRelativePathname(), $this->getExcludedDirs())) {
                    continue;
                }
                $excludeDirs[] = $file->getRelativePathname();
            }
            elseif($file->isFile()) {
                if($file->getExtension() === 'php') {
                    $excludeFiles[] = $file->getRelativePathname();
                }
            }
        }
        return $this->getFilesWithExcludes($excludeDirs, $excludeFiles);
    }

}