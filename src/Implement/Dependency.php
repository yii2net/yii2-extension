<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Interfaces\DependencyInterface;
use Yikaikeji\Extension\Interfaces\PackageInterface;
use Jean85\PrettyVersions;
use Composer\Semver\Semver;

class Dependency implements DependencyInterface
{
    /**
     * 过滤环境依赖
     * 来自： Composer\Repository\PlatformRepository::PLATFORM_PACKAGE_REGEX
     */
    const PLATFORM_PACKAGE_REGEX = '{^(?:php(?:-64bit|-ipv6|-zts|-debug)?|hhvm|(?:ext|lib)-[a-z0-9](?:[_.-]?[a-z0-9]+)*|composer-plugin-api)$}iD';

    private $require = [];

    private $package;

    private $installedPackages = [];

    private $unInstalledPackages = [];

    private $skipPackages = [];

    public function __construct(Array $package)
    {
        $this->package = $package;
        $this->require = isset($this->package['require']) ? $this->package['require'] : [];
        $this->checkDependencies();
    }

    /**
     * 检查package的依赖安装情况
     */
    protected function checkDependencies()
    {
        $dependencies = $this->getDependencies();
        if($dependencies && is_array($dependencies)){
            foreach ($dependencies as $packageName=>$packageVersion){
                $this->checkRequiredPackageInstalledAndVersion($packageName,$packageVersion);
            }
        }
    }

    /**
     * 通过把package与composer安装的package对比，检查package的安装情况
     * @param $packageName
     * @param $packageVersion
     */
    protected function checkRequiredPackageInstalledAndVersion($packageName,$packageVersion)
    {
        try{
            $version = PrettyVersions::getVersion($packageName);
            $installedPackageVersion = $version->getShortVersion();
            if(Semver::satisfies($installedPackageVersion, $packageVersion) ){
                //安装的包，符合packageVersion的需求
                $this->setInstalledPackages($packageName,[$packageVersion,$installedPackageVersion]);
            }else{
                $this->setUnInstalledPackages($packageName,[$packageVersion,$installedPackageVersion]);
            }
        }catch (\OutOfBoundsException $e){
            if (!preg_match(self::PLATFORM_PACKAGE_REGEX, $packageName)) {
                //如果包名不合法也过滤掉
                if(stripos($packageName,"/")){
                    $this->setUnInstalledPackages($packageName,$packageVersion);
                }else{
                    $this->setSkipPackages($packageName, $packageVersion);
                }
            }else{
                $this->setSkipPackages($packageName, $packageVersion);
            }
        }catch (\Exception $e){
            $this->setUnInstalledPackages($packageName,$packageVersion);
        }
    }

    /**
     * @param $packageName
     * @param $packageVersion
     * @return mixed
     */
    public function isInstalled($packageName, $packageVersion)
    {
        $installedPackages = $this->getInstalledDependencies();
        if(isset($installedPackages[$packageName])){
            return true;
        }
        return false;
    }

    /**
     * @param $packageName
     * @param $packageVersion
     * @return mixed
     */
    public function isSkiped($packageName, $packageVersion)
    {
        $skipPackages      = $this->getSkipDependencies();
        if(isset($skipPackages[$packageName])){
            return true;
        }
        return false;
    }

    /**
     * @param $packageName
     * @param $packageVersion
     */
    public function setInstalledPackages($packageName, $packageVersion)
    {
        $this->installedPackages[$packageName] = $packageVersion;
    }

    /**
     * @param $packageName
     * @param $packageVersion
     */
    public function setUnInstalledPackages($packageName, $packageVersion)
    {
        $this->unInstalledPackages[$packageName] = $packageVersion;
    }

    /**
     * @param $packageName
     * @param $packageVersion
     */
    public function setSkipPackages($packageName, $packageVersion)
    {
        $this->skipPackages[$packageName] = $packageVersion;
    }


    /**
     * @param string $packageName
     * @return array|mixed|null
     */
    public function getInstalledDependencies($packageName='')
    {
        if($packageName){
            return isset($this->installedPackages[$packageName]) ? $this->installedPackages[$packageName] : null;
        }
        return $this->installedPackages;
    }

    /**
     * @return mixed
     */
    public function getDependencies()
    {
        return $this->require;
    }

    /**
     * @param string $packageName
     * @return array|mixed|null
     */
    public function getSkipDependencies($packageName='')
    {
        if($packageName){
            return isset($this->skipPackages[$packageName]) ? $this->skipPackages[$packageName] : null;
        }
        return $this->skipPackages;
    }

    /**
     * @return mixed
     */
    public function hasResolvedDependencies()
    {
        return empty($this->getUnInstalledDependencies());
    }

    /**
     * @param string $packageName
     * @return array|mixed|null
     */
    public function getUnInstalledDependencies($packageName='')
    {
        if($packageName){
            return isset($this->unInstalledPackages[$packageName]) ? $this->unInstalledPackages[$packageName] : null;
        }
        return $this->unInstalledPackages;
    }

}