<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Interfaces\PackageInterface;
use Yikaikeji\Extension\Implement\ConfigSource;
use Yikaikeji\Extension\Utils\JsonFile;
use Yikaikeji\Extension\Utils\JsonValidationException;
use vierbergenlars\SemVer\version;

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
     * @var string require framework
     */
    private $requireFramework;
    /**
     * @var \Yikaikeji\Extension\Implement\ConfigSource
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

    private $_properties = ['description','keywords','price','authors','prettyName','extType','category','type','requireFramework'];

    public function __construct(ConfigSource $configSource, $packageName)
    {
        $this->name = $packageName;
        $this->configSource = $configSource;
        $this->_path  = $this->configSource->getPackageInstalledPath().DIRECTORY_SEPARATOR.$this->getName().DIRECTORY_SEPARATOR.$this->configSource->getPackageConfigFileName();
        $this->checkPackageConfig();

        if($this->getPackageValidate()){
            try{
                $this->_config = JsonFile::parseJson(file_get_contents($this->getPath()));
                //如果composer.json的name与getId()不一致就判断包有问题
                if($this->get('name') != $this->getName()){
                    $this->_config = [];
                    $this->_packageValidate = false;
                }else{
                    if($this->get('version')){
                        try{
                            $semver = new version($this->get('version'));
                            $this->version = $semver->valid();
                        }catch (\Exception $e){
                            //version not valid
                        }
                    }
                    $this->setStatus(static::STATUS_DOWNLOADED);
                    $this->setProperties();
                }
            }catch (\Exception $e){
                $this->_packageValidate = false;
            }
        }
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
        if($this->getPackageValidate() && isset($this->_config[$name])){
            return $this->_config[$name];
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
        $this->_config['extra'][$key] = $value;
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

}