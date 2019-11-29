<?php

namespace App\Exception;


class InvalidTokenConfirmationException extends  \Exception
{
    public function __construct(
        $message = "",
        $code = 0,
        Exception $previous = null
    )
    {
        parent::__construct("Confirmation token is invalid.", $code, $previous);
    }
}