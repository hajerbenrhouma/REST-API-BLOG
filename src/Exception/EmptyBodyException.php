<?php

namespace App\Exception;

class EmptyBodyException extends \Exception
{
    public function __construct(
        $message = "",
        $code = 0,
        Exception $previous = null
    )
    {
        parent::__construct("The body of the POST/PUT method cannot be empty", $code, $previous);
    }
}