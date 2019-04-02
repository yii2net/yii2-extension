<?php
namespace Yikaikeji\Extension\Interfaces;

interface DependencyInterface
{
    public function getInstalledDependencies();

    public function getUnInstalledDependencies();

    public function getDependencies();

    public function hasResolvedDependencies();
}