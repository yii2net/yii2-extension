<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Implement\EventArgs;
use Doctrine\Common\EventManager;
use Yikaikeji\Extension\Interfaces\ManagerInterface;
use Yikaikeji\Extension\Implement\Config;
use Jean85\PrettyVersions;
use Yikaikeji\Extension\Interfaces\PackageInterface;

class Manager implements ManagerInterface
{
    /**
     * @event Event an event raised before the extension manager starts to setup.
     */
    const EVENT_BEFORE_SETUP = "beforeSetup";
    /**
     * @event Event an event raised before the extension manager successfully setup.
     */
    const EVENT_AFTER_SETUP = "afterSetup";
    /**
     * @event Event an event raised before the extension manager starts to unsetup.
     */
    const EVENT_BEFORE_UNSETUP = "beforeUnSetup";
    /**
     * @event Event an event raised before the extension manager successfully unsetup.
     */
    const EVENT_AFTER_UNSETUP = "afterUnSetup";
    /**
     * @event Event an event raised before the extension manager starts to delete.
     */
    const EVENT_BEFORE_DELETE = "beforeDelete";
    /**
     * @event Event an event raised before the extension manager successfully delete.
     */
    const EVENT_AFTER_DELETE = "afterDelete";
    /**
     * @event Event an event raised before the extension manager starts to download.
     */
    const EVENT_BEFORE_DOWNLOAD = "beforeDownload";
    /**
     * @event Event an event raised before the extension manager successfully download.
     */
    const EVENT_AFTER_DOWNLOAD = "afterDownload";

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var array|\Yikaikeji\Extension\Implement\Config
     */
    private $config;

    /**
     * Manager constructor.
     * @param EventManager $eventManager
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     *  set config
     * @param $config
     */
    public function init(&$config)
    {
        if(isset($config['events']) && is_array($config['events'])){
            foreach ($config['events'] as $eventName=>$listener){
                $this->attachEvent($eventName,$listener);
            }
        }

        $this->config = new Config($config);
    }

    /**
     * attach Event
     * @param $eventName
     * @param $listener
     */
    private function attachEvent($eventName,$listener)
    {
        switch ($eventName){
            case self::EVENT_BEFORE_SETUP:
            case self::EVENT_BEFORE_UNSETUP:
            case self::EVENT_BEFORE_DOWNLOAD:
            case self::EVENT_BEFORE_DELETE:
            case self::EVENT_AFTER_SETUP:
            case self::EVENT_AFTER_UNSETUP:
            case self::EVENT_AFTER_DOWNLOAD:
            case self::EVENT_AFTER_DELETE:
                $this->eventManager->addEventListener([$eventName], $listener);
                break;
        }

    }

    /**
     * @param $extendisonId
     */
    public function Unsetup($extensionId)
    {
        $eventArgs = new EventArgs(['extensionId'=>$extensionId]);
        $this->eventManager->dispatchEvent(self::EVENT_BEFORE_UNSETUP,$eventArgs);
        $this->doUnsetup($eventArgs);
        $this->eventManager->dispatchEvent(self::EVENT_AFTER_UNSETUP,$eventArgs);
        return $eventArgs->result;
    }

    protected function doUnsetup(EventArgs $eventArgs)
    {
        $result = '';
        $eventArgs->result = $result;
        return $result;
    }

    /**
     * @param $extensionId
     */
    public function Setup($extensionId)
    {
        $eventArgs = new EventArgs(['extensionId'=>$extensionId]);
        $this->eventManager->dispatchEvent(self::EVENT_BEFORE_SETUP,$eventArgs);
        $this->doSetup($eventArgs);
        $this->eventManager->dispatchEvent(self::EVENT_AFTER_SETUP,$eventArgs);
        return $eventArgs->result;
    }

    protected function doSetup(EventArgs $eventArgs)
    {
        $result = '';
        $eventArgs->result = $result;
        return $result;
    }

    /**
     * @param $extensionId
     * @return mixed
     */
    public function Delete($extensionId)
    {
        $eventArgs = new EventArgs(['extensionId'=>$extensionId]);
        $this->eventManager->dispatchEvent(self::EVENT_BEFORE_DELETE,$eventArgs);
        $this->doDelete($eventArgs);
        $this->eventManager->dispatchEvent(self::EVENT_AFTER_DELETE,$eventArgs);
        return $eventArgs->result;
    }

    protected function doDelete(EventArgs $eventArgs)
    {
        $result = '';
        $eventArgs->result = $result;
        return $result;
    }

    /**
     * @param $category
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function remoteList( $category='', $page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE)
    {
        // TODO: Implement list() method.
    }

    /**
     * @param $status
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function localList($status='', $page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE)
    {
        $result = [
            'total'=>0,
            'page' =>$page,
            'pageSize'=>$pageSize,
            'data'=>[]
        ];

        $packages = $this->getAllLocalValidPackages();
        if(count($packages)>0 && in_array($status,[
            Package::STATUS_DOWNLOADED,
            Package::STATUS_SETUPED,
            Package::STATUS_PAUSE,
            Package::STATUS_START
        ])){
            $packages = $this->getLocalValidPackages($packages,$status);
        }

        $result['total'] = count($packages);
        if($result['total']>0){
            $start = ($page - 1) * $pageSize;
            $result['data'] = array_slice($packages,$start,$pageSize,false);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getAllLocalValidPackages()
    {
        $packages = [];
        $VendorIterator = new \DirectoryIterator($this->config->getPackageInstalledPath());
        foreach ($VendorIterator as $vendorFile) {
            if ($vendorFile->isDir() && !$vendorFile->isDot()) {
                $vendorName = $vendorFile->getFilename();
                $PackageIterator = new \DirectoryIterator($this->config->getPackageInstalledPath().DIRECTORY_SEPARATOR.$vendorName);
                foreach ($PackageIterator as $packageFile) {
                    if ($packageFile->isDir() && !$packageFile->isDot()) {
                        $packageName = $packageFile->getFilename();
                        $packageId = $vendorName.DIRECTORY_SEPARATOR.$packageName;
                        $package = new Package($this->config,$packageId);
                        if($package->getPackageValidate()){
                            try{
                                PrettyVersions::getVersion($packageId);
                                $package->setStatus(Package::STATUS_SETUPED);
                            }catch (\OutOfBoundsException $e){
                                $package->setStatus(Package::STATUS_DOWNLOADED);
                            }
                            $packages[] = $package;
                        }
                    }
                }
            }
        }
        return $packages;
    }

    /**
     * @param array $packages
     * @param $status
     * @return array
     */
    protected function getLocalValidPackages(Array $packages,$status)
    {
        $list = [];
        foreach ($packages as $package){
            if($package instanceof PackageInterface && $package->getStatus() == $status){
                $list[] = $package;
            }
        }
        return $list;
    }


}