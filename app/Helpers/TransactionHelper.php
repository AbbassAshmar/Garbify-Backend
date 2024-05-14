<?php 

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Exceptions\TransactionFailedException;
use Exception;

class TransactionHelper{

    public static function makeTransaction($callback, $args){
        try{
            DB::beginTransaction();
            $result = $callback(...$args);
            DB::commit();
            return $result;
        }catch(Exception $e){
            DB::rollBack();
            throw TransactionFailedException::transactionFailed();
        }
    }
}