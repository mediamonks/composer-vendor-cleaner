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
        $finder = new Finder();
        foreach ($finder->in($this->package->getDir())->depth(0)->directories() as $file) {
            if(in_array($file->getRelativePathname(), ['Tests'])) {
                continue;
            }
            if (in_array($file->getRelativePathname(), $this->getExcludedDirs())) {
                continue;
            }
            $excludeDirs[] = $file->getRelativePathname();
        }
        return $this->getFilesWithExcludes($excludeDirs);
    }

}