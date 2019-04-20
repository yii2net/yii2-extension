<?php
namespace Openadm\Extension\Implement;

use Nahid\QArray\QueryEngine;
use Openadm\Extension\Utils\JsonFile;

/**
 * Class ArrayQuery
 * @package Openadm\Extension\Implement
 */
class ArrayQuery extends QueryEngine
{

    public function __construct($data = null)
    {
        if(is_array($data)){
            $this->collect($data);
        }elseif(is_string($data)){
            if (is_file($data) || filter_var($data, FILTER_VALIDATE_URL)) {
                if (file_exists($data)) {
                    $this->collect($this->readPath($data));
                }
            }
        }
    }

    public function readPath($path)
    {
        try{
            return JsonFile::parseJson(file_get_contents($path));
        }catch (\Exception $e){
            return null;
        }
    }

    public function parseData($data)
    {
        if(is_array($data)){
            return $data;
        }else{
            try{
                return JsonFile::parseJson($data);
            }catch (\Exception $e){
                return null;
            }
        }
    }
}