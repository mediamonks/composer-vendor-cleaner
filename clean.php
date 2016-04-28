<?php

use Symfony\Component\Finder\Finder;

require_once 'vendor/autoload.php';

$settings = [
    'excludes' => [
        'packages' => [
            'symfony/polyfill-apcu',
            'symfony/polyfill-intl-icu',
            'symfony/polyfill-mbstring',
            'symfony/polyfill-php54',
            'symfony/polyfill-php55',
            'symfony/polyfill-php56',
            'symfony/polyfill-php70',
            'symfony/polyfill-util',
        ]
    ],
    'packages' => [
        'behat/behat' => [
            'excludes' => [
                'files' => [
                    'i18n.php'
                ]
            ]
        ],
        'mobiledetect/mobiledetectlib' => [
            'excludes' => [
                'files' => [
                    'Mobile_Detect.json'
                ]
            ]
        ]
    ]
];


$fs = new \Symfony\Component\Filesystem\Filesystem();

function getSubdirectoriesByDirectory($directory)
{
    $dirs = [];
    $dir  = new DirectoryIterator($directory);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDir() || $fileinfo->isDot()) {
            continue;
        }
        $dirs[] = $fileinfo->getFilename();
    }
    return $dirs;
}

$baseDir = 'vendor_clean/';
foreach (getSubdirectoriesByDirectory($baseDir) as $vendor) {
    foreach (getSubdirectoriesByDirectory($baseDir . $vendor) as $package) {
        $vendorPackage = $vendor . '/' . $package;

        $full = $baseDir . $vendorPackage;

        if (in_array($vendorPackage, $settings['excludes']['packages'])) {
            continue;
        }

        /*if ($vendor . '/' . $package !== 'mediamonks/rest-api-bundle') {
            continue;
        }*/

        echo $full . PHP_EOL;

        $finder = new Finder();
        $finder->files()->name('composer.json')->contains($vendor . '/' . $package);
        $composerJson = null;
        foreach ($finder->in($full) as $file) {
            $composerJson = $file->getRealpath();
            break;
        }
        if (empty($composerJson)) {
            // no composer.json was found, should never happen
            continue;
        }

        $excludeDirs = [];
        $excludeFiles = [];
        if (isset($settings['packages'][$vendorPackage]['excludes']['dirs'])) {
            $excludeDirs += $settings['packages'][$vendorPackage]['excludes']['dirs'];
        }
        if (isset($settings['packages'][$vendorPackage]['excludes']['files'])) {
            $excludeFiles += $settings['packages'][$vendorPackage]['excludes']['files'];
        }

        $composerData = json_decode(file_get_contents($composerJson), true);

        if (isset($composerData['type']) && in_array($composerData['type'], ['symfony-bundle'])) {
            $finder = new Finder();
            foreach ($finder->in($full)->depth(0)->directories() as $file) {
                if(in_array($file->getRelativePathname(), ['Tests'])) {
                    continue;
                }
                if (in_array($file->getRelativePathname(), $excludeDirs)) {
                    continue;
                }
                $excludeDirs[] = $file->getRelativePathname();
            }
        }
        else {
            // get excludes from autoload mapping
            $autoloadData = $composerData['autoload'];
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
        }

        $finder = new Finder();
        $finder->in($full)->exclude($excludeDirs)->ignoreVCS(false)->ignoreDotFiles(false);
        foreach (iterator_to_array($finder, false) as $file) {
            if (in_array($file->getRelativePathname(), $excludeFiles)) {
                continue;
            }
            try {
                $fs->remove($file->getRealPath());
            }
            catch(\Exception $e) {

            }
        }
    }
}

echo 'done' . PHP_EOL;
