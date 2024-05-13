<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductsImageResource;

class ProductFullResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    
    // returns an object where colors are keys and lists of images are values accordingly
    public function colorsObject(array $colors){
        $obj = []; 
        for ($i = 0 ; $i < count($colors) ; $i++){
            $color = $colors[$i];
            $obj[$color] = ProductsImageResource::collection($this->images()->whereHas("color", function($query)use(&$color){
                $query->where("color",$color);
            })->get());
        }
        return $obj;
    }
    
    public function toArray(Request $request): array
    {
        return [
            'id' =>$this->id,
            'name' => $this->name,
            'quantity' =>$this->quantity,
            'price' =>$this->price,
            'type' =>$this->type,
            "colors" =>$this->colors_array,
            "sizes" =>$this->sizes_array,

            "sizes_table" =>[
                'units'=>$this->sizes_units,
                'sizes'=> $this->sizes_lists,
            ],

            'reviews_summary'=>[
                'average_ratings'=> $this->average_ratings,
                'reviews_count' =>$this->reviews_count,
            ],
           
            "added_at"=>$this->created_at,
            'description' =>$this->description,
            'category' => $this->category->category,
            "sale" =>( $this->current_sale?
                [
                    'price_after_sale'=>$this->current_sale->price_after_sale,
                    'percentage'=>$this->current_sale->sale_percentage,
                    'starts_at' => $this->current_sale->starts_at,
                    'ends_at' => $this->current_sale->ends_at,
                ]
                :null
            ),

            'images' => $this->colorsObject($this->colors_array),
            "thumbnail" =>  new ProductsImageResource($this->thumbnail),
        ];
    }
}
