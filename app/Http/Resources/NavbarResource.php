<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use app\Models\Category;
class NavbarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [ 
            "name" =>$this->category,
            "children" =>NavbarResource::collection(Category::where("parent_id", $this->id)->get()),
        ];
    }
    public static $wrap = 'categories';
}
