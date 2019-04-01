<?php
namespace Yikaikeji\Extension\Implement;

use Yikaikeji\Extension\Interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    private $installedPath = null;

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
        return $this->installedPath;
    }

    /**
     * @return mixed
     */
    public function getPackageConfigFileName()
    {
        return "composer.json";
    }


}