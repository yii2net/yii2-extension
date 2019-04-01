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

    public function setup($extensionId);

    public function unsetup($extensionId);

    public function delete($extensionId);

    public function remoteList($category='',$page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE);

    public function localList($status='',$page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE);
}