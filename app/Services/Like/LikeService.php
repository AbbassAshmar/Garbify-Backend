<?php

namespace App\Services\Like;

use App\Helpers\TransactionHelper;
use App\Helpers\GetResponseHelper;

class LikeService{
  
    public static function toggleLikeOnResource($resource, $user, $fieldName){
        return TransactionHelper::makeTransaction(function ($resource, $user, $fieldName) {
            $like = $resource->likes()->where('user_id', $user->id)->first();

            if ($like) {
                $resource->likes()->detach($user->id);
                $data = ['action' => 'unliked'];
            } else {
                $resource->likes()->attach([$user->id]);
                $data = ['action' => 'liked'];
            }

            $newCount = $resource->likes()->count();
            $resource->update([$fieldName => $newCount]);
            $metadata = [$fieldName => $newCount];

            return GetResponseHelper::getSuccessResponse($data, $metadata);
        }, [$resource, $user, $fieldName]);
    }
}