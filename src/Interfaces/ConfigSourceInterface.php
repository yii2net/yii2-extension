<?php
namespace Yikaikeji\Extension\Interfaces;

interface ConfigSourceInterface
{
    public function getComposerPath();

    public function getRootProjectPath();

    public function getPackageScanPath();

    public function getPackageInstalledPath();

    public function getPackageConfigFileName();

    public function addPackageToComposer($packageName,$packageVersion,$extra=[]);

    public function removePackageToComposer($packageName,$packageVersion,$extra=[]);

    public function onSetupCallback();

    public function onUnSetupCallback();

    public function onDeleteCallback();

    public function getLogLevel();

    public function isDebug();

    public function getExtraNamespace();

    public function checkCanDisablePackagist(PackageInterface $package);

    public function getCanDisablePackagist();

    public function disablePackagist();

    public function enablePackagist();

}