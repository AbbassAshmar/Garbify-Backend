<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function test(Request $request){
        // dd(json_decode($request->input('text'),true));
        // $validator = Validator::make($data, [
        //     'sizes_data' => 'required|array|max:7',
        // ]);

        // dd($request->input('text'));
        // dd($request->input('text'));
        // dd($request->file('text.0.images'));
        return response('j',200);
    }   
    
}
