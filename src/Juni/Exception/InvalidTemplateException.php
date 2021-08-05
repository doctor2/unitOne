<?php

namespace App\Juni\Exception;

use RuntimeException;

class InvalidTemplateException extends RuntimeException
{
    public function __construct(string $message = 'Invalid template.')
    {
        parent::__construct($message);
    }
}
