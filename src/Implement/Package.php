<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Interfaces\PackageInterface;
use Yikaikeji\Extension\Implement\Config;
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
    private $_id;

    /**
     * @var string version (semver)
     */
    private $_version;

    /**
     *
     * @var string status
     */
    private $_status;

    /**
     * @var \Yikaikeji\Extension\Implement\Config
     */
    private $config;

    /**
     * @var path of composer.json
     */
    private $_path;

    /**
     * @var array composer array
     */
    private $_config = [];

    private $_packageValidate = false;

    public function __construct(Config $config,$packageId)
    {
        $this->_id = $packageId;
        $this->config = $config;
        $this->_path  = $this->config->getPackageInstalledPath().DIRECTORY_SEPARATOR.$this->_id.DIRECTORY_SEPARATOR.$this->config->getPackageConfigFileName();
        $this->checkPackageConfig();

        if($this->getPackageValidate()){
            try{
                $this->_config = JsonFile::parseJson(file_get_contents($this->getPath()));
                //如果composer.json的name与getId()不一致就判断包有问题
                if($this->get('name') != $this->getId()){
                    $this->_config = [];
                    $this->_packageValidate = false;
                }else{
                    if($this->get('version')){
                        try{
                            $semver = new version($this->get('version'));
                            $this->_version = $semver->valid();
                        }catch (\Exception $e){
                            //version not valid
                        }
                    }
                    $this->setStatus(static::STATUS_DOWNLOADED);
                }
            }catch (\Exception $e){
                $this->_packageValidate = false;
            }
        }
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->_version;
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
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param $status
     * @return mixed
     */
    public function setStatus($status)
    {
        $this->_status = $status;
    }


}