<?php

namespace App\Http\Resources;

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoritesListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    private $page;
    private $limit;
    private $sort_by;
    private $search;

    public function setPage($page){
        $this->page = $page;
    }

    public function setLimit($limit){
        $this->limit = $limit;
    }

    public function setSortBy($sort_by){
        $this->sort_by = $sort_by;
    }

    public function setSearch($search){
        $this->search = $search;
    }

    public function toArray(Request $request): array
    {
        $favorites = $this->favorites()->with("product");
        if ($this->search){
            //handle search
        }

        $sorted_favorites = ProductController::sortCollection($favorites, $this->sort_by);
        $sorted_limited_favorites = ProductController::filterNumber($sorted_favorites,$this->page,$this->limit);
        
        return [
            "favorites_list" => [
                "id"=>$this->id,
                'public' =>$this->public,
                "created_at" => $this->created_at,
                'likes_count' => $this->likes_count,
                'views_count' => $this->views_count,
                'user' => $this->user,
                "favorites" => $sorted_limited_favorites,
            ],
            "total_count" => $favorites->count(),
            "count" =>$sorted_limited_favorites->count()
        ];
    }
}
