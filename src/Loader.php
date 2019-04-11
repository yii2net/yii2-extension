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

    public function getPackage($packageName)
    {
        return $this->container->get($this->manager)->getPackage($packageName);
    }

    /**
     * @param $packageName
     * @return mixed
     */
    public function unSetup($packageName,$locate=self::LOCATE_LOCAL)
    {
        return $this->container->get($this->manager)->unsetup($packageName,$locate);
    }

    /**
     * @param $packageName
     * @param $packageVersion
     * @param Interfaces\local $locate
     * @return mixed
     */
    public function setup($packageName, $packageVersion, $locate=self::LOCATE_LOCAL)
    {
        return $this->container->get($this->manager)->setup($packageName, $packageVersion, $locate);
    }

    /**
     * @param $packageName
     * @return mixed
     */
    public function delete($packageName)
    {
        return $this->container->get($this->manager)->delete($packageName);
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
     * @param $category
     * @param $status
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function localList($category='', $status='', $query='', $page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE)
    {
        return $this->container->get($this->manager)->localList($category, $status, $query , $page, $pageSize);
    }


}