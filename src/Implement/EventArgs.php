<?php
namespace Yikaikeji\Extension\Implement;

use Doctrine\Common\EventArgs as BaseEventArgs;

/**
 * Class EventArgs
 * Save Event Result in this Static Class
 * @package Yikaikeji\Extension\Implement
 */
class EventArgs extends BaseEventArgs
{
    public $result;

    public $packageName;

    public $packageVersion;

    public $locate;

    public $params;

    /**
     * EventArgs constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $initFields = ['packageName','packageVersion','locate'];
        foreach ($initFields as $field){
            if(isset($params[$field])){
                $this->{$field} = $params[$field];
                unset($params[$field]);
            }
        }
        $this->params = $params;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        if(property_exists($this,$key)){
            return $this->{$key};
        }elseif(isset($this->params[$key])){
            return $this->params[$key];
        }
        return null;
    }
}