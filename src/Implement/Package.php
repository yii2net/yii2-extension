<?php
namespace Openadm\Extension\Implement;

use Openadm\Extension\Interfaces\PackageInterface;
use Openadm\Extension\Implement\ConfigSource;
use Openadm\Extension\Utils\JsonFile;
use Openadm\Extension\Utils\JsonValidationException;
use Composer\Semver\VersionParser;

class Package implements PackageInterface
{
    /**
     * pause
     */
    const STATUS_PAUSE = 'pause';

    /**
     *
     */
    const STATUS_START = 'start';

    /**
     *
     */
    const STATUS_SETUPED = 'setuped';

    /**
     *
     */
    const STATUS_DOWNLOADED = 'downloaded';

    /**
     * @var array 包名和真实的目录对应关系
     */
    private $autoloadMap = [];
    /**
     * @var packageName对应的真实目录
     */
    private $dirName;
    /**
     * @var string package id
     */
    private $name;
    /**
     * @var string pretty name
     */
    private $prettyName;
    /**
     * @var string extension type
     */
    private $extType;
    /**
     * @var string version (semver)
     */
    private $version;
    /**
     * @var float price
     */
    private $price;
    /**
     *
     * @var string status
     */
    private $status;
    /**
     * @var array authors
     */
    private $authors;
    /**
     * @var string $category
     */
    private $category;
    /**
     * @var string package type
     */
    private $type;
    /**
     * @var string
     */
    private $description;
    /**
     * @var
     */
    private $keywords;
    /**
     * @var array
     */
    private $require;
    /**
     * @var string require framework
     */
    private $requireFramework;
    /**
     * @var \Openadm\Extension\Implement\ConfigSource
     */
    private $configSource;

    /**
     * @var path of composer.json
     */
    private $_path;

    /**
     * @var array composer array
     */
    private $_config = [];

    private $_packageValidate = false;

    private $_properties = ['require','description','keywords','price','authors','prettyName','extType','category','type','requireFramework'];

    public function __construct(ConfigSource $configSource, $packageName, $packageDir='')
    {
        //这个包名是外围的目录名称，不是真实的包名
        $this->dirName = $packageName;
        $this->name = $packageName;//默认
        $this->configSource = $configSource;
        if(!$packageDir){
            $packageDir = $this->configSource->getPackageScanPath();
        }
        $this->_path  = $packageDir.DIRECTORY_SEPARATOR.$this->getDirName().DIRECTORY_SEPARATOR.$this->configSource->getPackageConfigFileName();
        $this->checkPackageConfig();

        if($this->getPackageValidate()){
            try{
                $this->_config = JsonFile::parseJson(file_get_contents($this->getPath()));
                //先尝试解析autoload，把packageName和真实的目录对应起来
                $this->parseAutoload();
                //如果composer.json的name与getName()不一致就判断包有问题
                if($this->get('name') != $this->getDirName()){
                    $this->_config = [];
                    $this->_packageValidate = false;
                }else{
                    if($this->get('version') && $this->checkValidVersion($this->get('version'))){
                        $this->version = $this->get('version');
                    }
                    $this->setStatus(static::STATUS_DOWNLOADED);
                    //检测composer extra meta=>string/array
                    if(isset($this->_config['extra'][$this->configSource->getExtraNamespace()]) && is_string($this->_config['extra'][$this->configSource->getExtraNamespace()])){
                        $extraConfigFile = $this->configSource->getPackageScanPath() . DIRECTORY_SEPARATOR . $this->getName() . DIRECTORY_SEPARATOR . $this->_config['extra'][$this->configSource->getExtraNamespace()];
                        if(is_file($extraConfigFile)){
                            $extraConfig = include $extraConfigFile;
                            if(is_array($extraConfig)){
                                $this->_config['extra'][$this->configSource->getExtraNamespace()] = $extraConfig;
                            }
                        }
                    }
                    $this->setProperties();
                }
            }catch (\Exception $e){
                if($this->configSource->isDebug()){
                    print_r($e->getMessage());
                }
                $this->_packageValidate = false;
            }
        }
    }

    private function parseAutoload()
    {
        $config = $this->_config;
        if(isset($config['autoload']) && !empty($config['autoload'])){
            $autoload = $config['autoload'];
            if(isset($autoload['psr-4']) && !empty($autoload['psr-4'])){
                foreach ($autoload['psr-4'] as $prefix => $dir){
                    $this->autoloadMap[$prefix] = $dir;
                }
            }
        }
    }

    /**
     * 强制要求版本号v0.0.0 要有三个数字
     * @param $version
     * @return bool
     */
    private function checkValidVersion($version)
    {
        if(preg_match('/^v?(\d{1,5})(\.\d++)(\.\d++)/i',$version)){
            $versionParser = new VersionParser();
            if($versionParser->normalize($version)){
                return true;
            }
        }
        throw new \UnexpectedValueException('Invalid version string "' . $version . '"');
    }

    private function setProperties()
    {
        foreach ($this->_properties as $property){
            $this->{$property} = $this->get($property);
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @return bool
     */
    public function checkPackageConfig()
    {
        $this->_packageValidate = false;
        try{
            if(is_file($this->getPath())){
                $composer = new JsonFile($this->getPath());
                $this->_packageValidate = $composer->validateSchema();
            }
        }catch (JsonValidationException $e){

        }
        return $this->_packageValidate;
    }

    /**
     * @return bool
     */
    public function getPackageValidate()
    {
        return $this->_packageValidate;
    }

    /**
     * get composer json property
     * @param $name
     * @return mixed|null
     */
    public function get($name)
    {
        $extraNamespace = $this->configSource->getExtraNamespace();
        if($this->getPackageValidate() && isset($this->_config[$name])){
            return $this->_config[$name];
        }elseif($this->getPackageValidate() && isset($this->_config['extra'][$extraNamespace][$name])){
            return $this->_config['extra'][$extraNamespace][$name];
        }elseif($this->getPackageValidate() && isset($this->_config['extra'][$name])){
            return $this->_config['extra'][$name];
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return mixed
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getPrettyName()
    {
        return $this->prettyName;
    }

    /**
     * @return mixed
     */
    public function getExtType()
    {
        return $this->extType;
    }

    /**
     * @return mixed
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setExtra($key, $value)
    {
        $extraNamespace = $this->configSource->getExtraNamespace();
        $this->_config['extra'][$extraNamespace][$key] = $value;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return mixed
     */
    public function getRequireFramework()
    {
        return $this->requireFramework;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    public function toArray()
    {
        $array = [];
        foreach ($this as $property=>$value){
            $method = "get".ucfirst($property);
            if(method_exists($this,$method)){
                $array[$property] = call_user_func([$this,$method]);
            }
        }
        return $array;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return join(',',$this->keywords);
    }

    /**
     * @return mixed
     */
    public function getRequire()
    {
        return $this->require;
    }

    public function getDirName()
    {
        return $this->dirName;
    }

    public function hasComposerInstalled()
    {
        return $this->getInstalledPath() ? true : false;
    }

    public function getInstalledPath()
    {
        if(is_dir($this->configSource->getVendorPath().DIRECTORY_SEPARATOR.$this->getDirName())){
            return $this->configSource->getVendorPath().DIRECTORY_SEPARATOR.$this->getDirName();
        }else if(is_dir($this->configSource->getPackageInstalledPath().DIRECTORY_SEPARATOR.$this->getDirName())){
            return $this->configSource->getPackageInstalledPath().DIRECTORY_SEPARATOR.$this->getDirName();
        }
        return null;
    }
}