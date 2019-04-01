<?php
namespace Yikaikeji\Extension;

use Yikaikeji\Extension\Interfaces\ManagerInterface;

class Loader implements ManagerInterface
{
    /**
     * @var DI\Container
     */
    private $container;

    private $manager = 'Yikaikeji\Extension\Implement\Manager';

    public function __construct($config = [])
    {
        $this->container = new \DI\Container;
        $this->init($config);
    }

    public function init(&$config)
    {
        $this->container->get($this->manager)->init($config);
    }

    /**
     * @param $extensionId
     */
    public function unsetup($extensionId)
    {
        return $this->container->get($this->manager)->unsetup($extensionId);
    }

    /**
     * @param $extensionId
     */
    public function setup($extensionId)
    {
        return $this->container->get($this->manager)->setup($extensionId);
    }

    /**
     * @param $extensionId
     * @return mixed
     */
    public function delete($extensionId)
    {
        return $this->container->get($this->manager)->delete($extensionId);
    }

    /**
     * @param $category
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function remoteList($category='', $page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE)
    {
        return $this->container->get($this->manager)->remoteList($category, $page, $pageSize);
    }

    /**
     * @param $status
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function localList($status='', $page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE)
    {
        return $this->container->get($this->manager)->localList($status, $page, $pageSize);
    }


}