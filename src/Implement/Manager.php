<?php
namespace Openadm\Extension\Implement;

use Openadm\Extension\Implement\EventArgs;
use Doctrine\Common\EventManager;
use Openadm\Extension\Interfaces\ManagerInterface;
use Openadm\Extension\Implement\ConfigSource;
use Openadm\Extension\Interfaces\PackageInterface;
use Openadm\Extension\Implement\ArrayQuery;
use Openadm\Extension\Implement\Dependency;
use Openadm\Extension\Implement\Package;
use Openadm\Extension\Implement\Composer;
use Openadm\Extension\Implement\Version;

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
     * @var array|\Openadm\Extension\Implement\ConfigSource
     */
    private $configSource;

    /**
     * @var \Openadm\Extension\Implement\Composer
     */
    private $composer;

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

        $this->configSource = new ConfigSource($config);
        $this->composer     = new Composer($this->configSource);
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
     * @param $packageName
     * @param \Openadm\Extension\Interfaces\local $locate
     * @return mixed
     */
    public function unSetup($packageName,$locate=self::LOCATE_LOCAL)
    {
        $eventArgs = new EventArgs(['locate'=>$locate,'packageName'=>$packageName]);
        $this->eventManager->dispatchEvent(self::EVENT_BEFORE_UNSETUP,$eventArgs);
        $this->doUnsetup($eventArgs);
        $this->eventManager->dispatchEvent(self::EVENT_AFTER_UNSETUP,$eventArgs);
        return $eventArgs->result;
    }

    /**
     * @param \Openadm\Extension\Implement\EventArgs $eventArgs
     * @return string
     */
    protected function doUnsetup(EventArgs $eventArgs)
    {
        $result = '';
        //修改root project composer.json
        $path = '';
        if($eventArgs->locate == self::LOCATE_LOCAL){
            $path = $this->configSource->getPackageScanPath().DIRECTORY_SEPARATOR.$eventArgs->packageName;
            $package = new Package($this->configSource,$eventArgs->packageName);
            $eventArgs->packageVersion = $package->getVersion();
        }
        //关闭后 加速卸载
        $this->configSource->disablePackagist();
        $this->composer->remove($eventArgs->packageName,$this->configSource->onUnSetupCallback());
        $this->configSource->removePackageToComposer($eventArgs->packageName,$eventArgs->packageVersion,$path);
        $this->configSource->enablePackagist();
        $eventArgs->result = $result;
        return $result;
    }

    /**
     * @param $locate
     * @param $packageName
     * @param $packageVersion
     */
    public function setup($packageName, $packageVersion, $locate=self::LOCATE_LOCAL)
    {
        $eventArgs = new EventArgs(['locate'=>$locate,'packageName'=>$packageName,'packageVersion'=>$packageVersion]);
        $this->eventManager->dispatchEvent(self::EVENT_BEFORE_SETUP,$eventArgs);
        $this->doSetup($eventArgs);
        $this->eventManager->dispatchEvent(self::EVENT_AFTER_SETUP,$eventArgs);
        return $eventArgs;
    }

    /**
     * @param \Openadm\Extension\Implement\EventArgs $eventArgs
     * @return string
     */
    protected function doSetup(EventArgs $eventArgs)
    {
        $result = '';
        //修改root project composer.json
        $path = '';
        $package = null;
        if($eventArgs->locate == self::LOCATE_LOCAL){
            $path = $this->configSource->getPackageScanPath().DIRECTORY_SEPARATOR.$eventArgs->packageName;
            $package = new Package($this->configSource,$eventArgs->packageName);
            $eventArgs->packageVersion = $package->getVersion();
            $unInstalledPackages = $this->configSource->checkCanDisablePackagist($package);
            if(is_array($unInstalledPackages) && !empty($unInstalledPackages)){
                //说明有缺失的依赖，此时进行自动安装
                foreach ($unInstalledPackages as $packageName=>$packageVersion){
                    $ver = is_array($packageVersion) ? $packageVersion[0] : $packageVersion;
                    $this->composer->requireN([$packageName.":".$ver],$this->configSource->onSetupCallback());
                }
            }
        }

        $this->configSource->addPackageToComposer($eventArgs->packageName,$eventArgs->packageVersion,$path);
        //要在addPackageToComposer之后
        if($this->configSource->getCanDisablePackagist()){
            $this->configSource->disablePackagist();
        }
        $this->composer->update([],$this->configSource->onSetupCallback());
        $this->configSource->enablePackagist();
        $eventArgs->result = $package->hasComposerInstalled();
        return $eventArgs->result;
    }

    /**
     * @param $packageName
     * @return mixed
     */
    public function delete($packageName)
    {
        $eventArgs = new EventArgs(['packageName'=>$packageName]);
        $this->eventManager->dispatchEvent(self::EVENT_BEFORE_DELETE,$eventArgs);
        $this->doDelete($eventArgs);
        $this->eventManager->dispatchEvent(self::EVENT_AFTER_DELETE,$eventArgs);
        return $eventArgs->result;
    }

    /**
     * @param \Openadm\Extension\Implement\EventArgs $eventArgs
     * @return string
     */
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
    public function remoteList( $category='',$query='', $page=self::DEFAULT_PAGE,$pageSize=self::DEFAULT_PAGESIZE)
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

    public function getPackage($packageName)
    {
        $package = new Package($this->configSource,$packageName);
        if($package->getPackageValidate()){
            try{
                Version::getVersion($packageName);
                $package->setStatus(Package::STATUS_SETUPED);
            }catch (\OutOfBoundsException $e){
                $package->setStatus(Package::STATUS_DOWNLOADED);
            }
            return $package;
        }
        return null;
    }

    public function getPackageWithoutValidate($packageName)
    {
        return new Package($this->configSource,$packageName);
    }

    /**
     * @return array
     */
    protected function getAllLocalValidPackages()
    {
        $VendorIterator = new \DirectoryIterator($this->configSource->getPackageScanPath());
        foreach ($VendorIterator as $vendorFile) {
            if ($vendorFile->isDir() && !$vendorFile->isDot()) {
                $vendorName = $vendorFile->getFilename();
                $PackageIterator = new \DirectoryIterator($this->configSource->getPackageScanPath().DIRECTORY_SEPARATOR.$vendorName);
                foreach ($PackageIterator as $packageFile) {
                    if ($packageFile->isDir() && !$packageFile->isDot()) {
                        $fileName = $packageFile->getFilename();
                        $packageName = $vendorName.DIRECTORY_SEPARATOR.$fileName;
                        $package = $this->getPackage($packageName);
                        if($package){
                            $this->localPackages[] = $package;
                        }
                    }
                }
            }
        }
        return $this->localPackages;
    }

    /**
     * @param array $conditions
     * @param $page
     * @param $pageSize
     * @return array
     */
    protected function queryLocalValidPackages($conditions=[], $page, $pageSize)
    {
        $page = intval($page);
        $pageSize = intval($pageSize);
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
            foreach ($result['data'] as $k=>$package){
                $dependency = new Dependency($package);
                $result['data'][$k]['unInstalledDependencies'] = $dependency->getUnInstalledDependencies();
            }
        }catch (\Exception $e){
//            print_r($e->getMessage());
//            print_r($e->getTraceAsString());
        }
        return $result;
    }
}