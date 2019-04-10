<?php
namespace Yikaikeji\Extension\Interfaces;

interface ComposerInterface
{
    public function getComposerPath();

    public function install($params = [], $callback);

    public function update($params = [], $callback);

    public function remove($packageName, $callback);
}