<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use SebastianBergmann\CodeCoverage\Report\Xml\Totals;

class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    protected $total_count;

    public function setTotalCount($count){
        $this->total_count = $count;
        return $this;
    }

    public function toArray(Request $request): array
    {
      
        return[
            'data' => $this->collection,
            'count' => $this->collection->count(),
            'total_count' =>$this->total_count,
        ];
    }
}
