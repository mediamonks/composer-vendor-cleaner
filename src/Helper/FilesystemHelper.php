<?php

namespace MediaMonks\ComposerVendorCleaner\Helper;

class FilesystemHelper
{
    public static function getSubdirectoriesByDirectory($dir)
    {
        $dirs = [];
        $dir  = new \DirectoryIterator($dir);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }
            $dirs[] = $fileinfo->getFilename();
        }
        return $dirs;
    }
}