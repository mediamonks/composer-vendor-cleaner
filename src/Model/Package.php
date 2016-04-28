<?php

namespace MediaMonks\ComposerVendorCleaner\Model;

class Package
{
    const TYPE_SYMFONY_BUNDLE = 'symfony-bundle';

    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @var package
     */
    protected $packageDir;

    /**
     * @var array
     */
    protected $data;

    /**
     * @param $baseDir
     * @param $vendorDir
     * @param $packageDir
     * @param array $data
     */
    public function __construct($baseDir, $vendorDir, $packageDir, array $data)
    {
        $this->baseDir    = $baseDir;
        $this->vendorDir  = $vendorDir;
        $this->packageDir = $packageDir;
        $this->data       = $data;
    }

    /**
     * @return string
     */
    public function getDir()
    {
        return $this->baseDir . '/' . $this->vendorDir . '/' . $this->packageDir;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->data['name'];
    }

    /**
     * @return string
     */
    public function getType()
    {
        if(empty($this->data['type'])) {
            return null;
        }
        return $this->data['type'];
    }

    /**
     * @return mixed
     */
    public function getAutoload()
    {
        return $this->data['autoload'];
    }
}