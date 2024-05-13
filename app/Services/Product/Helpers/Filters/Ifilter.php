<?php

namespace App\Services\Product\Helpers\Filters;

interface Ifilter{
    public function setNextHandler(Ifilter $nextFilter);
    public function filter($products);
}