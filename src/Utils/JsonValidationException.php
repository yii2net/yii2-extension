<?php
namespace Yikaikeji\Extension\Utils;

use Exception;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class JsonValidationException extends Exception
{
    protected $errors;

    public function __construct($message, $errors = array(), Exception $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, 0, $previous);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
