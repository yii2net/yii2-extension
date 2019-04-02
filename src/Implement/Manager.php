<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Implement\EventArgs;
use Doctrine\Common\EventManager;
use Yikaikeji\Extension\Interfaces\ManagerInterface;
use Yikaikeji\Extension\Implement\ConfigSource;
use Jean85\PrettyVersions;
use Yikaikeji\Extension\Interfaces\PackageInterface;
use Yikaikeji\Extension\Implement\ArrayQuery;

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
     * @var array
     */
    private $localPackages = [];

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var array|\Yikaikeji\Extension\Implement\ConfigSource
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

        $this->config = new ConfigSource($config);
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
     * @param $category
     * @param $status
     * @param $query string match package name
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function localList($category='',$status='',$query='', $page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE)
    {
        $conditions = [];
        if(in_array($status,[
                Package::STATUS_DOWNLOADED,
                Package::STATUS_SETUPED,
                Package::STATUS_PAUSE,
                Package::STATUS_START
            ]))
        {
            $conditions['status'] = $status;
        }
        if($category && is_string($category)){
            $conditions['category'] = $category;
        }
        if($query && is_string($query)){
            $conditions['name'] = ['contains',$query];
        }
        $this->getAllLocalValidPackages();

        return $this->queryLocalValidPackages($conditions,$page,$pageSize);
    }

    /**
     * @return array
     */
    protected function getAllLocalValidPackages()
    {
        $VendorIterator = new \DirectoryIterator($this->config->getPackageInstalledPath());
        foreach ($VendorIterator as $vendorFile) {
            if ($vendorFile->isDir() && !$vendorFile->isDot()) {
                $vendorName = $vendorFile->getFilename();
                $PackageIterator = new \DirectoryIterator($this->config->getPackageInstalledPath().DIRECTORY_SEPARATOR.$vendorName);
                foreach ($PackageIterator as $packageFile) {
                    if ($packageFile->isDir() && !$packageFile->isDot()) {
                        $fileName = $packageFile->getFilename();
                        $packageName = $vendorName.DIRECTORY_SEPARATOR.$fileName;
                        $package = new Package($this->config,$packageName);
                        if($package->getPackageValidate()){
                            try{
                                PrettyVersions::getVersion($packageName);
                                $package->setStatus(Package::STATUS_SETUPED);
                            }catch (\OutOfBoundsException $e){
                                $package->setStatus(Package::STATUS_DOWNLOADED);
                            }
                            $this->localPackages[] = $package;
                        }
                    }
                }
            }
        }
        return $this->localPackages;
    }

    protected function queryLocalValidPackages($conditions=[],$page,$pageSize)
    {
        $result = [
            'total'=>0,
            'page' =>$page,
            'pageSize'=>$pageSize,
            'data'=>[]
        ];

        $queryConditions = [
            'category',
            'price',
            'requireFramework',
            'require',
            'status',
            'name'
        ];
        foreach ($conditions as $key=>$value){
            if(!in_array($key,$queryConditions)){
                unset($conditions[$key]);
            }
        }

        //gen array data
        $array = [];
        foreach ($this->localPackages as $package){
            if($package instanceof PackageInterface){
                $array[] = $package->toArray();
            }
        }

        try{
            /**
             * help: https://github.com/nahid/qarray
             */
            $ArrayQuery = new ArrayQuery($array);
            $ArrayQuery->from('.');
            foreach ($conditions as $key=>$value){
                if(is_string($value)){
                    $ArrayQuery->where($key,$value);
                }elseif(is_array($value) && count($value)==2){
                    $ArrayQuery->where($key,$value[0],$value[1]);
                }
            }
            $result['total'] = $ArrayQuery->count();
            $start = ($page - 1)*$pageSize;
            $result['data']  = $ArrayQuery->offset($start)->take($pageSize)->toArray();

        }catch (\Exception $e){
//            print_r($e->getMessage());
//            print_r($e->getTraceAsString());
        }
        return $result;
    }

}