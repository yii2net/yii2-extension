<?php
namespace Yikaikeji\Extension\Interfaces;

interface ConfigSourceInterface
{
    public function getPackageInstalledPath();

    public function getPackageConfigFileName();
}