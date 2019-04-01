<?php
namespace Yikaikeji\Extension\Interfaces;

interface ConfigInterface
{
    public function getPackageInstalledPath();

    public function getPackageConfigFileName();
}