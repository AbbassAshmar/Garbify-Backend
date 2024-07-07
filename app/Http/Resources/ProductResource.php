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
    
    // returns an object where colors are keys and lists of images are values accordingly
    public function colorsImagesObject(array $colors){
        $obj = []; 
        foreach ($colors as $color){
            $obj[$color] =$this->images()->whereHas("color", function($query)use(&$color){
                $query->where("color",$color);
            })->get();
        }
        return $obj;
    }


    public function toArray(Request $request): array
    {
        return [
            'id' =>$this->id,
            'name' => $this->name,
            'quantity' =>$this->quantity,
            'original_price' =>$this->original_price,
            'selling_price' =>$this->selling_price,
            'type' =>$this->type,
            "colors" =>$this->colors,
            "sizes" =>$this->sizesAndAlternatives(),
            "created_at"=>$this->created_at,
            'description' =>$this->description,
            'category' => $this->category,
            'reviews_summary'=> $this->reviews_summary,
            "sale" => $this->current_sale,
            'images' => $this->colorsImagesObject($this->colors_array),
            "thumbnail" => $this->thumbnail,
        ];
    }
}
