<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        return  [
            "image_details" => $this->image_details,
            "color" => $this->color->color,
            "size" => $this->size->size,
            "url" => $this->image_url
        ];
    }
}
