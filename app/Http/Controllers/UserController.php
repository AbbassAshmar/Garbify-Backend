<?php

namespace App\Http\Controllers;


use App\Helpers\GetResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\User\UserService;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\CreateUserRequest;
use App\Services\FavoritesList\FavoritesListService;
use App\Services\User\ShoppingCartService;

class UserController extends Controller
{
    private $userService;
    private $favoritesListService;
    private $shoppingCartService;

    function __construct(UserService $userService, FavoritesListService $favoritesListService, ShoppingCartService $shoppingCartService){
        $this->userService = $userService;
        $this->favoritesListService = $favoritesListService;
        $this->shoppingCartService = $shoppingCartService;
    }

    public function retrieveUser($request, $id){
        $requester = $request->user();
        $user = $this->userService->getByID($id);

        if (!$requester->can("retrieve_admin") && $user->hasRole('admin')){
            $error = [
                'message'=>"You do not have the required permission to retrieve an admin's information.",
                'code'=>403
            ];
            $response = GetResponseHelper::getFailedResponse($error,null);
            return response($response,403);
        }

        if (!$requester->can("retrieve_super_admin") && $user->hasRole('super admin')){
            $error = [
                'message'=>"You do not have the required permission to retrieve a super admin's information.",
                'code'=>403
            ];
            $response = GetResponseHelper::getFailedResponse($error,null);
            return response($response,403);
        }

        $response = GetResponseHelper::getSuccessResponse(['user'=>$user],null);
        return response($response, 200);
    }

    public function createAdmin(CreateUserRequest $request){
        $data = $request->validated();
        ['user'=>$user, 'error' => $error] = $this->userService->createUser($data,"admin");

        if ($user == null && $error){
            $error = ['message'=>$error, 'code'=>400];
            $data = ['action' => "not created"];
            $responseBody = GetResponseHelper::getFailedResponse($error,null, $data);
            return response($responseBody,400);
        }

        $this->favoritesListService->createFavoritesListForUser($user);
        $this->shoppingCartService->createShoppingCartForUser($user);

        $body = ['action' => 'created'];
        $responseBody = GetResponseHelper::getSuccessResponse($body,null);
        return response($responseBody,201);
    }

    public function createClient(CreateUserRequest $request){
        $data = $request->validated();
        ['user'=>$user, 'error' => $error] = $this->userService->createUser($data,"client");

        if ($user == null && $error){
            $error = ['message'=>$error, 'code'=>400];
            $data = ['action' => "not created"];
            $responseBody = GetResponseHelper::getFailedResponse($error,null, $data);
            return response($responseBody,400);
        }

        $this->favoritesListService->createFavoritesListForUser($user);
        $this->shoppingCartService->createShoppingCartForUser($user);

        $body = ['action' => 'created'];
        $responseBody = GetResponseHelper::getSuccessResponse($body,null);
        return response($responseBody,201);
    }

    public function updateUser(Request $request){
        $user = $request->user();
        $validated_data = $request->validate([
            'email'=>['bail','unique:users,email', 'email'],
            'name'=>['bail', 'max:256','string'],
            'profile_picture'=>['bail','max:5000', 'image','mimes:jpg,jpeg,png'],
            'old_password'=> [
                'bail',
                'required_with:password,confirm_password',
                'string' ,
                'current_password',
            ],
            'password'=> [
                'required_with:old_password,confirm_password',
                'bail',
                'string',
                'regex:/\d/',
                'regex:/.*[A-Z].*/',
                'min:8',
                'same:confirm_password'
            ],
            'confirm_password'=>[
                'required_with:old_password,password',
                'bail',
                'same:password'
            ],
            'id'=>['prohibited'],
            'created_at'=>['prohibited'],
            'updated_at'=>['prohibited'],
        ],[
            'password.min' => 'Password should be at least 8 characters.',
            'created_at.prohibited'=>"You do not have the required authorization to update this field.", 
            'updated_at.prohibited'=>"You do not have the required authorization to update this field.", 
            'id.prohibited'=>"You do not have the required authorization to update this field.", 
            'confirm_password.same'=>"Passwords do not match.",
            "password.required_with" => 'A new password is required to change password.',
            "old_password.required_with" => 'Old password is required to change password.',
            "confirm_password.required_with" => 'confirm password is required to change password.',
            'password.same'=>''
        ]);

        if (empty($validated_data)){
            return response(GetResponseHelper::getSuccessResponse(null, null),200);
        }

        // remove old password and confirm password from the update array
        if(isset($validated_data['password'])){
            if(!Hash::check($validated_data['old_password'],$user->password)){
                throw ValidationException::withMessages([
                    'old password' => 'Old password is incorrect.'
                ]);
            }
            unset($validated_data['old_password']);
            unset($validated_data['confirm_password']);
        }

        // store profile picture 
        if(isset($validated_data['profile_picture'])){
            $name = $validated_data['profile_picture']->hashName();
            $path= $validated_data['profile_picture']->storeAs('/public/usersProfilePictures',$name);
            $validated_data['profile_picture'] = basename($path);
        }

        $update = $user->update($validated_data);
        
        if (isset($validated_data['password'])){
            $validated_data['password']= $user->password;
        }

        if(isset($validated_data['profile_picture'])){
            $symlink_dir = '/storage/usersProfilePictures/'.$validated_data['profile_picture'];
            $validated_data['profile_picture'] = asset($symlink_dir);        
        }

        if ($update){
            return GetResponseHelper::processDataFormating($validated_data,"user");
        } 

        $error = ['message'=>'Update failed.', 'code'=>400];
        $response_body = GetResponseHelper::getFailedResponse($error,null);
        return response($response_body,400);
    }  


}
