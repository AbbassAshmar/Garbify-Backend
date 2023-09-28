<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            "id"=>$this->id,
            "created_at"=>$this->created_at,
            "status"=>$this->status,
            "total_cost"=>$this->total_cost,
            "shipping_status"=>$this->shipping_status,
            "return_cancellation_info" =>$this->return_cancellation_info,
            "recipient_name"=>$this->shippingAddress->recipient_name,
            "products"=> OrderDetailResource::collection($this->orderDetails)
        ];
    }
}
