<?php

namespace App\Juni\Exception;

use RuntimeException;

class ResultTemplateMismatchException extends RuntimeException
{
    public function __construct(string $message = 'Result not matches original template.')
    {
        parent::__construct($message);
    }
}
