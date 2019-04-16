<?php
namespace Yikaikeji\Extension\Interfaces;

interface ManagerInterface
{
    /**
     * @var int default pagesize
     */
    const DEFAULT_PAGESIZE = 20;
    /**
     * @var int default page
     */
    const DEFAULT_PAGE = 1;
    /**
     * @var local package
     */
    const LOCATE_LOCAL = 'local';
    /**
     * @var remote package
     */
    const LOCATE_REMOTE = 'remote';

    public function setup($packageName, $packageVersion, $locate=self::LOCATE_LOCAL);

    public function unSetup($packageName, $locate=self::LOCATE_LOCAL);

    public function delete($packageName);

    public function remoteList($category='',$query='',$page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE);

    public function localList($category='',$status='',$query='',$page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE);
}