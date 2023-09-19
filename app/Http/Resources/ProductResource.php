<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use app\Models\Product;
use app\Models\Image;
// use app\Http\Resources\ImageResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    // customizes the json representation of image and images collection manually
    public function returnImages($images) {
        if (!$images) return null;
        if ($images instanceof Image){
            return [
                "image_details" => $images->image_details,
                "color" => $images->color->color,
                "size" => $images->size->size,
                "url" => $images->image_url
            ];
        }

        $result = [];
        foreach($images as $img){            
            $obj = [
                "image_details" => $img->image_details,
                "color" => $img->color->color,
                "size" => $img->size->size,
                "url" => $img->image_url
            ];
            array_push($result, $obj);
        }  
        return $result; 
    }


    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        return [
            'pk' =>$this->id,
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
            "thumbnail" =>  new ImageResource($this->thumbnail),
        ];
    }
}
