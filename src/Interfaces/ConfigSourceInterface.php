<?php
namespace Yikaikeji\Extension\Interfaces;

interface ConfigSourceInterface
{
    public function getComposerBin();

    public function getRootProjectPath();

    public function getPackageScanPath();

    public function getPackageInstalledPath();

    public function getPackageConfigFileName();

    public function addPackageToComposer($packageName,$packageVersion,$extra=[]);
}