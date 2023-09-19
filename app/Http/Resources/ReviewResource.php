<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{


    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=>$this->id,
            "username"=>$this->user->name,
            "rating"=>floatval($this->product_rating),
            "color"=>$this->color? $this->color->color: null,
            "size"=>$this->size ? $this->size->size: null,
            "title"=>$this->title,
            "text"=>$this->text,
            "images"=> $this->review_images_list,
            "helpful_count"=>$this->helpful_count,
            "created_at"=>$this->created_at,
            'user_height' =>$this->user_height,
            'user_weight'=>$this->user_weight
        ];
    }

}
