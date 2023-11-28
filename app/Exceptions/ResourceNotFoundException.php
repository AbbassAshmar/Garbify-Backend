<?php

namespace App\Exceptions;

use Exception;

class ResourceNotFoundException extends Exception
{
    public static function notFound($resource){
        return new static("$resource not found.");
    }
}
