<?php
namespace Yikaikeji\Extension\Interfaces;

interface PackageInterface
{
    public function getId();

    public function getVersion();

    public function getPackageValidate();

    public function getStatus();

    public function setStatus($status);
}