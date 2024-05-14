<?php

namespace App\Helpers;

use App\Exceptions\ResourceNotFoundException;

class ValidateResourceHelper{

    public static function ensureResourceExists($resource,$name){
        if (!$resource){
            throw ResourceNotFoundException::notFound($name);
        }
        return null;
    }
}