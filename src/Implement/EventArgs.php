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

    public $extensionId;

    public $params;

    /**
     * EventArgs constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        if(isset($params['extensionId'])){
            $this->extensionId = $params['extensionId'];
            unset($params['extensionId']);
        }
        $this->params = $params;
    }
}