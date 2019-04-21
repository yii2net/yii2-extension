<?php
namespace Openadm\Extension\Interfaces;

interface VersionInterface
{

    public function getShortVersion(): string;

    public function getCommitHash(): string;

    public function getShortCommitHash(): string;

}