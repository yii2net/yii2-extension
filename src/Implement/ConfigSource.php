<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Interfaces\ConfigSourceInterface;
use Yikaikeji\Extension\Utils\JsonFile;

class ConfigSource implements ConfigSourceInterface
{
    const COMPOSER_JSON_NAME = "composer.json";

    private $mustComposerPlugins = [
        "oomphinc/composer-installers-extender"=>"dev-master"
    ];

    private $rootProjectPath;

    private $packageInstalledPath;

    private $packageScanPath;

    private $composerBin;

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
    public function getComposerBin()
    {
        return $this->composerBin;
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
                foreach ($composerSource['repositories'] as $k=>$repository){
                    if($repository['url'] == $path){
                        $hasRepo = true;
                        break;
                    }
                }
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
        print_r($composerSource);
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


}