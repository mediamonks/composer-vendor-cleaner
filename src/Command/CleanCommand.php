<?php

namespace MediaMonks\ComposerVendorCleaner\Command;

use MediaMonks\ComposerVendorCleaner\Model\Package;
use MediaMonks\ComposerVendorCleaner\Helper\FilesystemHelper;
use MediaMonks\ComposerVendorCleaner\Handler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CleanCommand extends Command
{
    const NAME = 'clean';

    const OPTION_DIR = 'dir';
    const OPTION_OPTIONS = 'options';
    const OPTION_DRY_RUN = 'dry-run';

    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $files = [];

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption(
                self::OPTION_DIR,
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify which dir you want to clean up'
            )
            ->addOption(
                self::OPTION_OPTIONS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Provide custom options by passing a json string or a path to a json file'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Do not actually delete the files'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setBaseDir($input);
        $this->setOptions($input);

        foreach ($this->getPackages($output) as $package) {
            if($this->isExcludedPackage($package->getName())) {
                $output->writeln(sprintf('Skipping package "%s"', $package->getName()));
                continue;
            }
            $this->cleanPackage($package, $output);
        }
        $this->removeFiles($input, $output);
    }

    /**
     * @param $packageName
     * @return bool
     */
    protected function isExcludedPackage($packageName)
    {
        foreach($this->options['excludes']['packages'] as $exclude) {
            if(preg_match(sprintf('~%s~', $exclude), $packageName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function removeFiles(InputInterface $input, OutputInterface $output)
    {
        $dryRun = false;
        if (!empty($input->getOption(self::OPTION_DRY_RUN))) {
            $dryRun = true;
            $output->writeln('<info>Dryrun only, no files will be deleted.</info>');
            sleep(1);
        }

        $fs      = new Filesystem();
        $success = 0;
        $failed  = 0;
        foreach ($this->files as $file) {
            try {
                $output->writeln(sprintf('Removing file "%s"', $file), OutputInterface::VERBOSITY_NORMAL);
                if (!$dryRun) {
                    $fs->remove($file);
                }
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $output->writeln(
                    sprintf('<error>Error removing file "%s": "$s"</error>', $file, $e->getMessage()),
                    OutputInterface::VERBOSITY_NORMAL
                );
            }
        }

        if ($success > 0) {
            $output->writeln(sprintf('<info>Removed a total of %d files</info>', $success),
                OutputInterface::VERBOSITY_NORMAL);
        }

        if ($failed > 0) {
            $output->writeln(sprintf('<error>%d files could not be removed</error>', $failed),
                OutputInterface::VERBOSITY_NORMAL);
        }
    }

    /**
     * @param Package $package
     * @param OutputInterface $output
     */
    protected function cleanPackage(Package $package, OutputInterface $output)
    {
        $options = [];
        if (isset($this->options['packages'][$package->getName()]['excludes'])) {
            $options['excludes'] = $this->options['packages'][$package->getName()]['excludes'];
        }

        $handler = $this->getHandlerByPackage($package, $options);
        $files   = $handler->getFilesToRemove();

        if (count($files) === 0) {
            $output->writeln(sprintf('No files to delete in package %s', $package->getName()),
                OutputInterface::OUTPUT_NORMAL);
        } else {
            $output->writeln(sprintf('%d files to delete in package %s', count($files), $package->getName()),
                OutputInterface::OUTPUT_NORMAL);
            foreach ($files as $file) {
                $this->files[] = $file;
            }
        }
    }

    /**
     * @param Package $package
     * @param $options
     * @return Handler\HandlerInterface
     */
    protected function getHandlerByPackage(Package $package, $options)
    {
        switch ($package->getType()) {
            case Package::TYPE_SYMFONY_BUNDLE:
                return new Handler\SymfonyBundleHandler($package, $options);
            default:
                return new Handler\DefaultHandler($package, $options);
        }
    }

    /**
     * @return array
     */
    protected function getPackages(OutputInterface $output)
    {
        $output->writeln(sprintf('Cleaning packages in %s', $this->baseDir), OutputInterface::OUTPUT_NORMAL);

        $packages = [];
        foreach (FilesystemHelper::getSubdirectoriesByDirectory($this->baseDir) as $vendorDir) {
            foreach (FilesystemHelper::getSubdirectoriesByDirectory($this->baseDir . $vendorDir) as $packageDir) {

                $finder = new Finder();
                $finder->files()->name('composer.json')->contains($vendorDir . '/' . $packageDir);
                $composerJson = null;
                foreach ($finder->in($this->baseDir . $vendorDir . '/' . $packageDir) as $file) {
                    $composerJson = $file->getRealpath();
                    break;
                }
                if (empty($composerJson)) {
                    $output->writeln(sprintf('Could not find matching composer.json in  %s',
                        $vendorDir . '/' . $packageDir), OutputInterface::OUTPUT_NORMAL);
                    // no matching composer.json was found, we skip this dir
                    continue;
                }

                $output->writeln(sprintf('Found package %s', $vendorDir . '/' . $packageDir),
                    OutputInterface::OUTPUT_NORMAL);

                $packages[] = new Package($this->baseDir, $vendorDir, $packageDir,
                    $this->parseJsonFromFile($composerJson));
            }
        }
        return $packages;
    }

    /**
     * @param InputInterface $input
     */
    protected function setBaseDir(InputInterface $input)
    {
        if (!empty($input->getOption(self::OPTION_DIR))) {
            $this->baseDir = $input->getOption(self::OPTION_DIR);
        } else {
            $this->baseDir = realpath(__DIR__ . '/../../../../../') . '/vendor/';
        }
    }

    /**
     * @param InputInterface $input
     * @throws \Exception
     */
    protected function setOptions(InputInterface $input)
    {
        if (!empty($input->getOption(self::OPTION_OPTIONS))) {
            $options = $input->getOption(self::OPTION_OPTIONS);
        } else {
            $options = realpath(__DIR__ . '/../../') . '/options.json';
        }

        if (file_exists($options)) {
            $options = $this->parseJsonFromFile($options);
        } else {
            $options = @json_decode($options, true);
            if (!is_array($options)) {
                throw new \Exception('Options file does not exist or is an invalid json string');
            }
        }

        $this->options = $options;
    }

    /**
     * @param $filename
     * @return array
     */
    protected function parseJsonFromFile($filename)
    {
        return json_decode(file_get_contents($filename), true);
    }
}
