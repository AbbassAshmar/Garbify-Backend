<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductsImageResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' =>$this->id,
            'name' => $this->name,
            'quantity' =>$this->quantity,
            'price' =>$this->price,
            'type' =>$this->type,
            "colors" =>$this->colors_array,
            "added_at"=>$this->created_at,
            "sale" => $this->when($this->current_sale ,$this->current_sale?
                [
                    'price_after_sale'=>$this->current_sale->price_after_sale,
                    'percentage'=>$this->current_sale->sale_percentage,
                    'starts_at' => $this->current_sale->starts_at,
                    'ends_at' => $this->current_sale->ends_at,
                ]
                :null
            ),
            "thumbnail" =>  new ProductsImageResource($this->thumbnail),
        ];
    }
}
