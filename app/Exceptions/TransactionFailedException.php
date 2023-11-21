<?php

namespace App\Exceptions;

use Exception;

class TransactionFailedException extends Exception
{
    public static function transactionFailed(){
        return new static("Transaction failed.");
    }
}
