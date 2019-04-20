<?php
namespace Openadm\Extension\Interfaces;

interface PackageInterface
{
    public function getName();

    public function getVersion();

    public function getPackageValidate();

    public function getStatus();

    public function setStatus($status);

    public function getPrettyName();

    public function getExtType();

    public function getAuthors();

    public function setExtra($key,$value);

    public function getCategory();

    public function getRequireFramework();

    public function getType();

    public function getPrice();

    public function getDescription();

    public function getKeywords();

    public function getRequire();

    public function toArray();
}