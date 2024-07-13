<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
   

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {

        $rules = [
            'name' =>['bail','required', 'string', 'max:256'],
            'email'=>['bail','unique:users,email', 'required', 'email'],
            'password' =>['bail','required' ,'string' ,'regex:/\d/','regex:/.*[A-Z].*/','min:8','same:confirm_password'],
            'confirm_password'=> ['bail','required','same:password'],
        ];

        return $rules;
    }

    public function messages(){
        return [
            'password.min' => 'Password should be at least 8 characters.',
            'password.regex' => "The password should contain a capital letter",
            'password.same' => '',
            'confirm_password.same' => "Passwords do not match.", 
        ];
    }
}
