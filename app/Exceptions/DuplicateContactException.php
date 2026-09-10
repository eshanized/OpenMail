<?php

namespace App\Exceptions;

use Exception;

class DuplicateContactException extends Exception
{
    public function __construct(string $message = 'Contact with this email already exists', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
