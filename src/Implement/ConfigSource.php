<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Interfaces\ConfigSourceInterface;
use Yikaikeji\Extension\Interfaces\PackageInterface;
use Yikaikeji\Extension\Utils\JsonFile;
use Yikaikeji\Extension\Implement\Dependency;
use Yikaikeji\Extension\Implement\Composer;

/**
 * Class ConfigSource
 * @package Yikaikeji\Extension\Implement
 */
class ConfigSource implements ConfigSourceInterface
{
    /**
     *
     */
    const COMPOSER_JSON_NAME = "composer.json";

    const LOG_LEVEL_INFO = "info";

    const LOG_LEVEL_DEBUG = "debug";

    private $logLevel = 'info';

    private $canDisablePackagist = false;


    /**
     * @var array
     */
    private $mustComposerPlugins = [
        "oomphinc/composer-installers-extender"=>"dev-master"
    ];

    /**
     * @var
     */
    private $rootProjectPath;

    /**
     * @var
     */
    private $packageInstalledPath;

    /**
     * @var
     */
    private $packageScanPath;

    /**
     * @var
     */
    private $composerPath;

    /**
     * @var
     */
    private $onSetupCallback;

    /**
     * @var
     */
    private $onUnSetupCallback;

    /**
     * @var
     */
    private $onDeleteCallback;

    private $extraNamespace = 'meta';

    private $composerSourceList = [
        'packagist' => ['type'=>'composer','url'=>'https://packagist.laravel-china.org'],
        'asset' => ['type'=>'composer','url'=>'https://asset-packagist.org'],
    ];

    /**
     * ConfigSource constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        foreach ($config as $property=>$value){
            if(property_exists($this,$property)){
                $this->{$property} = $value;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * @return mixed
     */
    public function getPackageInstalledPath()
    {
        return $this->packageInstalledPath;
    }

    /**
     * @return mixed
     */
    public function getPackageConfigFileName()
    {
        return static::COMPOSER_JSON_NAME;
    }

    /**
     * @return mixed
     */
    public function getPackageScanPath()
    {
        return $this->packageScanPath;
    }

    /**
     * @return mixed
     */
    public function getRootProjectPath()
    {
        return $this->rootProjectPath;
    }

    /**
     * @return mixed
     */
    public function getComposerPath()
    {
        return $this->composerPath;
    }

    /**
     * @param $packageName
     * @param $packageVersion
     * @param string $path
     * @return mixed
     */
    public function addPackageToComposer($packageName, $packageVersion, $path = '')
    {
        $composerPath = $this->getRootProjectPath().DIRECTORY_SEPARATOR.$this->getPackageConfigFileName();
        try{
            $composerSource = JsonFile::parseJson(file_get_contents($composerPath));
            if(isset($composerSource['require']) && is_array($composerSource['require'])){
                $composerSource['require'][$packageName] = $packageVersion;
            }
            $composerSource = $this->setInstalledPaths($composerSource,$packageName);
            if($path && is_dir($path)){
                if(!isset($composerSource['repositories'])){
                    $composerSource['repositories'] = [];
                }
                $hasRepo = false;
                $repositories = [];
                foreach ($composerSource['repositories'] as $k=>$repository){
                    if($repository['url'] == $path){
                        $hasRepo = true;
                    }
                    if(!$this->shouldSkip($k,$repository)){
                        $repositories[$k] = $repository;
                    }
                }
                $composerSource['repositories'] = $repositories;
                if(!$hasRepo){
                    $composerSource['repositories'][] = [
                        'type' => 'path',
                        'url'  => $path
                    ];
                }
            }
            file_put_contents($composerPath,JsonFile::encode($composerSource,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            return true;
        }catch (\Exception $e){
            //todo
        }
        return false;
    }

    /**
     * 默认是过滤掉type = composer的源，程序自动补充对应的源
     * @param $key
     * @param $repo
     * @return bool
     */
    private function shouldSkip($key,$repo)
    {
        if(is_array($this->composerSourceList) && !empty($this->composerSourceList)){
            if(isset($this->composerSourceList[$key])){
                return true;
            }
            foreach ($this->composerSourceList as $k=>$v){
                if($v == $repo){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $packageName
     * @param $packageVersion
     * @param string $path
     * @return mixed
     */
    public function removePackageToComposer($packageName, $packageVersion, $path = '')
    {
        $composerPath = $this->getRootProjectPath().DIRECTORY_SEPARATOR.$this->getPackageConfigFileName();
        try{
            $composerSource = JsonFile::parseJson(file_get_contents($composerPath));
            $composerSource = $this->removeInstalledPaths($composerSource,$packageName);
            if($path && is_dir($path)){
                if(!isset($composerSource['repositories'])){
                    $composerSource['repositories'] = [];
                }
                $repositories = [];
                foreach ($composerSource['repositories'] as $k=>$repository){
                    if($repository['url'] != $path){
                        if(!$this->shouldSkip($k,$repository)){
                            $repositories[$k] = $repository;
                        }
                    }
                }
                $composerSource['repositories'] = $repositories;
            }
            file_put_contents($composerPath,JsonFile::encode($composerSource,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            return true;
        }catch (\Exception $e){
            //todo
        }
        return false;
    }

    /**
     * @param $composerSource
     * @return mixed
     */
    public function checkMustComposerPlugins($composerSource)
    {
        if(!empty($this->mustComposerPlugins)){
            $installedPackages = array_keys($composerSource['require']);
            foreach ($this->mustComposerPlugins as $packageName=>$packageVersion){
                if(!in_array($packageName,$installedPackages)){
                    $composerSource['require'][$packageName] = $packageVersion;
                }
            }
        }
        //config installer paths
        if(!isset($composerSource['extra'])){
            $composerSource['extra'] = [];
        }
        $composerSource['extra']['installer-types'] = ['library'];
        $composerSource['extra']['installer-paths'] = [];
        return $composerSource;
    }

    /**
     * 把要安装的包，配置在installer-paths里面
     * @param $composerSource
     * @param $packageName
     * @return mixed
     */
    public function setInstalledPaths($composerSource,$packageName)
    {
        $composerSource = $this->checkMustComposerPlugins($composerSource);
        $dest = $this->getPackageInstalledPath().DIRECTORY_SEPARATOR.'{$vendor}'.DIRECTORY_SEPARATOR.'{$name}';
        $dests = array_keys($composerSource['extra']['installer-paths']);
        if(!in_array($dest,$dests)){
            $composerSource['extra']['installer-paths'][$dest] = [];
        } elseif(!is_array($composerSource['extra']['installer-paths'][$dest])){
            $composerSource['extra']['installer-paths'][$dest] = [];
        }
        if(!in_array($packageName,$composerSource['extra']['installer-paths'][$dest])){
            $composerSource['extra']['installer-paths'][$dest][] = $packageName;
        }
        return $composerSource;
    }

    /**
     * 把包从installer-paths里面删除
     * @param $composerSource
     * @param $packageName
     * @return mixed
     */
    public function removeInstalledPaths($composerSource,$packageName)
    {
        $dest = $this->getPackageInstalledPath().DIRECTORY_SEPARATOR.'{$vendor}'.DIRECTORY_SEPARATOR.'{$name}';
        if(in_array($packageName,$composerSource['extra']['installer-paths'][$dest])){
            foreach ($composerSource['extra']['installer-paths'][$dest] as $k=>$p){
                if($p == $packageName){
                    unset($composerSource['extra']['installer-paths'][$dest][$k]);
                    break;
                }
            }
            if(empty($composerSource['extra']['installer-paths'][$dest])){
                $composerSource['extra']['installer-paths'] = [];
            }
        }
        return $composerSource;
    }

    /**
     * @return mixed
     */
    public function onSetupCallback()
    {
        return $this->onSetupCallback;
    }

    /**
     * @return mixed
     */
    public function onUnSetupCallback()
    {
        return $this->onUnSetupCallback;
    }

    /**
     * @return mixed
     */
    public function onDeleteCallback()
    {
        return $this->onDeleteCallback;
    }

    public function isDebug()
    {
        return $this->getLogLevel() == self::LOG_LEVEL_DEBUG;
    }

    /**
     * @return mixed
     */
    public function getExtraNamespace()
    {
        return $this->extraNamespace;
    }

    public function checkCanDisablePackagist(PackageInterface $package)
    {
        $this->canDisablePackagist = false;
        $dependency = new Dependency($package->toArray());
        $un = $dependency->getUnInstalledDependencies();
        if(empty($un)){
            $this->canDisablePackagist = true;
        }
        return $this->canDisablePackagist;
    }

    /**
     *
     */
    public function disablePackagist()
    {
        if(is_array($this->composerSourceList) && !empty($this->composerSourceList)){
            $composer = new Composer($this);
            foreach ($this->composerSourceList as $key=>$repo){
                $composer->config(['repositories.' . $key, 'false'],null);
            }
        }
    }

    /**
     *
     */
    public function enablePackagist()
    {
        if(is_array($this->composerSourceList) && !empty($this->composerSourceList)){
            $composer = new Composer($this);
            foreach ($this->composerSourceList as $key=>$repo){
                $composer->config(['repositories.' . $key, $repo['type'] , $repo['url']],null);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getCanDisablePackagist()
    {
        return $this->canDisablePackagist;
    }

}