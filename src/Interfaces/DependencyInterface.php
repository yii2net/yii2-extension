<?php
namespace Yikaikeji\Extension\Interfaces;

interface DependencyInterface
{
    public function getInstalledDependencies($packageName='');

    public function getSkipDependencies($packageName='');

    public function getUnInstalledDependencies($packageName='');

    public function getDependencies();

    public function hasResolvedDependencies();

    public function isInstalled($packageName,$packageVersion);

    public function isSkiped($packageName,$packageVersion);
}